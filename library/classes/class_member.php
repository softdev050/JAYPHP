<?php 

class TSUE_Member
{
    public $info = NULL;
    public $remember_me = false;
    public $testingPermission = false;

    //public function TSUE_Member()
	function __construct()
    {
        global $TSUE;
		if (isset($_COOKIE["tsue_member"])) {
        	$_COOKIE["tsue_member"] = base64_decode($_COOKIE["tsue_member"]);
		}
		else{
			$_COOKIE["tsue_member"] =base64_decode("0");
		}
        if( isset($_COOKIE["tsue_member"]) && $_COOKIE["tsue_member"] && !empty($_COOKIE["tsue_member"]) && substr_count($_COOKIE["tsue_member"], "_") == 3 ) 
        {
            $tsue_member = tsue_explode("_", $_COOKIE["tsue_member"]);
            $cookie_remember = (isset($tsue_member["0"]) && $tsue_member["0"] == "1" ? true : false);
            $cookie_memberid = (isset($tsue_member["1"]) && is_numeric($tsue_member["1"]) ? intval($tsue_member["1"]) : 0);
            $cookie_password = (isset($tsue_member["2"]) ? trim($tsue_member["2"]) : 0);
            $cookie_member_ip = (isset($tsue_member["3"]) ? trim($tsue_member["3"]) : 0);
            if( $cookie_memberid && strlen($cookie_password) === 32 && strlen($cookie_member_ip) === 32 ) 
            {
                $Member = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE m.*, g.*, p.*, b.memberid AS isBanned, w.memberid AS isWarned, aw.warned AS autoWarnedDate, MAX(s.expiry_date) AS expiry_date \r\n\t\t\t\tFROM tsue_members m \r\n\t\t\t\tINNER JOIN tsue_membergroups g USING(membergroupid) \r\n\t\t\t\tINNER JOIN tsue_member_profile p USING(memberid) \r\n\t\t\t\tLEFT JOIN tsue_member_bans b USING(memberid) \r\n\t\t\t\tLEFT JOIN tsue_member_warns w USING(memberid) \r\n\t\t\t\tLEFT JOIN tsue_auto_warning aw USING(memberid) \r\n\t\t\t\tLEFT JOIN tsue_member_upgrades_promotions s ON(s.memberid=m.memberid && s.active = 1) \r\n\t\t\t\tWHERE m.memberid = " . $TSUE["TSUE_Database"]->escape($cookie_memberid) . " \r\n\t\t\t\tGROUP BY m.memberid", true);
                if( $Member && $Member["password"] === $cookie_password && $cookie_member_ip === md5(MEMBER_IP) ) 
                {
                    if( $Member["isBanned"] ) 
                    {
                        $Member["membergroupid"] = is_member_of("banned", true);
                    }
                    else
                    {
                        if( has_permission("canlogin_admincp", $Member["permissions"]) && isset($_COOKIE["testPermissions"]) && is_valid_string($_COOKIE["testPermissions"]) ) 
                        {
                            $testPerms = $TSUE["TSUE_Database"]->query_result("SELECT m.membername, m.membergroupid, g.permissions FROM tsue_members m INNER JOIN tsue_membergroups g USING(membergroupid) WHERE m.membername = " . $TSUE["TSUE_Database"]->escape($_COOKIE["testPermissions"]));
                            if( $testPerms ) 
                            {
                                $Member["membergroupid"] = $testPerms["membergroupid"];
                                $Member["permissions"] = $testPerms["permissions"];
                                $this->testingPermission = $testPerms["membername"];
                                unset($testPerms);
                            }

                        }

                    }

                    if( $cookie_remember ) 
                    {
                        $this->remember_me = true;
                    }

                    $updateAccount = array(  );
                    if( !$Member["themeid"] || !in_array($Member["themeid"], tsue_explode(",", $TSUE["TSUE_Settings"]->settings["global_settings"]["available_themes"])) ) 
                    {
                        $Member["themeid"] = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_themeid"];
                    }

                    if( !$Member["languageid"] || !in_array($Member["languageid"], tsue_explode(",", $TSUE["TSUE_Settings"]->settings["global_settings"]["available_languages"])) ) 
                    {
                        $Member["languageid"] = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"];
                    }

                    if( $Member["timezone"] === "" ) 
                    {
                        $Member["timezone"] = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_timezone"];
                    }

                    $Member["defaultTorrentCategories"] = ($Member["defaultTorrentCategories"] ? tsue_explode(",", $Member["defaultTorrentCategories"]) : array(  ));
                    if( !$Member["cpOptions"] ) 
                    {
                        $Member["cpOptions"] = array( "shoutbox_enabled" => 1, "irtm_enabled" => 1, "alerts_enabled" => 1 );
                    }
                    else
                    {
                        $Member["cpOptions"] = unserialize($Member["cpOptions"]);
                    }

                    $canFreeLeech = has_permission("do_not_record_download_stats", $Member["permissions"]);
                    if( $Member["download_multiplier"] && $canFreeLeech ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "0" ), "memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]));
                    }
                    else
                    {
                        if( !$Member["download_multiplier"] && !$canFreeLeech ) 
                        {
                            $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "1" ), "memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]));
                        }

                    }

                    $Member["email"] = strip_tags($Member["email"]);
                    $Member["membername"] = strip_tags($Member["membername"]);
                    $this->info = $Member;
                    $this->info["permissions"] = unserialize($Member["permissions"]);
                    unset($Member);
                    if( !$this->info["passkey"] ) 
                    {
                        $this->info["passkey"] = generatePasskey();
                        $updateAccount[] = "passkey = " . $TSUE["TSUE_Database"]->escape($this->info["passkey"]);
                    }

                    if( !$this->info["torrent_pass"] ) 
                    {
                        $this->info["torrent_pass"] = substr($this->info["passkey"], 0, 32);
                        $TSUE["TSUE_Database"]->shutdown_query("UPDATE tsue_member_profile SET torrent_pass=" . $TSUE["TSUE_Database"]->escape($this->info["torrent_pass"]) . " WHERE memberid = " . $TSUE["TSUE_Database"]->escape($this->info["memberid"]));
                    }

                    $this->info["csrf_token"] = sha1($this->info["memberid"] . sha1($this->info["passkey"]) . sha1(MEMBER_IP));
                    $this->info["csrf_token_page"] = $this->info["memberid"] . "-" . TIMENOW . "-" . sha1(TIMENOW . $this->info["csrf_token"]);
                    if( $this->info["ipaddress"] != MEMBER_IP ) 
                    {
                        $updateAccount[] = "ipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP);
                    }

                    if( $this->info["inactivitytag"] ) 
                    {
                        $updateAccount[] = "inactivitytag = 0";
                    }

                    if( $this->info["passwordExpires"] ) 
                    {
                        $passwordDaysOld = floor((TIMENOW - $this->info["password_date"]) / 86400);
                        if( $this->info["passwordExpires"] <= $passwordDaysOld && !defined("IS_AJAX") && defined("SCRIPTNAME") && !in_array(SCRIPTNAME, array( "ajax.php", "announce.php", "auto_uploader.php", "cron.php", "js.php", "payment_gateway.php", "scrape.php", "style.php", "membercp.php", "dialog.php", "goto.php", "notfound.php", "rss.php" )) ) 
                        {
                            define("PASSWORDEXPIRED", $passwordDaysOld);
                        }

                    }

                    if( $this->info["accountParked"] && !defined("IS_AJAX") && defined("SCRIPTNAME") && !in_array(SCRIPTNAME, array( "ajax.php", "announce.php", "auto_uploader.php", "cron.php", "js.php", "payment_gateway.php", "scrape.php", "style.php", "membercp.php", "dialog.php", "goto.php", "notfound.php", "rss.php" )) ) 
                    {
                        define("ACCOUNTPARKED", true);
                    }

                    if( !defined("NO_LASTACTIVITY_UPDATE") ) 
                    {
                        $timeOut = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_timeout"] * 60;
                        if( defined("SCRIPTNAME") && SCRIPTNAME == "forums.php" ) 
                        {
                            if( $timeOut < TIMENOW - $this->info["lastforumactivity"] ) 
                            {
                                $updateAccount[] = "lastforumvisit = lastforumactivity, lastforumactivity = " . TIMENOW;
                            }
                            else
                            {
                                $updateAccount[] = "lastforumactivity = " . TIMENOW;
                            }

                        }

                        if( $timeOut < TIMENOW - $this->info["lastactivity"] ) 
                        {
                            $updateAccount[] = "lastvisit = lastactivity, lastactivity = " . TIMENOW;
                        }
                        else
                        {
                            $updateAccount[] = "lastactivity = " . TIMENOW;
                        }

                        $TSUE["TSUE_Database"]->shutdown_query("UPDATE tsue_members SET " . implode(",", $updateAccount) . " WHERE memberid = " . $TSUE["TSUE_Database"]->escape($this->info["memberid"]));
                        unset($updateAccount);
                    }

                    if( getSetting("global_settings", "security_ip_history", 0) ) 
                    {
                        $TSUE["TSUE_Database"]->replace("tsue_member_ip_history", array( "memberid" => $this->info["memberid"], "ipaddress" => MEMBER_IP ), true);
                    }

                }

            }

        }

        if( !$this->info ) 
        {
            $guestGroupID = is_member_of("unregistered", true);
            $this->info["memberid"] = 0;
            $this->info["membergroupid"] = $guestGroupID;
            $this->info["membername"] = "Guest";
            $this->info["passkey"] = generatePasskey();
            $this->info["email"] = "";
            $this->info["themeid"] = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_themeid"];
            $this->info["languageid"] = $this->getGuestLanguage();
            $this->info["lastvisit"] = TIMENOW;
            $this->info["lastactivity"] = TIMENOW;
            $this->info["lastforumvisit"] = TIMENOW;
            $this->info["lastforumactivity"] = TIMENOW;
            $this->info["timezone"] = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_timezone"];
            $this->info["dst"] = 0;
            $this->info["ipaddress"] = MEMBER_IP;
            $this->info["gender"] = "";
            $this->info["visible"] = 0;
            $this->info["unread_alerts"] = 0;
            $this->info["unread_messages"] = 0;
            $this->info["accountParked"] = 0;
            $this->info["csrf_token"] = sha1($this->info["themeid"] . $this->info["languageid"] . sha1(MEMBER_IP) . $this->info["timezone"]);
            $this->info["csrf_token_page"] = $this->info["memberid"] . "-" . TIMENOW . "-" . sha1(TIMENOW . $this->info["csrf_token"]);
            $this->info["date_of_birth"] = "";
            $this->info["signature"] = "";
            $this->info["country"] = "";
            $this->info["custom_title"] = "";
            $this->info["uploaded"] = 0;
            $this->info["downloaded"] = 0;
            $this->info["total_posts"] = 0;
            $this->info["invites_left"] = 0;
            $this->info["points"] = 0;
            $this->info["total_warns"] = 0;
            $this->info["muted"] = "";
            $this->info["hitRuns"] = 0;
            $this->info["torrentStyle"] = 1;
            $this->info["cpOptions"] = array( "shoutbox_enabled" => 1, "irtm_enabled" => 0, "alerts_enabled" => 0 );
            $this->info["defaultTorrentCategories"] = array(  );
            $QuestQuery = $TSUE["TSUE_Database"]->query_result("SELECT groupname, groupstyle, permissions, flood_limit FROM tsue_membergroups WHERE membergroupid = " . $guestGroupID);
            $this->info["groupname"] = $QuestQuery["groupname"];
            $this->info["groupstyle"] = $QuestQuery["groupstyle"];
            $this->info["permissions"] = unserialize($QuestQuery["permissions"]);
            $this->info["flood_limit"] = $QuestQuery["flood_limit"];
        }

        if( !defined("NO_SESSION_UPDATE") ) 
        {
            $VALUES = array_map(array( $TSUE["TSUE_Database"], "escape" ), array( $this->info["memberid"], MEMBER_IP, SCRIPTNAME, (isset($_SERVER["HTTP_USER_AGENT"]) ? strip_tags($_SERVER["HTTP_USER_AGENT"]) : "--"), TIMENOW, (isset($_SERVER["HTTP_REFERER"]) ? strip_tags($_SERVER["HTTP_REFERER"]) : "--"), (isset($_SERVER["QUERY_STRING"]) ? strip_tags($_SERVER["QUERY_STRING"]) : "--") ));
            $TSUE["TSUE_Database"]->shutdown_query("INSERT INTO tsue_session VALUES\r\n\t\t\t\t(" . implode(",", $VALUES) . ")\r\n\t\t\tON DUPLICATE KEY UPDATE \r\n\t\t\t\t`location` = " . $VALUES["2"] . ",\r\n\t\t\t\t`browser` = " . $VALUES["3"] . ",\r\n\t\t\t\t`date` = " . $VALUES["4"] . ",\r\n\t\t\t\t`http_referer` = " . $VALUES["5"] . ",\r\n\t\t\t\t`query_string` = " . $VALUES["6"]);
        }

    }

    public function getGuestLanguage()
    {
        global $TSUE;
        $languageid = (isset($_COOKIE["tsue_guest_language"]) ? intval($_COOKIE["tsue_guest_language"]) : $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"]);
        if( !$languageid || !in_array($languageid, tsue_explode(",", $TSUE["TSUE_Settings"]->settings["global_settings"]["available_languages"])) ) 
        {
            $languageid = $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"];
        }

        return $languageid;
    }

}



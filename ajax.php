<?php 
define("SCRIPTNAME", "ajax.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

switch( $TSUE["action"] ) 
{
    case "refreshTopUploaders":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_topUploaders.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_topUploaders.php");
            ajax_message(TSUEPlugin_topUploaders(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshTopDonors":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_topDonors.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_topDonors.php");
            ajax_message(TSUEPlugin_topDonors(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshNewestMembers":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_newestMembers.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_newestMembers.php");
            ajax_message(TSUEPlugin_newestMembers(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshRecentThreads":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_recentThreads.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_recentThreads.php");
            ajax_message(TSUEPlugin_recentThreads(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshDonateUs":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_donate.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_donate.php");
            ajax_message(TSUEPlugin_donate(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "autoDescription":
        globalize("post", array( "field_id" => "INT" ));
        if( $field_id ) 
        {
            $Field = $TSUE["TSUE_Database"]->query_result("SELECT default_value, viewpermissions FROM tsue_auto_description WHERE field_id = " . $TSUE["TSUE_Database"]->escape($field_id) . " AND active = 1");
            if( $Field ) 
            {
                if( $Field["viewpermissions"] ) 
                {
                    $Field["viewpermissions"] = unserialize($Field["viewpermissions"]);
                    if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Field["viewpermissions"]) ) 
                    {
                        exit();
                    }

                }

                ajax_message(nl2br($Field["default_value"]), "", false);
            }

        }

        break;
    case "update_member_language":
        globalize("post", array( "languageid" => "INT" ));
        if( !$languageid ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $Language = $TSUE["TSUE_Database"]->query_result("SELECT title FROM tsue_languages WHERE languageid = " . $TSUE["TSUE_Database"]->escape($languageid) . " AND active = 1");
        if( !$Language ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( is_member_of("unregistered") ) 
        {
            cookie_set("tsue_guest_language", $languageid, TIMENOW + 31536000);
        }
        else
        {
            $TSUE["TSUE_Database"]->update("tsue_members", array( "languageid" => $languageid ), "memberid=" . $TSUE["TSUE_Member"]->info["memberid"]);
        }

        break;
    case "select_language":
        $Languages = $TSUE["TSUE_Database"]->query("SELECT languageid, title FROM tsue_languages WHERE active = 1");
        $language_select_language = "";
        while( $Language = $TSUE["TSUE_Database"]->fetch_assoc($Languages) ) 
        {
            eval("\$language_select_language .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("language_select_language") . "\";");
        }
        ajax_message($language_select_language, "", false, get_phrase("language_select_your_language"));
        break;
    case "preview_message":
        globalize("post", array( "message" => "TRIM" ));
        $message = $TSUE["TSUE_Parser"]->parse($message);
        ajax_message($message, "", false, get_phrase("button_preview"));
        break;
    case "search_membername":
        globalize("post", array( "keywords" => "TRIM" ));
        if( !is_member_of("unregistered") && is_valid_string($keywords) ) 
        {
            $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid, membername, gender FROM `tsue_members` WHERE `membername` LIKE '%" . $TSUE["TSUE_Database"]->escape_no_quotes($keywords) . "%' ORDER BY membername ASC LIMIT 15");
            if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
            {
                $worked = 0;
                for( $results = ""; $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members); $worked++ ) 
                {
                    $class = ($worked % 2 == 0 ? "ac_even" : "");
                    $content = $_membername = $Member["membername"];
                    $inputName = "receiver_membername";
                    $_memberid = $Member["memberid"];
                    $_alt = "";
                    $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
                    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                    eval("\$results .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("autocomplete_results") . "\";");
                }
                eval("\$SearchResults = \"" . $TSUE["TSUE_Template"]->LoadTemplate("autocomplete") . "\";");
                ajax_message($SearchResults);
            }

        }

        break;
    case "search_torrent":
        globalize("post", array( "keywords" => "TRIM", "search_type" => "TRIM" ));
        if( 3 <= strlen($keywords) && $search_type ) 
        {
            switch( $search_type ) 
            {
                case "name":
                    $whereCondition = explodeSearchKeywords("t.name", $keywords);
                    $scoreSQL = $whereCondition . " AS Score,";
                    $orderSQL = "ORDER BY Score DESC";
                    break;
                case "description":
                    $whereCondition = explodeSearchKeywords("t.description", $keywords);
                    $scoreSQL = $whereCondition . " AS Score,";
                    $orderSQL = "ORDER BY Score DESC";
                    break;
                case "both":
                case "default":
                    $whereCondition = explodeSearchKeywords("t.name,t.description", $keywords);
                    $scoreSQL = $whereCondition . " AS Score,";
                    $orderSQL = "ORDER BY Score DESC";
                    break;
                case "uploader":
                    if( !is_valid_string($keywords) ) 
                    {
                        exit();
                    }

                    $whereCondition = "m.membername=" . $TSUE["TSUE_Database"]->escape($keywords);
                    $hideAnonymouseTorrents = true;
                    $scoreSQL = "";
                    $orderSQL = "ORDER BY m.membername ASC";
                    break;
            }
            if( !isset($whereCondition) ) 
            {
                exit();
            }

            $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE " . $scoreSQL . "t.*, m.membername, g.groupstyle, c.cname, c.cviewpermissions, a.filename, i.content as IMDBContent\r\n\t\t\tFROM tsue_torrents t \r\n\t\t\tLEFT JOIN tsue_members m on (t.owner=m.memberid) \r\n\t\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\t\tLEFT JOIN tsue_torrents_categories c ON(t.cid=c.cid) \r\n\t\t\tLEFT JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) \r\n\t\t\tLEFT JOIN tsue_imdb i USING(tid) \r\n\t\t\tWHERE t.awaitingModeration = 0 AND " . $whereCondition . "\r\n\t\t\tGROUP BY t.tid " . $orderSQL . " LIMIT 10");
            if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
            {
                require_once(REALPATH . "/library/functions/functions_getTorrents.php");
                $Output = "";
                while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
                {
                    if( $Torrent["flags"] != 1 && $Torrent["size"] && $Torrent["name"] && hasViewPermission($Torrent["cviewpermissions"]) ) 
                    {
                        $Torrent["name"] = strip_tags($Torrent["name"]);
                        $Torrent["options"] = unserialize($Torrent["options"]);
                        $Torrent["IMDBContent"] = parseIMDB($Torrent["IMDBContent"]);
                        $Torrent["owner"] = get_phrase("torrents_owner", convert_relative_time($Torrent["added"]), $Torrent["membername"]);
                        $Torrent["size"] = friendly_size($Torrent["size"]);
                        $torrentFlags = getTorrentFlags($Torrent);
                        $torrentMultipliers = getTorrentMultipliers($Torrent);
                        $_memberid = $Torrent["owner"];
                        $_membername = getMembername($Torrent["membername"], $Torrent["groupstyle"]);
                        $categoryImage = get_torrent_category_image($Torrent["cid"]);
                        if( isAnonymouse($Torrent) ) 
                        {
                            if( isset($hideAnonymouseTorrents) ) 
                            {
                                continue;
                            }

                            $_memberid = 0;
                            $_membername = get_phrase("torrents_anonymouse_uploader");
                        }

                        if( is_valid_image($Torrent["filename"]) && is_file(REALPATH . "/data/torrents/torrent_images/s/" . $Torrent["filename"]) ) 
                        {
                            $Poster = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/s/" . $Torrent["filename"];
                        }
                        else
                        {
                            if( $Torrent["IMDBContent"]["title_id"] && is_file(REALPATH . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg") ) 
                            {
                                $Poster = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg";
                            }
                            else
                            {
                                $Poster = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_s.png";
                            }

                        }

                        eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("auto_suggest_torrent_list") . "\";");
                    }

                }
                ajax_message($Output);
            }

        }

        break;
    case "report":
        globalize("post", array( "content_type" => "TRIM", "content_id" => "INT", "report_reason" => "TRIM" ));
        if( !has_permission("canreport") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$content_id ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $content_type == "message" ) 
        {
            $Reply = $TSUE["TSUE_Database"]->query_result("SELECT r.reply_id, m.owner_memberid, m.receiver_memberid FROM tsue_messages_replies r INNER JOIN tsue_messages_master m USING(message_id) WHERE r.reply_id = " . $TSUE["TSUE_Database"]->escape($content_id));
            if( !$Reply ) 
            {
                ajax_message(get_phrase("message_content_error"), "-ERROR-");
            }
            else
            {
                if( $Reply["owner_memberid"] != $TSUE["TSUE_Member"]->info["memberid"] && $Reply["receiver_memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

            }

            $report_button = $TSUE["TSUE_Language"]->phrase["messages_report"];
        }
        else
        {
            if( $content_type == "profile_comments" ) 
            {
                if( !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $content_id ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $Privacy = $TSUE["TSUE_Database"]->query_result("SELECT allow_view_profile FROM tsue_member_privacy WHERE memberid = " . $TSUE["TSUE_Database"]->escape($content_id));
                $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
                $PassiveUser = array( "memberid" => $content_id, "allow_view_profile" => $Privacy["allow_view_profile"] );
                if( !canViewProfile($ActiveUser, $PassiveUser) ) 
                {
                    ajax_message(get_phrase("membercp_limited_view"), "-ERROR-");
                }

                $report_button = $TSUE["TSUE_Language"]->phrase["report_profile_post"];
            }
            else
            {
                if( $content_type == "torrent" ) 
                {
                    if( !has_permission("canview_torrents") ) 
                    {
                        ajax_message(get_phrase("permission_denied"), "-ERROR-");
                    }

                    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT tid FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($content_id));
                    if( !$Torrent ) 
                    {
                        ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
                    }

                    $report_button = $TSUE["TSUE_Language"]->phrase["torrents_report"];
                }
                else
                {
                    if( $content_type == "torrent_comments" ) 
                    {
                        if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                        {
                            ajax_message(get_phrase("permission_denied"), "-ERROR-");
                        }

                        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT content_id FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($content_id));
                        if( !$Torrent ) 
                        {
                            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
                        }

                        $report_button = $TSUE["TSUE_Language"]->phrase["torrents_report_comment"];
                    }
                    else
                    {
                        if( $content_type == "peer" ) 
                        {
                            if( !has_permission("canview_torrents") ) 
                            {
                                ajax_message(get_phrase("permission_denied"), "-ERROR-");
                            }

                            $MemberCheck = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($content_id));
                            if( !$MemberCheck ) 
                            {
                                ajax_message(get_phrase("member_not_found"), "-ERROR-");
                            }

                            $report_button = $TSUE["TSUE_Language"]->phrase["torrents_peer_report"];
                        }
                        else
                        {
                            if( $content_type == "forum_post" ) 
                            {
                                $report_button = $TSUE["TSUE_Language"]->phrase["forums_report_post"];
                            }
                            else
                            {
                                if( $content_type == "file_comments" ) 
                                {
                                    require_once(REALPATH . "library/functions/functions_downloads.php");
                                    checkOnlineStatus();
                                    $report_button = $TSUE["TSUE_Language"]->phrase["report_file"];
                                }
                                else
                                {
                                    if( $content_type == "ig_foto" ) 
                                    {
                                        require_once(REALPATH . "library/functions/functions_imageGallery.php");
                                        checkOnlineStatus();
                                        $report_button = $TSUE["TSUE_Language"]->phrase["report_image"];
                                    }
                                    else
                                    {
                                        ajax_message(get_phrase("permission_denied"), "-ERROR-");
                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

        $strlenOriginalText = strlenOriginalText($report_reason);
        if( $TSUE["do"] == "save" && $strlenOriginalText < 3 ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            $BuildQuery = array( "content_type" => $content_type, "content_id" => $content_id, "reported_by_memberid" => $TSUE["TSUE_Member"]->info["memberid"], "first_report_date" => TIMENOW, "comment_count" => 1 );
            check_flood("report");
            if( $TSUE["TSUE_Database"]->insert("tsue_reports", $BuildQuery, false, " ON DUPLICATE KEY UPDATE comment_count = comment_count + 1") ) 
            {
                $report_id = $TSUE["TSUE_Database"]->insert_id();
                $BuildQuery = array( "report_id" => $report_id, "comment_date" => TIMENOW, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "message" => $report_reason );
                if( $TSUE["TSUE_Database"]->insert("tsue_reports_comments", $BuildQuery) ) 
                {
                    $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                    if( $searchMembergroups ) 
                    {
                        $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                        if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                        {
                            while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                            {
                                alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "report", $report_id, "new_report");
                            }
                        }

                    }

                    ajax_message(get_phrase("report_thanks"));
                }

            }

            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post_form") . "\";");
        ajax_message($Output, "", false, $report_button);
        break;
    case "member_info":
        globalize("post", array( "memberid" => "INT" ));
        if( !$memberid ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }
        else
        {
            if( !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
            {
                ajax_message(get_phrase("permission_denied"), "-ERROR-");
            }
            else
            {
                require_once(REALPATH . "/library/classes/class_awards.php");
                $TSUE_Awards = new TSUE_Awards($memberid);
                $memberAwards = $TSUE_Awards->getMemberAwards($memberid);
                require_once(REALPATH . "library/functions/functions_memberInfo.php");
                $MemberInfo = memberInfo($memberid, true, "yes", "l", $memberAwards);
                if( !is_array($MemberInfo) ) 
                {
                    ajax_message($MemberInfo, "-ERROR-");
                }

                ajax_message($MemberInfo["0"]);
            }

        }

        break;
    case "total_warns":
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        prepareWarns($TSUE["TSUE_Member"]->info["memberid"]);
        break;
    case "hitrun_warns":
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        prepareHitRunWarns($TSUE["TSUE_Member"]->info["memberid"]);
        break;
    case "member_mutes":
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        prepareMutes($TSUE["TSUE_Member"]->info["memberid"]);
        break;
    case "download_history":
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        prepareDownloadHistory($TSUE["TSUE_Member"]->info["memberid"]);
        break;
    case "refreshMemberStats":
        if( !is_member_of("unregistered") ) 
        {
            $ul_dl_stats = ul_dl_stats($TSUE["TSUE_Member"]->info["uploaded"], $TSUE["TSUE_Member"]->info["downloaded"]);
            ajax_message($ul_dl_stats);
        }
        else
        {
            ajax_message(get_phrase("login_required"));
        }

        break;
    case "refreshOnlineList":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_onlineMembers.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_onlineMembers.php");
            ajax_message(TSUEPlugin_onlineMembers(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshlast24OnlineList":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_last24onlineMembers.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_last24onlineMembers.php");
            ajax_message(TSUEPlugin_last24onlineMembers(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshStaffOnlineNow":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_staffOnlineNow.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_staffOnlineNow.php");
            ajax_message(TSUEPlugin_staffOnlineNow(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshWebsiteStats":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_websiteStats.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_websiteStats.php");
            ajax_message(TSUEPlugin_websiteStats(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "refreshtodaysBirthdays":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_todaysBirthdays.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_todaysBirthdays.php");
            ajax_message(TSUEPlugin_todaysBirthdays(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  ))));
        }

        break;
    case "check_alerts":
        $Data = array( "unread_alerts" => $TSUE["TSUE_Member"]->info["unread_alerts"], "unread_messages" => $TSUE["TSUE_Member"]->info["unread_messages"], "total" => intval($TSUE["TSUE_Member"]->info["unread_messages"] + $TSUE["TSUE_Member"]->info["unread_alerts"]) );
        jsonHeaders($Data);
        break;
    case "view_alerts":
        require_once(REALPATH . "/library/functions/functions_getAlerts.php");
        ajax_message(showMemberAlerts(), "", false, get_phrase("recent_alerts"));
        break;
    case "view_unread_messages":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        require_once(REALPATH . "library/functions/functions_getMessages.php");
        ajax_message(getMessages(), "", false, get_phrase("messages_title"));
        break;
    case "update_member_country":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        check_flood("update_member_country");
        globalize("post", array( "country" => "TRIM" ));
        if( !$country || !is_file(REALPATH . "/data/countryFlags/" . $country . ".png") ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( !profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "country" => $country )) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        exit();
    case "update_dst":
        if( !is_member_of("unregistered") ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_members", array( "dst" => array( "escape" => 0, "value" => "IF(dst > 0, 0, 1)" ) ), "memberid=" . $TSUE["TSUE_Member"]->info["memberid"]);
        }

        break;
    case "like_x_people_like_this":
        if( !has_permission("canview_like_list") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "content_id" => "INT", "content_type" => "TRIM" ));
        $available_content_types = array( "profile_post", "torrent", "thread_posts", "torrent_comments" );
        if( !in_array($content_type, $available_content_types) || !$content_id ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $PeopleList = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.like_memberid, l.like_date, m.membername, m.gender, g.groupname, g.groupstyle, b.memberid as isBanned, mp.custom_title FROM tsue_liked_content l INNER JOIN tsue_members m ON (l.like_memberid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) LEFT JOIN tsue_member_bans b ON(m.memberid=b.memberid) LEFT JOIN tsue_member_profile mp on(m.memberid=mp.memberid) WHERE l.content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " ORDER BY l.like_date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($PeopleList) ) 
        {
            $LikeList = "";
            for( $count = 0; $List = $TSUE["TSUE_Database"]->fetch_assoc($PeopleList); $count++ ) 
            {
                $groupname = getGroupname($List);
                $_avatar = get_member_avatar($List["like_memberid"], $List["gender"], "s");
                $_memberid = $List["like_memberid"];
                $_membername = getMembername($List["membername"], $List["groupstyle"]);
                $like_date = convert_relative_time($List["like_date"]);
                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                eval("\$ShowMemberName = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                eval("\$LikeList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_full_people_list") . "\";");
            }
            ajax_message($LikeList, "", false, get_phrase("like_title") . " (" . friendly_number_format($count) . ")");
        }
        else
        {
            ajax_message(get_phrase("an_error_hash_occurded"), "-ERROR-");
        }

        break;
    case "like_profile_comments":
    case "like_torrent":
    case "like_torrent_comments":
    case "like_thread_posts":
        if( !has_permission("canlike") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "content_id" => "INT", "content_type" => "TRIM", "content_memberid" => "INT", "extra" => "INT" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !$content_id || !$content_memberid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $content_memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("you_cant_like_your_own_content"), "-ERROR-");
        }

        $available_content_types = array( "profile_comments", "torrent", "torrent_comments", "thread_posts" );
        if( !in_array($content_type, $available_content_types) || !$content_id ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "profile_comments":
                $checkComment = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_comments WHERE content_type=\"profile_comments\" AND comment_id =" . $TSUE["TSUE_Database"]->escape($content_id) . " AND memberid=" . $TSUE["TSUE_Database"]->escape($content_memberid) . " AND content_id=" . $TSUE["TSUE_Database"]->escape($extra));
                if( !$checkComment ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                break;
            case "torrent":
                $checkTorrent = $TSUE["TSUE_Database"]->query_result("SELECT owner FROM tsue_torrents WHERE tid=" . $TSUE["TSUE_Database"]->escape($content_id) . " AND owner=" . $TSUE["TSUE_Database"]->escape($content_memberid));
                if( !$checkTorrent ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                break;
            case "torrent_comments":
                $checkComment = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_comments WHERE content_type=\"torrent_comments\" AND comment_id =" . $TSUE["TSUE_Database"]->escape($content_id) . " AND memberid=" . $TSUE["TSUE_Database"]->escape($content_memberid) . " AND content_id=" . $TSUE["TSUE_Database"]->escape($extra));
                if( !$checkComment ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                break;
            case "thread_posts":
                $checkPost = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_forums_posts WHERE postid = " . $TSUE["TSUE_Database"]->escape($content_id) . " AND threadid = " . $TSUE["TSUE_Database"]->escape($extra) . " AND memberid=" . $TSUE["TSUE_Database"]->escape($content_memberid));
                if( !$checkPost ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                break;
        }
        $useTextLink = ($content_type == "thread_posts" ? true : false);
        $points_likes_type = getSetting("global_settings", "points_likes_type", 2);
        $points_likes = getSetting("global_settings", "points_likes");
        require(REALPATH . "library/classes/class_likes.php");
        $Likes = new TSUE_Likes();
        if( $TSUE["TSUE_Database"]->delete("tsue_liked_content", "content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " AND like_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"])) ) 
        {
            switch( $points_likes_type ) 
            {
                case 3:
                    updateMemberPoints($points_likes, $TSUE["TSUE_Member"]->info["memberid"], false);
                    updateMemberPoints($points_likes, $content_memberid, false);
                    break;
                case 2:
                    updateMemberPoints($points_likes, $TSUE["TSUE_Member"]->info["memberid"], false);
                    break;
                case 1:
                    updateMemberPoints($points_likes, $content_memberid, false);
                    break;
            }
            $LikeLink = $Likes->likeButton($content_id, $content_memberid, $content_type, $extra, $useTextLink);
            $LikeList = $Likes->getContentLikes($content_id, $content_type);
        }
        else
        {
            switch( $points_likes_type ) 
            {
                case 3:
                    updateMemberPoints($points_likes, $TSUE["TSUE_Member"]->info["memberid"]);
                    updateMemberPoints($points_likes, $content_memberid);
                    break;
                case 2:
                    updateMemberPoints($points_likes, $TSUE["TSUE_Member"]->info["memberid"]);
                    break;
                case 1:
                    updateMemberPoints($points_likes, $content_memberid);
                    break;
            }
            $Likes->saveLikeAndAlertContentOwner($content_id, $content_memberid, $content_type, $extra);
            $LikeLink = $Likes->unlikeButton($content_id, $content_memberid, $content_type, $extra, $useTextLink);
            $LikeList = $Likes->getContentLikes($content_id, $content_type);
        }

        ajax_message($LikeLink . "|" . $LikeList . "|" . $Likes->TotalLikesCount, "-DONE-", false);
        break;
    case "follow_member":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !has_permission("canfollow") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "memberid" => "INT" ));
        if( !$memberid ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        if( $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membername, m.joindate, m.lastactivity, m.gender, m.visible, profile.date_of_birth, profile.custom_title, profile.country, p.allow_view_profile, p.show_your_age, g.groupname, s.location as lastViewedPage, s.http_referer, s.query_string FROM tsue_members m INNER JOIN tsue_member_profile profile USING(memberid) INNER JOIN tsue_member_privacy p USING(memberid) INNER JOIN tsue_membergroups g USING(membergroupid) LEFT JOIN tsue_session s USING(memberid) WHERE m.memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$Member ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }
        else
        {
            $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
            $PassiveUser = array( "memberid" => $Member["memberid"], "allow_view_profile" => $Member["allow_view_profile"] );
            if( !canViewProfile($ActiveUser, $PassiveUser) ) 
            {
                ajax_message(get_phrase("membercp_limited_view"), "-ERROR-");
            }

        }

        if( $TSUE["TSUE_Database"]->delete("tsue_member_follow", "memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND follow_memberid = " . $TSUE["TSUE_Database"]->escape($memberid)) ) 
        {
            ajax_message(get_phrase("button_follow"), "-DONE-", false);
        }
        else
        {
            $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "follow_memberid" => $memberid, "follow_date" => TIMENOW );
            if( $TSUE["TSUE_Database"]->replace("tsue_member_follow", $BuildQuery) ) 
            {
                alert_member($memberid, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "user", $TSUE["TSUE_Member"]->info["memberid"], "follow");
                ajax_message(get_phrase("button_unfollow"), "-DONE-", false);
            }

        }

        ajax_message(get_phrase("database_error"), "-ERROR-");
        break;
    default:
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
        break;
}


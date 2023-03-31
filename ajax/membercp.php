<?php 
define("SCRIPTNAME", "membercp.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
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
    case "membercp":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        switch( $TSUE["do"] ) 
        {
            case "personal_details":
                globalize("post", array( "gender" => "TRIM" ));
                $BuildQuery = array(  );
                $gender = (!in_array($gender, array( "f", "m" )) ? "" : $gender);
                $BuildQuery[] = "`gender` = " . $TSUE["TSUE_Database"]->escape($gender);
                if( has_permission("canchange_birthday") ) 
                {
                    globalize("post", array( "date_of_birth" => "TRIM" ));
                    if( !$date_of_birth || substr_count($date_of_birth, "/") != 2 || strlen($date_of_birth) != 10 ) 
                    {
                        ajax_message(get_phrase("invalid_date_of_birth"), "-ERROR-");
                    }
                    else
                    {
                        $nowYear = date("Y");
                        list($day, $month, $year) = @tsue_explode("/", $date_of_birth);
                        if( !checkdate($month, $day, $year) || $nowYear <= $year ) 
                        {
                            ajax_message(get_phrase("invalid_date_of_birth"), "-ERROR-");
                        }
                        else
                        {
                            if( $nowYear - $year <= 5 ) 
                            {
                                ajax_message(get_phrase("you_must_meet_certain_age_requirements", getSetting("global_settings", "website_title")), "-ERROR-");
                            }
                            else
                            {
                                $TSUE["TSUE_Database"]->query("UPDATE tsue_member_profile SET date_of_birth = " . $TSUE["TSUE_Database"]->escape($date_of_birth) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                            }

                        }

                    }

                }

                if( $BuildQuery && count($BuildQuery) ) 
                {
                    if( $TSUE["TSUE_Database"]->query("UPDATE tsue_members SET " . implode(",", $BuildQuery) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"])) ) 
                    {
                        ajax_message(get_phrase("message_saved"), "-DONE-", false);
                    }
                    else
                    {
                        ajax_message(get_phrase("database_error"), "-ERROR-");
                    }

                }

                break;
            case "contact_details":
                globalize("post", array( "your_email" => "TRIM", "membercp_your_existing_password" => "TRIM" ));
                if( $your_email != $TSUE["TSUE_Member"]->info["email"] ) 
                {
                    if( !$TSUE["TSUE_Member"]->info["password"] || !$membercp_your_existing_password || $TSUE["TSUE_Member"]->info["password"] != md5($membercp_your_existing_password) ) 
                    {
                        $Error[] = get_phrase("membercp_your_existing_password_is_not_correct");
                    }

                    if( !is_valid_email($your_email) ) 
                    {
                        $Error[] = get_phrase("invalid_email");
                    }
                    else
                    {
                        $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE email = " . $TSUE["TSUE_Database"]->escape($your_email));
                        if( $Member ) 
                        {
                            $Error[] = get_phrase("invalid_email_in_use");
                        }

                    }

                    if( isset($Error) ) 
                    {
                        ajax_message(implode("<br />", $Error), "-ERROR-");
                    }
                    else
                    {
                        $SendConfirmationEmail = true;
                        if( isset($SendConfirmationEmail) ) 
                        {
                            $Check = $TSUE["TSUE_Database"]->query_result("SELECT membergroupid FROM tsue_member_confirmation WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND type = 'confirm_account'");
                            if( $Check ) 
                            {
                                $OLDmembergroupid = $Check["membergroupid"];
                            }
                            else
                            {
                                $OLDmembergroupid = $TSUE["TSUE_Member"]->info["membergroupid"];
                            }

                            $hash = generate_random_string(16);
                            $confirm_link = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . "\">" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . "</a>";
                            $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "membergroupid" => $OLDmembergroupid, "type" => "confirm_account", "hash" => $hash, "date" => TIMENOW );
                            if( !$TSUE["TSUE_Database"]->replace("tsue_member_confirmation", $BuildQuery) ) 
                            {
                                ajax_message(get_phrase("database_error"), "-ERROR-");
                            }
                            else
                            {
                                $BuildQuery = array( "email" => $your_email, "membergroupid" => is_member_of("awaitingemailconfirmation", true) );
                                $TSUE["TSUE_Database"]->update("tsue_members", $BuildQuery, "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                            }

                            $subject = get_phrase("signup_finished_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                            $body = get_phrase("signup_finished_email_body", $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $confirm_link);
                            if( sent_mail($your_email, $subject, $body, $TSUE["TSUE_Member"]->info["membername"]) ) 
                            {
                                ajax_message(get_phrase("signup_finished_email"), "-DONE-");
                            }
                            else
                            {
                                ajax_message(get_phrase("unable_to_send_email"), "-ERROR-");
                            }

                        }

                    }

                }

                ajax_message(get_phrase("message_saved"), "-DONE-", false);
                break;
            case "preferences":
                globalize("post", array( "themeid" => "INT", "languageid" => "INT", "timezone" => "TRIM", "torrentStyle" => "INT", "cids" => "TRIM", "accountParked" => "INT" ));
                $BuildQuery = $profileQuery = array(  );
                $BuildQuery[] = "accountParked = " . (($accountParked == 1 ? 1 : 0));
                if( $themeid ) 
                {
                    $Theme = $TSUE["TSUE_Database"]->query_result("SELECT themeid, viewpermissions FROM tsue_themes WHERE themeid = " . $TSUE["TSUE_Database"]->escape($themeid) . " AND active = 1");
                    if( $Theme ) 
                    {
                        if( $Theme["viewpermissions"] ) 
                        {
                            $Theme["viewpermissions"] = unserialize($Theme["viewpermissions"]);
                            if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Theme["viewpermissions"]) ) 
                            {
                                $skipThemeQuery = true;
                            }

                        }

                        if( !isset($skipThemeQuery) ) 
                        {
                            $BuildQuery[] = "`themeid` = " . $TSUE["TSUE_Database"]->escape($themeid);
                        }

                    }

                }

                if( $languageid ) 
                {
                    $Language = $TSUE["TSUE_Database"]->query_result("SELECT languageid FROM tsue_languages WHERE languageid = " . $TSUE["TSUE_Database"]->escape($languageid) . " AND active = 1");
                    if( $Language ) 
                    {
                        $BuildQuery[] = "`languageid` = " . $TSUE["TSUE_Database"]->escape($languageid);
                    }

                }

                $AvailableTimeZones = fetch_timezones();
                if( isset($AvailableTimeZones[$timezone]) && !empty($AvailableTimeZones[$timezone]) ) 
                {
                    $BuildQuery[] = "`timezone` = " . $TSUE["TSUE_Database"]->escape($timezone);
                }

                if( !in_array($torrentStyle, array( 1, 2 )) ) 
                {
                    $torrentStyle = 1;
                }

                $profileQuery[] = "torrentStyle = " . $TSUE["TSUE_Database"]->escape($torrentStyle);
                if( $cids ) 
                {
                    $tmpCids = array(  );
                    $cids = tsue_explode(",", $cids);
                    if( $cids ) 
                    {
                        foreach( $cids as $cid ) 
                        {
                            $cid = intval($cid);
                            if( $cid ) 
                            {
                                $tmpCids[] = $cid;
                            }

                        }
                    }

                    if( $tmpCids ) 
                    {
                        $cids = implode(",", $tmpCids);
                        unset($tmpCids);
                    }
                    else
                    {
                        $cids = "";
                    }

                }

                $profileQuery[] = "defaultTorrentCategories = " . $TSUE["TSUE_Database"]->escape($cids);
                if( !empty($profileQuery) ) 
                {
                    $TSUE["TSUE_Database"]->query("UPDATE tsue_member_profile SET " . implode(",", $profileQuery) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                }

                if( !empty($BuildQuery) ) 
                {
                    $TSUE["TSUE_Database"]->query("UPDATE tsue_members SET " . implode(",", $BuildQuery) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                }

                ajax_message(get_phrase("message_saved"), "-DONE-", false);
                break;
            case "privacy":
                globalize("post", array( "visible" => "INT", "receive_admin_email" => "INT", "receive_pm_email" => "INT", "show_your_age" => "INT", "allow_view_profile" => "TRIM" ));
                $allow_view_profile = (!in_array($allow_view_profile, array( "everyone", "members", "followed", "none" )) ? "members" : $allow_view_profile);
                $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "allow_view_profile" => $allow_view_profile, "receive_admin_email" => $receive_admin_email, "receive_pm_email" => (!has_permission("canreceive_pm_email") ? 0 : $receive_pm_email), "show_your_age" => $show_your_age );
                $TSUE["TSUE_Database"]->replace("tsue_member_privacy", $BuildQuery);
                $BuildQuery = array(  );
                $BuildQuery[] = "`visible` = " . $TSUE["TSUE_Database"]->escape($visible);
                if( $BuildQuery && count($BuildQuery) ) 
                {
                    if( $TSUE["TSUE_Database"]->query("UPDATE tsue_members SET " . implode(",", $BuildQuery) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"])) ) 
                    {
                        ajax_message(get_phrase("message_saved"), "-DONE-", false);
                    }
                    else
                    {
                        ajax_message(get_phrase("database_error"), "-ERROR-");
                    }

                }

                break;
            case "password":
                globalize("post", array( "membercp_your_existing_password" => "TRIM", "membercp_new_password" => "TRIM", "membercp_confirm_new_password" => "TRIM" ));
                if( !$TSUE["TSUE_Member"]->info["password"] || !$membercp_your_existing_password || $TSUE["TSUE_Member"]->info["password"] != md5($membercp_your_existing_password) ) 
                {
                    $Error[] = get_phrase("membercp_your_existing_password_is_not_correct");
                }
                else
                {
                    if( !$membercp_new_password || !$membercp_confirm_new_password || $TSUE["TSUE_Member"]->info["password"] == md5($membercp_new_password) ) 
                    {
                        $Error[] = get_phrase("invalid_memberpassword");
                    }
                    else
                    {
                        if( $membercp_new_password != $membercp_confirm_new_password ) 
                        {
                            $Error[] = get_phrase("invalid_memberpassword_mismatch");
                        }
                        else
                        {
                            if( strlen($membercp_new_password) < $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"] ) 
                            {
                                $Error[] = get_phrase("invalid_memberpassword_min_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"]);
                            }
                            else
                            {
                                if( $membercp_new_password == $TSUE["TSUE_Member"]->info["membername"] ) 
                                {
                                    $Error[] = get_phrase("invalid_memberpassword_same_as_name");
                                }

                            }

                        }

                    }

                }

                if( isset($Error) ) 
                {
                    ajax_message(implode("<br />", $Error), "-ERROR-");
                }
                else
                {
                    $buildQuery = array( "password" => md5($membercp_new_password), "password_date" => TIMENOW );
                    if( $TSUE["TSUE_Database"]->update("tsue_members", $buildQuery, "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"])) ) 
                    {
                        setLoginCookie($TSUE["TSUE_Member"]->remember_me, $TSUE["TSUE_Member"]->info["memberid"], $membercp_new_password);
                        ajax_message(get_phrase("message_saved"), "-DONE-", false);
                    }
                    else
                    {
                        ajax_message(get_phrase("database_error"), "-ERROR-");
                    }

                }

                break;
            case "signature":
                if( !has_permission("canpost_signature") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                globalize("post", array( "signature" => "TRIM" ));
                if( profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "signature" => $signature )) ) 
                {
                    ajax_message(get_phrase("message_saved"), "-DONE-", false);
                }
                else
                {
                    ajax_message(get_phrase("database_error"), "-ERROR-");
                }

                break;
            case "invite":
                if( !has_permission("cansend_invite") ) 
                {
                    ajax_message(get_phrase("invite_no_permission"), "-ERROR-");
                }

                if( $TSUE["TSUE_Member"]->info["invites_left"] <= 0 ) 
                {
                    ajax_message(get_phrase("invite_no_limit"), "-ERROR-");
                }

                check_flood("send_invite");
                globalize("post", array( "invite_friend_name" => "STRIP", "invite_friend_email" => "TRIM", "invite_friend_message" => "STRIP" ));
                if( !$invite_friend_name || !is_valid_email($invite_friend_email) ) 
                {
                    ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                }

                $searchEmail = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE email = " . $TSUE["TSUE_Database"]->escape($invite_friend_email));
                if( $searchEmail ) 
                {
                    ajax_message(get_phrase("invalid_email_in_use"), "-ERROR-");
                }

                $searchEmail = $TSUE["TSUE_Database"]->query_result("SELECT hash FROM tsue_invites WHERE email = " . $TSUE["TSUE_Database"]->escape($invite_friend_email));
                if( $searchEmail ) 
                {
                    ajax_message(get_phrase("invalid_email_in_use"), "-ERROR-");
                }

                $inviteHash = generate_random_string(32);
                $BuildQuery = array( "hash" => $inviteHash, "sender_memberid" => $TSUE["TSUE_Member"]->info["memberid"], "send_date" => TIMENOW, "email" => $invite_friend_email, "name" => $invite_friend_name, "status" => "pending" );
                if( $TSUE["TSUE_Database"]->insert("tsue_invites", $BuildQuery) ) 
                {
                    $inviteLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=signup&pid=16&hash=" . $inviteHash;
                    profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "invites_left" => array( "escape" => 0, "value" => "IF(invites_left > 0, invites_left-1, 0)" ) ));
                }

                $subject = get_phrase("invite_email_title");
                $body = get_phrase("invite_email_body", $invite_friend_name, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], html_clean($invite_friend_message), $inviteLink);
                if( sent_mail($invite_friend_email, $subject, $body, $invite_friend_name) ) 
                {
                    require(REALPATH . "/library/functions/functions_getInvites.php");
                    $membercp_invited_friends_table = prepareInviteList($TSUE["TSUE_Member"]->info["memberid"]);
                    ajax_message(show_done(get_phrase("invite_sent"), "", false) . $membercp_invited_friends_table);
                }
                else
                {
                    ajax_message(get_phrase("unable_to_send_email"), "-ERROR-");
                }

                break;
            case "delete_invite":
                if( !has_permission("cansend_invite") ) 
                {
                    ajax_message(get_phrase("invite_no_permission"), "-ERROR-");
                }

                check_flood("delete_invite");
                globalize("post", array( "hash" => "TRIM" ));
                if( !$hash ) 
                {
                    ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                }

                if( $TSUE["TSUE_Database"]->delete("tsue_invites", "hash = " . $TSUE["TSUE_Database"]->escape($hash) . " AND sender_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND status = 'pending'") ) 
                {
                    profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "invites_left" => array( "escape" => 0, "value" => "invites_left+1" ) ));
                    require_once(REALPATH . "/library/functions/functions_getInvites.php");
                    $membercp_invited_friends_table = prepareInviteList($TSUE["TSUE_Member"]->info["memberid"]);
                    ajax_message(show_done(get_phrase("invite_your_invite_deleted"), "", false) . $membercp_invited_friends_table);
                }
                else
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                break;
            case "performance":
                globalize("post", array( "shoutbox_enabled" => "INT", "irtm_enabled" => "INT", "alerts_enabled" => "INT" ));
                $cpOptions = array( "shoutbox_enabled" => $shoutbox_enabled, "irtm_enabled" => $irtm_enabled, "alerts_enabled" => $alerts_enabled );
                $buildQuery = array( "cpOptions" => serialize($cpOptions) );
                if( $TSUE["TSUE_Database"]->update("tsue_member_profile", $buildQuery, "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"])) ) 
                {
                    ajax_message(get_phrase("message_saved"), "-DONE-", false);
                }
                else
                {
                    ajax_message(get_phrase("database_error"), "-ERROR-");
                }

                break;
            case "delete_subscribed_thread":
                globalize("post", array( "threadid" => "INT" ));
                if( !$threadid ) 
                {
                    ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                }

                $TSUE["TSUE_Database"]->delete("tsue_forums_thread_subscribe", "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND threadid=" . $TSUE["TSUE_Database"]->escape($threadid));
                break;
            case "open_port_check":
                globalize("post", array( "port" => "INT" ));
                if( !$port ) 
                {
                    ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                }
                else
                {
function emptyErrorHandler($errno, $errstr, $errfile, $errline)
{
}

                    @set_error_handler("emptyErrorHandler");
                    $fp = @fsockopen(MEMBER_IP, $port, $errno, $errstr, 10);
                    if( !$fp ) 
                    {
                        ajax_message(get_phrase("port_x_is_closed_on_y", $port, MEMBER_IP), "-ERROR-");
                    }
                    else
                    {
                        ajax_message(get_phrase("port_x_is_open_on_y", $port, MEMBER_IP), "-DONE-");
                        @fclose($fp);
                    }

                }

        }
}


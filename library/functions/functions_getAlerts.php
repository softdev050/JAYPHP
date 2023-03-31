<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function showMemberAlerts($expirity = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        return _aError(get_phrase("login_required"));
    }

    if( 0 < $TSUE["TSUE_Member"]->info["unread_alerts"] ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_members", array( "unread_alerts" => 0 ), "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    }

    $ShowAlerts = "";
    if( $expirity ) 
    {
        $DateCut = TIMENOW - $TSUE["TSUE_Settings"]->settings["global_settings"]["alerts_popup_expiry_hours"] * 3600;
    }

    $Alerts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE a.*, m.gender, g.groupstyle FROM tsue_member_alerts a LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE a.alerted_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . (($expirity ? " AND (a.event_date > " . $DateCut . " OR a.read_date = 0)" : "")) . " ORDER BY a.event_date DESC");
    if( $TSUE["TSUE_Database"]->num_rows($Alerts) ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_member_alerts", array( "read_date" => TIMENOW ), "alerted_memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
        while( $Alert = $TSUE["TSUE_Database"]->fetch_assoc($Alerts) ) 
        {
            $event_date = convert_relative_time($Alert["event_date"]);
            $_memberid = $Alert["memberid"];
            $_membername = getMembername($Alert["membername"], $Alert["groupstyle"]);
            $_alt = "";
            $_avatar = get_member_avatar($Alert["memberid"], $Alert["gender"], "s");
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            switch( $Alert["content_type"] ) 
            {
                case "profile_comments":
                    switch( $Alert["action"] ) 
                    {
                        case "like_profile_comments":
                        case "like_post":
                            $ShowMessage = get_phrase("alerts_liked_your_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $Alert["memberid"] . "#profile_posts");
                            break;
                        case "new_comment":
                            $ShowMessage = get_phrase("alerts_posted_profile_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $Alert["alerted_memberid"] . "#profile_posts");
                            break;
                        case "new_reply":
                            $ShowMessage = get_phrase("alerts_posted_comment_reply", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $Alert["content_id"] . "#profile_posts");
                    }
                    break;
                case "thread_posts":
                    switch( $Alert["action"] ) 
                    {
                        case "subscribed_threads_new_post":
                            $Thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid,title FROM tsue_forums_threads WHERE threadid=" . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Thread ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $Thread["title"] = strip_tags($Thread["title"]);
                                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($Thread["title"]) ) 
                                {
                                    $Thread["title"] = substr($Thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                                }

                                $ShowMessage = get_phrase("subscribed_threads_alert_message", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=11&fid=" . $Thread["forumid"] . "&tid=" . $Alert["content_id"] . "&postid=" . $Alert["extra"] . "#show_post_" . $Alert["extra"], $Thread["title"]);
                            }

                            break;
                        case "new_reply":
                            $Thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid,title FROM tsue_forums_threads WHERE threadid=" . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Thread ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $Thread["title"] = strip_tags($Thread["title"]);
                                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($Thread["title"]) ) 
                                {
                                    $Thread["title"] = substr($Thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                                }

                                $ShowMessage = get_phrase("alerts_posted_new_reply", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=11&fid=" . $Thread["forumid"] . "&tid=" . $Alert["content_id"] . "&postid=" . $Alert["extra"] . "#show_post_" . $Alert["extra"], $Thread["title"]);
                            }

                            break;
                        case "like_thread_posts":
                            $Thread = $TSUE["TSUE_Database"]->query_result("SELECT threadid,forumid,title FROM tsue_forums_threads WHERE threadid=" . $TSUE["TSUE_Database"]->escape($Alert["extra"]));
                            if( !$Thread ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $Thread["title"] = strip_tags($Thread["title"]);
                                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($Thread["title"]) ) 
                                {
                                    $Thread["title"] = substr($Thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                                }

                                $ShowMessage = get_phrase("alerts_liked_your_post", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=11&fid=" . $Thread["forumid"] . "&tid=" . $Thread["threadid"] . "&postid=" . $Alert["content_id"] . "#show_post_" . $Alert["content_id"], $Thread["title"]);
                            }

                            break;
                        case "reply_quoted":
                            $Thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid,title FROM tsue_forums_threads WHERE threadid=" . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Thread ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $Thread["title"] = strip_tags($Thread["title"]);
                                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($Thread["title"]) ) 
                                {
                                    $Thread["title"] = substr($Thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                                }

                                $ShowMessage = get_phrase("x_quoted_your_post", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=11&fid=" . $Thread["forumid"] . "&tid=" . $Alert["content_id"] . "&postid=" . $Alert["extra"] . "#show_post_" . $Alert["extra"], $Thread["title"]);
                            }

                    }
                    break;
                case "torrent":
                case "torrent_comments":
                case "reseed":
                    switch( $Alert["action"] ) 
                    {
                        case "t_upload_awaiting_moderation":
                            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Torrent ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $ShowMessage = get_phrase("alert_torrent_x_awaiting_moderation", strip_tags($Torrent["name"]), $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"]);
                            }

                            break;
                        case "torrent_approved":
                            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Torrent ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $ShowMessage = get_phrase("your_torrent_has_been_approved_msg", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"], strip_tags($Torrent["name"]), $member_info_link);
                            }

                            break;
                        case "request":
                            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( !$Torrent ) 
                            {
                                $ShowMessage = get_phrase("message_content_error");
                            }
                            else
                            {
                                $ShowMessage = get_phrase("alerts_x_wants_reseed_request", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"], strip_tags($Torrent["name"]), $member_info_link);
                            }

                            break;
                        case "like_torrent":
                            $ShowMessage = get_phrase("alerts_liked_your_torrent", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"]);
                            break;
                        case "like_torrent_comments":
                            $findComment = $TSUE["TSUE_Database"]->query_result("SELECT content_id FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            if( $findComment ) 
                            {
                                $Alert["content_id"] = $findComment["content_id"];
                            }

                            $ShowMessage = get_phrase("alerts_liked_your_torrent_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"] . "#torrent_comments");
                            break;
                        case "new_comment":
                            $ShowMessage = get_phrase("alerts_posted_torrent_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"] . "#torrent_comments");
                            break;
                        case "new_reply":
                            $ShowMessage = get_phrase("alerts_posted_comment_reply", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"] . "#torrent_comments");
                    }
                    break;
                case "user":
                    switch( $Alert["action"] ) 
                    {
                        case "follow":
                            $ShowMessage = get_phrase("alerts_x_is_following_you", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $Alert["alerted_memberid"]);
                    }
                    break;
                case "warns":
                    switch( $Alert["action"] ) 
                    {
                        case "warn_member":
                            $ShowMessage = get_phrase("warned_you_have_been_warned", $member_info_link);
                            break;
                        case "warn_lifted":
                            $ShowMessage = get_phrase("warned_your_warn_lifted", $member_info_link);
                    }
                    break;
                case "market":
                    switch( $Alert["action"] ) 
                    {
                        case "gift":
                            $ShowMessage = get_phrase("market_gift_alert_member", friendly_size($Alert["extra"]), $member_info_link);
                    }
                    break;
                case "report":
                    switch( $Alert["action"] ) 
                    {
                        case "new_report":
                            $ShowMessage = get_phrase("report_new_report", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=reports&pid=25&report_id=" . $Alert["content_id"], $member_info_link);
                            break;
                        case "new_comment":
                            $ShowMessage = get_phrase("report_new_comment", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=reports&pid=25&report_id=" . $Alert["content_id"], $member_info_link);
                    }
                    break;
                case "mutes":
                    switch( $Alert["action"] ) 
                    {
                        case "mute_member":
                            $ShowMessage = get_phrase("you_have_been_muted", $member_info_link);
                            break;
                        case "mute_lifted":
                            $ShowMessage = get_phrase("your_mute_has_been_lifted_by_x", $member_info_link);
                    }
                    break;
                case "promotions":
                    switch( $Alert["action"] ) 
                    {
                        case "promoted":
                            $ShowMessage = get_phrase("you_have_been_auto_promoted", strip_tags($TSUE["TSUE_Member"]->info["membername"]), getMembername($TSUE["TSUE_Member"]->info["groupname"], $TSUE["TSUE_Member"]->info["groupstyle"]));
                            break;
                        case "demoted":
                            $ShowMessage = get_phrase("you_have_been_auto_demoted", strip_tags($TSUE["TSUE_Member"]->info["membername"]), getMembername($TSUE["TSUE_Member"]->info["groupname"], $TSUE["TSUE_Member"]->info["groupstyle"]));
                            break;
                        case "uploader_demoted":
                            $TSUE["TSUE_Settings"]->loadSettings("uploader_inactivity");
                            $ShowMessage = get_phrase("uploader_you_have_been_auto_demoted", intval(getSetting("uploader_inactivity", "criterias_torrents")), intval(getSetting("uploader_inactivity", "criterias_days")));
                            break;
                        case "received_automatic_invite":
                            $ShowMessage = get_phrase("x_automatic_invites_received");
                    }
                    break;
                case "applications":
                    switch( $Alert["action"] ) 
                    {
                        case "new-uploader-form":
                            $ShowMessage = get_phrase("uploader_application_new_form", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=manageapplications&amp;pid=100");
                            break;
                        case "new-uploader-comment":
                            $ShowMessage = get_phrase("uploader_application_new_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=manageapplications&amp;pid=100&amp;memberid=" . $Alert["content_id"]);
                            break;
                        case "state-updated":
                            switch( $Alert["content_id"] ) 
                            {
                                case 1:
                                    $phrase = "pending";
                                    break;
                                case 2:
                                    $phrase = "approved";
                                    break;
                                case 3:
                                    $phrase = "rejected";
                            }
                            $ShowMessage = get_phrase("your_uploader_application_has_bee_updated", get_phrase("application_state_" . $phrase), $member_info_link);
                    }
                    break;
                case "requests":
                    switch( $Alert["action"] ) 
                    {
                        case "filled":
                            $Request = $TSUE["TSUE_Database"]->query_result("SELECT title FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($Alert["extra"]));
                            $ShowMessage = get_phrase("your_request_has_been_filled", substr(strip_tags($Request["title"]), 0, 85), $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Alert["content_id"]);
                            break;
                        case "new":
                            $Request = $TSUE["TSUE_Database"]->query_result("SELECT title FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($Alert["content_id"]));
                            $ShowMessage = get_phrase("a_new_request_posted", substr(strip_tags($Request["title"]), 0, 85), $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&pid=101");
                    }
                    break;
                case "file_comments":
                    switch( $Alert["action"] ) 
                    {
                        case "new_comment":
                            $ShowMessage = get_phrase("alerts_posted_file_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&pid=300&action=viewFile&did=" . $Alert["content_id"] . "#comments");
                            break;
                        case "new_reply":
                            $ShowMessage = get_phrase("alerts_posted_file_comment_reply", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&pid=300&action=viewFile&did=" . $Alert["content_id"] . "#comments");
                    }
                    break;
                case "paid_subscriptions":
                    switch( $Alert["action"] ) 
                    {
                        case "expired":
                            $ShowMessage = get_phrase("paid_subscription_expired_message", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
                    }
                    break;
                case "auto_alerts":
                case "contains":
                    $aa_words_strings = preg_split("/\\r?\\n/", trim(getSetting("auto_alert", "words")), -1, PREG_SPLIT_NO_EMPTY);
                    $ShowMessage = get_phrase("auto_alert_contains", implode(", ", $aa_words_strings), $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/admincp/?action=Dashboard&do=Read PM&message_id=" . $Alert["content_id"]);
                    break;
            }
            eval("\$ShowAlerts .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("alerts_list") . "\";");
        }
        return $ShowAlerts;
    }

    return _aError(get_phrase("alerts_no_new"));
}

function _aError($msg)
{
    if( defined("IS_AJAX") ) 
    {
        return ajax_message($msg, "-ERROR-");
    }

    return show_error($msg, get_phrase("recent_alerts"), 0);
}



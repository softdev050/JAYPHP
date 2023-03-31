<?php 
define("SCRIPTNAME", "comments.php");
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
    case "post_comment":
        globalize("post", array( "message" => "TRIM", "content_type" => "TRIM", "content_id" => "INT", "comment_id" => "INT" ));
        if( !has_permission("canpost_comments") || isMuted($TSUE["TSUE_Member"]->info["muted"], "comments") || !$content_type || !$content_id ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name,cid,owner FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($content_id));
                if( !$Torrent ) 
                {
                    ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
                }

                $Content_Owner = $Torrent["owner"];
                break;
            case "profile_comments":
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

                $Content_Owner = $content_id;
                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $searchReport = $TSUE["TSUE_Database"]->query_result("SELECT reported_by_memberid FROM tsue_reports WHERE report_id = " . $TSUE["TSUE_Database"]->escape($content_id));
                if( !$searchReport ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                $Content_Owner = $searchReport["reported_by_memberid"];
                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $searchApplication = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_uploader_applications WHERE memberid = " . $TSUE["TSUE_Database"]->escape($content_id));
                if( !$searchApplication ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                $Content_Owner = $searchApplication["memberid"];
                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                $search = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_downloads WHERE did = " . $TSUE["TSUE_Database"]->escape($content_id));
                if( !$search ) 
                {
                    ajax_message(get_phrase("message_content_error"), "-ERROR-");
                }

                $Content_Owner = $search["memberid"];
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        $strlenOriginalText = strlenOriginalText($message);
        if( $strlenOriginalText < $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_min_char_length"] ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_max_char_length"] < $strlenOriginalText ) 
        {
            ajax_message(get_phrase("message_length_error", $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_max_char_length"]), "-ERROR-");
        }

        if( $TSUE["do"] == "reply" ) 
        {
            if( !$comment_id ) 
            {
                ajax_message(get_phrase("permission_denied"), "-ERROR-");
            }

            $Comment = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
            if( !$Comment ) 
            {
                ajax_message(get_phrase("message_content_error"), "-ERROR-");
            }

            $comment_owner = $Comment["memberid"];
            $BuildQuery = array( "comment_id" => $comment_id, "content_type" => $content_type, "content_id" => $content_id, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "membername" => $TSUE["TSUE_Member"]->info["membername"], "post_date" => TIMENOW, "message" => $message );
            check_flood("post_comment");
            if( $TSUE["TSUE_Database"]->insert("tsue_comments_replies", $BuildQuery) ) 
            {
                $reply_id = $TSUE["TSUE_Database"]->insert_id();
                if( $content_type == "report_comments" ) 
                {
                    $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                    if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                    {
                        while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                        {
                            if( $TSUE["TSUE_Member"]->info["memberid"] != $moderator["memberid"] ) 
                            {
                                alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "report", $content_id, "new_comment");
                            }

                        }
                    }

                }
                else
                {
                    if( $content_type == "application_comments" ) 
                    {
                        $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                        if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                        {
                            while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                            {
                                if( $TSUE["TSUE_Member"]->info["memberid"] != $moderator["memberid"] ) 
                                {
                                    alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "applications", $content_id, "new-uploader-comment");
                                }

                            }
                        }

                    }
                    else
                    {
                        if( $content_type == "staff_messages_comments" ) 
                        {
                            $staffMessage = $TSUE["TSUE_Database"]->query_result("SELECT sm.message, sm.memberid, m.membername FROM tsue_staff_messages sm LEFT JOIN tsue_members m USING(memberid) WHERE sm.mid = " . $TSUE["TSUE_Database"]->escape($content_id));
                            if( $staffMessage && $staffMessage["memberid"] ) 
                            {
                                $Subject = get_phrase("you_have_a_new_message_to_your_staff_message");
                                $Message = "[QUOTE]" . nl2br($staffMessage["message"]) . "[/QUOTE]" . $message;
                                sendPM($Subject, $TSUE["TSUE_Member"]->info["memberid"], $staffMessage["memberid"], $Message);
                            }

                        }
                        else
                        {
                            updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_post_comment"], $TSUE["TSUE_Member"]->info["memberid"]);
                            if( $TSUE["TSUE_Member"]->info["memberid"] != $Content_Owner ) 
                            {
                                alert_member($Content_Owner, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $content_type, $content_id, "new_comment");
                            }

                            if( $TSUE["TSUE_Member"]->info["memberid"] != $comment_owner ) 
                            {
                                alert_member($comment_owner, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $content_type, $content_id, "new_reply");
                            }

                            if( $content_type == "torrent_comments" ) 
                            {
                                shoutboxAnnouncement(array( "new_torrent_comment", $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"], $Torrent["cid"], $content_id, strip_tags($Torrent["name"]) ));
                            }

                        }

                    }

                }

                require_once(REALPATH . "/library/functions/functions_getComments.php");
                $Reply = getReply($reply_id, $content_type, $content_id);
                ajax_message($Reply);
            }
            else
            {
                ajax_message(get_phrase("database_error"), "-ERROR-");
            }

        }

        $BuildQuery = array( "content_type" => $content_type, "content_id" => $content_id, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "membername" => $TSUE["TSUE_Member"]->info["membername"], "post_date" => TIMENOW, "message" => $message );
        check_flood("post_comment");
        if( $TSUE["TSUE_Database"]->insert("tsue_comments", $BuildQuery) ) 
        {
            $comment_id = $TSUE["TSUE_Database"]->insert_id();
            if( $content_type == "report_comments" ) 
            {
                $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                {
                    while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                    {
                        if( $TSUE["TSUE_Member"]->info["memberid"] != $moderator["memberid"] ) 
                        {
                            alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "report", $content_id, "new_comment");
                        }

                    }
                }

            }
            else
            {
                if( $content_type == "application_comments" ) 
                {
                    $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                    if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                    {
                        while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                        {
                            if( $TSUE["TSUE_Member"]->info["memberid"] != $moderator["memberid"] ) 
                            {
                                alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "applications", $content_id, "new-uploader-comment");
                            }

                        }
                    }

                }
                else
                {
                    if( $content_type == "staff_messages_comments" ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_staff_messages", array( "rid" => $comment_id ), "mid = " . $TSUE["TSUE_Database"]->escape($content_id));
                        $staffMessage = $TSUE["TSUE_Database"]->query_result("SELECT sm.message, sm.memberid, m.membername FROM tsue_staff_messages sm LEFT JOIN tsue_members m USING(memberid) WHERE sm.mid = " . $TSUE["TSUE_Database"]->escape($content_id));
                        if( $staffMessage && $staffMessage["memberid"] ) 
                        {
                            $Subject = get_phrase("you_have_a_new_message_to_your_staff_message");
                            $Message = "[QUOTE]" . nl2br($staffMessage["message"]) . "[/QUOTE]" . $message;
                            sendPM($Subject, $TSUE["TSUE_Member"]->info["memberid"], $staffMessage["memberid"], $Message);
                        }

                    }
                    else
                    {
                        updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_post_comment"], $TSUE["TSUE_Member"]->info["memberid"]);
                        if( $TSUE["TSUE_Member"]->info["memberid"] != $Content_Owner ) 
                        {
                            alert_member($Content_Owner, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $content_type, $content_id, "new_comment");
                        }

                        if( $content_type == "torrent_comments" ) 
                        {
                            shoutboxAnnouncement(array( "new_torrent_comment", $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"], $Torrent["cid"], $content_id, strip_tags($Torrent["name"]) ));
                        }

                    }

                }

            }

            require_once(REALPATH . "/library/functions/functions_getComments.php");
            $Comments = getComments($content_type, $content_id, $comment_id);
            ajax_message($Comments, "-DONE-", false);
        }

        ajax_message(get_phrase("database_error"), "-ERROR-");
        break;
    case "delete_comment":
        globalize("post", array( "comment_id" => "INT", "reply_id" => "INT", "content_type" => "TRIM" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !$comment_id && !$reply_id ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "profile_comments":
                if( !has_permission("canview_member_profiles") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        if( $comment_id ) 
        {
            $Comment = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
            if( !$Comment ) 
            {
                ajax_message(get_phrase("message_content_error"), "-ERROR-");
            }

            if( has_permission("candelete_own_comments") && $Comment["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_comments") ) 
            {
                check_flood("delete_comment");
                $TSUE["TSUE_Database"]->delete("tsue_comments", "comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
                $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
                $TSUE["TSUE_Database"]->delete("tsue_liked_content", "content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND content_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
                if( !($content_type == "report_comments" || $content_type == "application_comments" || $content_type == "staff_messages_comments") ) 
                {
                    updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_post_comment"], $Comment["memberid"], false);
                }

            }
            else
            {
                ajax_message(get_phrase("permission_denied"), "-ERROR-");
            }

        }
        else
        {
            $Reply = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_comments_replies WHERE reply_id = " . $TSUE["TSUE_Database"]->escape($reply_id));
            if( !$Reply ) 
            {
                ajax_message(get_phrase("message_content_error"), "-ERROR-");
            }

            if( has_permission("candelete_own_comments") && $Reply["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_comments") ) 
            {
                check_flood("delete_comment");
                $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "reply_id = " . $TSUE["TSUE_Database"]->escape($reply_id));
                if( !($content_type == "report_comments" || $content_type == "application_comments" || $content_type == "staff_messages_comments") ) 
                {
                    updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_post_comment"], $Reply["memberid"], false);
                }

            }
            else
            {
                ajax_message(get_phrase("permission_denied"), "-ERROR-");
            }

        }

        break;
    case "comments_show_more":
        globalize("post", array( "last_comment_id" => "INT", "content_type" => "TRIM", "content_id" => "INT" ));
        if( !$last_comment_id || !$content_type || !$content_id ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "profile_comments":
                if( !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $content_id ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        require_once(REALPATH . "/library/functions/functions_getComments.php");
        $comments_show_more = comments_show_more($last_comment_id, $content_type, $content_id);
        ajax_message($comments_show_more);
        break;
    case "get_editor_for_comment_reply":
        globalize("post", array( "content_type" => "TRIM", "comment_id" => "INT" ));
        if( !has_permission("canpost_comments") || !$content_type || !$comment_id ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "profile_comments":
                if( !has_permission("canview_member_profiles") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        $post_id = $comment_id;
        $message = "";
        $upload_button = "";
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_ajax_editor") . "\";");
        ajax_message($Output, false, "", get_phrase("message_reply"));
        break;
    case "get_comment_for_edit":
        globalize("post", array( "content_type" => "TRIM", "comment_id" => "INT" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !has_permission("canpost_comments") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Comment = $TSUE["TSUE_Database"]->query_result("SELECT memberid, message FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
        if( !$Comment ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( has_permission("canedit_own_comments") && $Comment["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_comments") ) 
        {
            $post_id = $comment_id;
        }
        else
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "profile_comments":
                if( !has_permission("canview_member_profiles") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        $message = html_clean($Comment["message"]);
        $upload_button = "";
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_ajax_editor") . "\";");
        ajax_message($Output, false, "", get_phrase("message_edit"));
        break;
    case "update_comment":
        globalize("post", array( "content_type" => "TRIM", "comment_id" => "INT", "message" => "TRIM" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !has_permission("canpost_comments") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Available_Content_Types = array( "torrent_comments", "profile_comments", "report_comments", "application_comments", "file_comments", "staff_messages_comments" );
        if( !in_array($content_type, $Available_Content_Types) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Comment = $TSUE["TSUE_Database"]->query_result("SELECT memberid, message FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
        if( !$Comment ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( !(has_permission("canedit_own_comments") && $Comment["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_comments")) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $strlenOriginalText = strlenOriginalText($message);
        if( $strlenOriginalText < $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_min_char_length"] ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_max_char_length"] < $strlenOriginalText ) 
        {
            ajax_message(get_phrase("message_length_error", $TSUE["TSUE_Settings"]->settings["global_settings"]["comment_post_max_char_length"]), "-ERROR-");
        }

        switch( $content_type ) 
        {
            case "torrent_comments":
                if( !has_permission("canview_torrents") || !has_permission("canview_torrent_details") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "profile_comments":
                if( !has_permission("canview_member_profiles") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "report_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_reports");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "application_comments":
                $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
                if( !$searchMembergroups || !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $searchMembergroups) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
            case "file_comments":
                require_once(REALPATH . "library/functions/functions_downloads.php");
                checkOnlineStatus();
                break;
            case "staff_messages_comments":
                if( !has_permission("canreply_staff_messages") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                break;
        }
        $BuildQuery = array( "message" => $message );
        $TSUE["TSUE_Database"]->update("tsue_comments", $BuildQuery, "comment_id=" . $TSUE["TSUE_Database"]->escape($comment_id));
        $message = $TSUE["TSUE_Parser"]->parse($message);
        ajax_message($message);
}


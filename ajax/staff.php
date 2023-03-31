<?php 
define("SCRIPTNAME", "staff.php");
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
    case "spam_cleaner":
        globalize("post", array( "memberid" => "INT", "do" => "TRIM", "Clean" => "ARRAY" ));
        if( !has_permission("canuse_spam_cleaner") || !$memberid || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Member = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$Member ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        if( $do == "clean" ) 
        {
            $affectedRows = 0;
            if( isset($Clean["Announcements"]) && $Clean["Announcements"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_announcements", "memberid = " . $memberid);
            }

            if( isset($Clean["Comments"]) && $Clean["Comments"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_comments", "memberid = " . $memberid);
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "memberid = " . $memberid);
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_reports_comments", "memberid = " . $memberid);
            }

            if( isset($Clean["PM"]) && $Clean["PM"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_messages_master", "owner_memberid = " . $memberid);
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_messages_replies", "memberid = " . $memberid);
            }

            if( isset($Clean["Reports"]) && $Clean["Reports"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_reports", "reported_by_memberid = " . $memberid);
            }

            if( isset($Clean["Requests"]) && $Clean["Requests"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_requests", "memberid = " . $memberid);
            }

            if( isset($Clean["StaffMessages"]) && $Clean["StaffMessages"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_staff_messages", "memberid = " . $memberid);
            }

            if( isset($Clean["Shouts"]) && $Clean["Shouts"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "memberid = " . $memberid);
            }

            if( isset($Clean["Subscripionts"]) && $Clean["Subscripionts"] == 1 ) 
            {
                $affectedRows += $TSUE["TSUE_Database"]->delete("tsue_forums_thread_subscribe", "memberid = " . $memberid);
            }

            if( isset($Clean["Posts"]) && $Clean["Posts"] == 1 ) 
            {
                require(REALPATH . "/library/classes/class_forums.php");
                $TSUE_Forums = new forums();
                $Posts = $TSUE["TSUE_Database"]->query("SELECT p.postid,p.threadid,p.memberid, t.memberid as threadOwner, f.forumid FROM tsue_forums_posts p LEFT JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_forums f ON (t.forumid=f.forumid) WHERE p.memberid = " . $memberid);
                if( $TSUE["TSUE_Database"]->num_rows($Posts) ) 
                {
                    while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($Posts) ) 
                    {
                        $TSUE_Forums->deletePost($Post["postid"], $Post["memberid"]);
                        $threadHasStillPosts = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.post_date, t.threadid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON (p.memberid=m.memberid) WHERE p.threadid = " . $TSUE["TSUE_Database"]->escape($Post["threadid"]) . " ORDER BY p.post_date DESC");
                        if( !$threadHasStillPosts ) 
                        {
                            $TSUE_Forums->deleteThread($Post["threadid"], $Post["forumid"], $Post["threadOwner"]);
                        }
                        else
                        {
                            $findLastPosts = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.post_date, t.threadid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON (p.memberid=m.memberid) WHERE t.forumid = " . $TSUE["TSUE_Database"]->escape($Post["forumid"]) . " ORDER BY p.post_date DESC");
                            $last_post_info = serialize(array( "lastpostdate" => $findLastPosts["post_date"], "lastposter" => $findLastPosts["membername"], "lastposterid" => $findLastPosts["memberid"] ));
                            $BuildQuery = array( "replycount" => array( "escape" => 0, "value" => "IF(replycount > 0, replycount - 1, 0)" ), "last_post_info" => $last_post_info, "last_post_threadid" => ($findLastPosts["threadid"] ? $findLastPosts["threadid"] : 0) );
                            $TSUE["TSUE_Database"]->update("tsue_forums", $BuildQuery, "forumid = " . $TSUE["TSUE_Database"]->escape($Post["forumid"]));
                            $BuildQuery = array( "reply_count" => array( "escape" => 0, "value" => "IF(reply_count > 0, reply_count - 1, 0)" ), "last_post_info" => $last_post_info, "last_post_date" => $findLastPosts["post_date"] );
                            $TSUE["TSUE_Database"]->update("tsue_forums_threads", $BuildQuery, "threadid = " . $TSUE["TSUE_Database"]->escape($Post["threadid"]));
                        }

                    }
                }

            }

            if( isset($Clean["Threads"]) && $Clean["Threads"] == 1 ) 
            {
                if( !isset($TSUE_Forums) ) 
                {
                    require(REALPATH . "/library/classes/class_forums.php");
                    $TSUE_Forums = new forums();
                }

                $Threads = $TSUE["TSUE_Database"]->query("SELECT threadid,forumid,memberid FROM tsue_forums_threads WHERE memberid = " . $memberid);
                if( $TSUE["TSUE_Database"]->num_rows($Threads) ) 
                {
                    while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
                    {
                        $posts = $TSUE["TSUE_Database"]->query("SELECT postid, memberid FROM tsue_forums_posts WHERE threadid = " . $TSUE["TSUE_Database"]->escape($Thread["threadid"]));
                        if( $TSUE["TSUE_Database"]->num_rows($posts) ) 
                        {
                            while( $post = $TSUE["TSUE_Database"]->fetch_assoc($posts) ) 
                            {
                                $TSUE_Forums->deletePost($post["postid"], $post["memberid"]);
                            }
                        }

                        $TSUE_Forums->deleteThread($Thread["threadid"], $Thread["forumid"], $Thread["memberid"]);
                    }
                }

            }

            if( $Clean ) 
            {
                $actions = array(  );
                foreach( $Clean as $LEFT => $RIGHT ) 
                {
                    $actions[] = " [" . $LEFT . "] ";
                }
                $Phrase = get_phrase("spam_content_cleaned_for_x", $Member["membername"] . " " . implode(" ", $actions));
                logAction($Phrase);
            }
            else
            {
                $Phrase = get_phrase("there_is_nothing_to_do");
            }

            ajax_message($Phrase, "-DONE-");
        }

        eval("\$staff_spam_cleaner_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_spam_cleaner_form") . "\";");
        ajax_message($staff_spam_cleaner_form, NULL, true, get_phrase("spam_cleaner") . ": " . $Member["membername"]);
        break;
    case "delete_staff_note":
        globalize("post", array( "noteid" => "INT" ));
        if( !has_permission("candelete_staff_notes") || !$noteid || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Note = $TSUE["TSUE_Database"]->query_result("SELECT n.*, m.membername, g.groupstyle FROM tsue_member_staff_notes n LEFT JOIN tsue_members m ON(n.memberid=m.memberid) LEFT JOIN tsue_membergroups g ON(m.membergroupid=g.membergroupid) WHERE n.noteid = " . $TSUE["TSUE_Database"]->escape($noteid));
        if( !$Note ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_member_staff_notes", "noteid = " . $TSUE["TSUE_Database"]->escape($noteid));
        $Output = get_phrase("staff_note_has_been_deleted", getMembername($Note["membername"], $Note["groupstyle"]));
        logAction($Output);
        ajax_message($Output, "-DONE-");
        break;
    case "add_a_note":
        globalize("post", array( "memberid" => "INT", "note" => "TRIM" ));
        if( !has_permission("canadd_note") || !$memberid || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        if( $TSUE["do"] == "save" ) 
        {
            if( !$note ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }
            else
            {
                $buildQuery = array( "memberid" => $memberid, "staffid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "note" => $note );
                $TSUE["TSUE_Database"]->insert("tsue_member_staff_notes", $buildQuery);
                $Output = get_phrase("a_new_staff_note_added", $membername);
                logAction($Output);
                ajax_message($Output);
            }

        }

        eval("\$staff_add_a_note_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_add_a_note_form") . "\";");
        ajax_message($staff_add_a_note_form, "", false, get_phrase("add_a_note") . ": " . $membername);
        break;
    case "member_ip_to_country":
        globalize("post", array( "memberid" => "INT" ));
        if( !$memberid || !has_permission("canview_special_details") || !has_permission("canview_member_profiles") || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Member = $TSUE["TSUE_Database"]->query_result("SELECT ipaddress FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$Member ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        if( !filter_var($Member["ipaddress"], FILTER_VALIDATE_IP) ) 
        {
            ajax_message(get_phrase("invalid_ip"), "-ERROR-");
        }

        include(REALPATH . "library/classes/class_geoip.php");
        $geoIP = geoip_open(REALPATH . "library/geoip/GeoIP.dat", GEOIP_STANDARD);
        $countryCode = geoip_country_code_by_addr($geoIP, $Member["ipaddress"]);
        $countryName = geoip_country_name_by_addr($geoIP, $Member["ipaddress"]);
        if( !$countryCode || !$countryName ) 
        {
            ajax_message(get_phrase("could_not_detect_country"), "-ERROR-");
        }

        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/countryFlags/" . strtolower($countryCode) . ".png", "alt" => $countryName, "title" => $countryName, "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
        $countryFlag = getImage($Image);
        $details = "";
        $json = file_get_contents("http://api.easyjquery.com/ips/?ip=" . $Member["ipaddress"] . "&full=true");
        if( $json ) 
        {
            $json = json_decode($json, true);
        }

        if( $json ) 
        {
            $details = "\r\n\t\t\t\t<table cellpadding=\"5\" cellspacing=\"0\" align=\"center\">";
            foreach( $json as $left => $right ) 
            {
                $details .= "\r\n\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t<td><b>" . html_clean($left) . "</b></td>\r\n\t\t\t\t\t\t<td>" . html_clean($right) . "</td>\r\n\t\t\t\t\t</tr>";
            }
            $details .= "\r\n\t\t\t\t</table>";
        }

        ajax_message($details, "", false, $countryName . " (" . html_clean($Member["ipaddress"]) . ")");
        break;
    case "find_all_content":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canview_all_content") || !$memberid || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membername, m.gender, g.groupstyle FROM tsue_members m INNER JOIN tsue_membergroups g USING(membergroupid) WHERE m.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$Member ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $_memberid = $memberid;
        $_membername = getMembername($Member["membername"], $Member["groupstyle"]);
        eval("\$by = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $tabs = array(  );
        $tabs["requests"] = get_phrase("message_nothing_found");
        $tabs["posts"] = $tabs["requests"];
        $tabs["threads"] = $tabs["posts"];
        $tabs["shoutbox"] = $tabs["threads"];
        $tabs["torrent_comments"] = $tabs["shoutbox"];
        $tabs["profile_comment_replies"] = $tabs["torrent_comments"];
        $tabs["profile_comments"] = $tabs["profile_comment_replies"];
        $recentProfileComments = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername as receiverName, g.groupstyle as receiverGroupstyle FROM tsue_comments c INNER JOIN tsue_members m ON(c.content_id=m.memberid) INNER JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'profile_comments' ORDER BY c.post_date");
        if( $TSUE["TSUE_Database"]->num_rows($recentProfileComments) ) 
        {
            $tabs["profile_comments"] = "";
            while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentProfileComments) ) 
            {
                $Comment["message"] = $TSUE["TSUE_Parser"]->parse($Comment["message"]);
                $_memberid = $Comment["content_id"];
                $_membername = getMembername($Comment["receiverName"], $Comment["receiverGroupstyle"]);
                eval("\$for = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $post_date = get_phrase("profile_post_by_x_for_y", $by, $for, convert_relative_time($Comment["post_date"]));
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_profile_comments") . "\";");
                $tabs["profile_comments"] .= $Activity;
            }
        }

        $recentProfileCommentReplies = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername as receiverName, g.groupstyle as receiverGroupstyle FROM tsue_comments_replies c INNER JOIN tsue_members m ON(c.content_id=m.memberid) INNER JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'profile_comments' ORDER BY c.post_date DESC ");
        if( $TSUE["TSUE_Database"]->num_rows($recentProfileCommentReplies) ) 
        {
            $tabs["profile_comment_replies"] = "";
            while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentProfileCommentReplies) ) 
            {
                $Comment["message"] = $TSUE["TSUE_Parser"]->parse($Comment["message"]);
                $_memberid = $Comment["content_id"];
                $_membername = getMembername($Comment["receiverName"], $Comment["receiverGroupstyle"]);
                eval("\$for = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $post_date = get_phrase("profile_post_by_x_for_y", $by, $for, convert_relative_time($Comment["post_date"]));
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_profile_comments") . "\";");
                $tabs["profile_comment_replies"] .= $Activity;
            }
            $tabs["profile_comments"] .= $tabs["profile_comment_replies"];
            unset($tabs["profile_comment_replies"]);
        }

        $recentTorrentComments = $TSUE["TSUE_Database"]->query("SELECT c.*, t.name as torrentName FROM tsue_comments c INNER JOIN tsue_torrents t ON(c.content_id=t.tid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'torrent_comments' ORDER BY c.post_date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($recentTorrentComments) ) 
        {
            $tabs["torrent_comments"] = "";
            while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentTorrentComments) ) 
            {
                $Comment["message"] = $TSUE["TSUE_Parser"]->parse($Comment["message"]);
                $tid = $Comment["content_id"];
                $torrentName = strip_tags($Comment["torrentName"]);
                eval("\$for = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_link") . "\";");
                $post_date = get_phrase("torrent_comment_by_x_for_y", $by, $for, convert_relative_time($Comment["post_date"]));
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_torrent_comments") . "\";");
                $tabs["torrent_comments"] .= $Activity;
            }
        }

        $recentShouts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE sdate, smessage FROM tsue_shoutbox WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY sdate DESC");
        if( $TSUE["TSUE_Database"]->num_rows($recentShouts) ) 
        {
            $tabs["shoutbox"] = "";
            while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentShouts) ) 
            {
                $Shout = $TSUE["TSUE_Parser"]->parse($Comment["smessage"]);
                $post_date = get_phrase("shout_by_x", $by, convert_relative_time($Comment["sdate"]));
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_shouts") . "\";");
                $tabs["shoutbox"] .= $Activity;
            }
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        $forumThreads = $TSUE["TSUE_Database"]->query("SELECT t.threadid, t.forumid, t.title, t.post_date, f.title as forumtitle, f.password FROM tsue_forums_threads t LEFT JOIN tsue_forums f USING(forumid) WHERE t.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY t.post_date DESC");
        if( $TSUE_Forums->forumCategories && $TSUE["TSUE_Database"]->num_rows($forumThreads) ) 
        {
            $tabs["threads"] = "";
            while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($forumThreads) ) 
            {
                if( isset($TSUE_Forums->availableForums[$Thread["forumid"]]) && has_forum_permission("canview_thread_list", $TSUE_Forums->forumPermissions[$Thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Thread["forumid"], $Thread["password"]) ) 
                {
                    $Thread["title"] = strip_tags($Thread["title"]);
                    $Thread["forumtitle"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Thread["forumid"] . "\">" . strip_tags($Thread["forumtitle"]) . "</a>";
                    $post_date = get_phrase("thread_by_x_in_y", $by, $Thread["forumtitle"], convert_relative_time($Thread["post_date"]));
                    eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_threads") . "\";");
                    $tabs["threads"] .= $Activity;
                }

            }
        }

        $forumPosts = $TSUE["TSUE_Database"]->query("SELECT p.postid, p.threadid, p.post_date, p.message, t.forumid, f.title as forumtitle, f.password FROM tsue_forums_posts p LEFT JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_forums f USING(forumid) WHERE p.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY p.post_date DESC");
        if( $TSUE_Forums->forumCategories && $TSUE["TSUE_Database"]->num_rows($forumPosts) ) 
        {
            $tabs["posts"] = "";
            while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($forumPosts) ) 
            {
                if( isset($TSUE_Forums->availableForums[$Post["forumid"]]) && has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$Post["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Post["forumid"], $Post["password"]) ) 
                {
                    $Post["message"] = substr(strip_tags($Post["message"]), 0, 100);
                    $Post["forumtitle"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Post["forumid"] . "\">" . strip_tags($Post["forumtitle"]) . "</a>";
                    $post_date = get_phrase("post_by_x_in_y", $by, $Post["forumtitle"], convert_relative_time($Post["post_date"]));
                    eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_posts") . "\";");
                    $tabs["posts"] .= $Activity;
                }

            }
        }

        $recentRequests = $TSUE["TSUE_Database"]->query("SELECT title, added FROM tsue_requests WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY added DESC");
        if( $TSUE["TSUE_Database"]->num_rows($recentRequests) ) 
        {
            $tabs["requests"] = "";
            while( $recentRequest = $TSUE["TSUE_Database"]->fetch_assoc($recentRequests) ) 
            {
                $Request = substr(strip_tags($recentRequest["title"]), 0, 85);
                eval("\$request_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("request_link") . "\";");
                $post_date = get_phrase("request_by_x", $by, convert_relative_time($recentRequest["added"]));
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_requests") . "\";");
                $tabs["requests"] .= $Activity;
            }
        }

        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("find_all_content_main") . "\";");
        ajax_message($Output, "", false, get_phrase("find_all_content") . ": " . $Member["membername"]);
        break;
    case "remove_member_avatar":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canremove_avatar") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        $ValidAvatarExtensions = array( "jpg", "gif", "png", "jpeg" );
        foreach( $ValidAvatarExtensions as $AvatarExtension ) 
        {
            if( is_file(REALPATH . "data/avatars/l/" . $memberid . "." . $AvatarExtension) ) 
            {
                $avatarName = $memberid . "." . $AvatarExtension;
            }

        }
        if( isset($avatarName) ) 
        {
            foreach( array( "l", "m", "s" ) as $size ) 
            {
                @unlink(REALPATH . "data/avatars/" . $size . "/" . $avatarName);
            }
        }

        $Output = get_phrase("member_avatar_has_been_removed", $membername, $TSUE["TSUE_Member"]->info["membername"]);
        logAction($Output);
        ajax_message($Output);
        break;
    case "reset_member_passkey":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canreset_passkey") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        $passkey = generatePasskey();
        $TSUE["TSUE_Database"]->update("tsue_members", array( "passkey" => $passkey ), "memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "torrent_pass" => substr($passkey, 0, 32) ), "memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        $Output = get_phrase("reset_passkey_message", $membername);
        logAction($Output);
        ajax_message($Output);
        break;
    case "mute_member":
        globalize("post", array( "memberid" => "INT", "notes" => "TRIM", "end_date" => "TRIM", "areas" => "TRIM" ));
        if( !has_permission("canmute_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        if( $TSUE["do"] == "mute" ) 
        {
            if( !$areas ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $areas = array_map("intval", tsue_explode(",", $areas));
            if( empty($areas) ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $areas = implode("", $areas);
            if( $end_date && substr_count($end_date, "/") == 2 ) 
            {
                list($day, $month, $year) = tsue_explode("/", $end_date);
                $end_date = strtotime((string) $year . "/" . $month . "/" . $day);
                if( !checkdate($month, $day, $year) || $end_date <= TIMENOW ) 
                {
                    ajax_message(get_phrase("mute_invalid_date"), "-ERROR-");
                }

            }

            $muteMember = array( "memberid" => $memberid, "muted_by" => $TSUE["TSUE_Member"]->info["memberid"], "mute_date" => TIMENOW, "end_date" => ($end_date ? $end_date : 0), "notes" => ($notes ? strip_tags($notes) : "") );
            $TSUE["TSUE_Database"]->replace("tsue_member_mutes", $muteMember);
            $BuildQuery = array( "muted" => $areas );
            $TSUE["TSUE_Database"]->update("tsue_member_profile", $BuildQuery, "memberid=" . $memberid);
            alert_member($memberid, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "mutes", 0, "mute_member");
            $Output = get_phrase("member_has_been_muted", $membername, $TSUE["TSUE_Member"]->info["membername"]);
            logAction($Output);
            ajax_message($Output);
        }

        eval("\$staff_mute_member_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_mute_member_form") . "\";");
        ajax_message($staff_mute_member_form, "", false, get_phrase("mute_member") . ": " . $membername);
        break;
    case "lift_mute":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canmute_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $mutedMember = $TSUE["TSUE_Database"]->query_result("SELECT w.*, m.membername, mm.membername AS mutedBy FROM tsue_member_mutes w LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_members mm ON (w.muted_by=mm.memberid) WHERE w.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$mutedMember ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_member_mutes", "memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        $BuildQuery = array( "muted" => 0 );
        $TSUE["TSUE_Database"]->update("tsue_member_profile", $BuildQuery, "memberid=" . $mutedMember["memberid"]);
        alert_member($mutedMember["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "mutes", 0, "mute_lifted");
        $Output = get_phrase("mute_has_been_lifted", $mutedMember["membername"], $TSUE["TSUE_Member"]->info["membername"]);
        ajax_message($Output, "", false, get_phrase("lift_mute") . ": " . $mutedMember["membername"]);
        break;
    case "award_member":
        globalize("post", array( "memberid" => "INT", "reason" => "STRIP", "award_id" => "INT" ));
        if( !has_permission("canaward_members") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        $defaultTheme = $TSUE["TSUE_Template"]->ThemeName;
        if( $TSUE["do"] == "award" ) 
        {
            $awardMember = array( "award_id" => $award_id, "memberid" => $memberid, "reason" => $reason, "givenby" => $TSUE["TSUE_Member"]->info["memberid"], "date" => TIMENOW );
            if( !$awardMember["award_id"] ) 
            {
                ajax_message(get_phrase("awards_please_select_a_valid_award_type"), "-ERROR-");
            }
            else
            {
                $checkAward = $TSUE["TSUE_Database"]->query_result("SELECT award_title, award_image FROM tsue_awards WHERE award_id = " . $TSUE["TSUE_Database"]->escape($awardMember["award_id"]));
                if( !$checkAward ) 
                {
                    ajax_message(get_phrase("awards_please_select_a_valid_award_type"), "-ERROR-");
                }

            }

            if( !$awardMember["reason"] ) 
            {
                ajax_message(get_phrase("awards_please_enter_a_valid_award_reason"), "-ERROR-");
            }

            $TSUE["TSUE_Database"]->insert("tsue_awards_members", $awardMember);
            $subject = get_phrase("you_got_a_new_award_subject");
            $reply = get_phrase("you_got_a_new_award_message", $membername, getMembername($TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"]), "[img]" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $defaultTheme . "/awards/" . $checkAward["award_image"] . "[/img] " . $checkAward["award_title"], $awardMember["reason"]);
            sendPM($subject, $TSUE["TSUE_Member"]->info["memberid"], $awardMember["memberid"], nl2br($reply));
            $Phrase = get_phrase("awards_member_got_an_alert", $membername, $checkAward["award_title"], $TSUE["TSUE_Member"]->info["membername"], $awardMember["reason"]);
            logAction($Phrase);
            ajax_message($Phrase);
        }

        $options = "\r\n\t\t\t<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\r\n\t\t\t\t<tr>";
        $Count = 0;
        for( $Awards = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_awards ORDER BY award_title ASC"); $Award = $TSUE["TSUE_Database"]->fetch_assoc($Awards); $Count++ ) 
        {
            if( $Count % 20 == 0 ) 
            {
                $options .= "</tr><tr>";
            }

            $options .= "\r\n\t\t\t<td>\r\n\t\t\t\t<label style=\"margin: 5px;\">\r\n\t\t\t\t\t<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $defaultTheme . "/awards/" . $Award["award_image"] . "\" alt=\"" . $Award["award_title"] . "\" title=\"" . $Award["award_title"] . "\" /> <input type=\"radio\" name=\"award_id\" value=\"" . $Award["award_id"] . "\" />\r\n\t\t\t\t</label>\r\n\t\t\t</td>";
        }
        $options .= "\r\n\t\t\t</tr>\r\n\t\t</table>";
        eval("\$staff_award_member_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_award_member_form") . "\";");
        ajax_message($staff_award_member_form, "", false, get_phrase("awards_award_member") . ": " . $membername);
        break;
    case "warn_member":
        globalize("post", array( "memberid" => "INT", "notes" => "TRIM", "end_date" => "TRIM" ));
        if( !has_permission("canwarn_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        if( $TSUE["do"] == "warn" ) 
        {
            if( $end_date && substr_count($end_date, "/") == 2 ) 
            {
                list($day, $month, $year) = tsue_explode("/", $end_date);
                $end_date = strtotime((string) $year . "/" . $month . "/" . $day);
                if( !checkdate($month, $day, $year) || $end_date <= TIMENOW ) 
                {
                    ajax_message(get_phrase("warned_invalid_date"), "-ERROR-");
                }

            }

            $warnMember = array( "memberid" => $memberid, "warned_by" => $TSUE["TSUE_Member"]->info["memberid"], "warn_date" => TIMENOW, "end_date" => ($end_date ? $end_date : 0), "notes" => ($notes ? strip_tags($notes) : "") );
            $TSUE["TSUE_Database"]->replace("tsue_member_warns", $warnMember);
            $BuildQuery = array( "total_warns" => array( "escape" => 0, "value" => "total_warns+1" ) );
            $TSUE["TSUE_Database"]->update("tsue_member_profile", $BuildQuery, "memberid=" . $memberid);
            alert_member($memberid, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "warns", 0, "warn_member");
            $Output = get_phrase("warned_member_has_been_warned", $membername, $TSUE["TSUE_Member"]->info["membername"]);
            logAction($Output);
            ajax_message($Output);
        }

        eval("\$staff_warn_member_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_warn_member_form") . "\";");
        ajax_message($staff_warn_member_form, "", false, get_phrase("warned_warn_member") . ": " . $membername);
        break;
    case "lift_warn":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canwarn_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $warnedMember = $TSUE["TSUE_Database"]->query_result("SELECT w.*, m.membername, mm.membername AS warnedBy FROM tsue_member_warns w LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_members mm ON (w.warned_by=mm.memberid) WHERE w.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$warnedMember ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_member_warns", "memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        $BuildQuery = array( "total_warns" => array( "escape" => 0, "value" => "IF(total_warns > 0, total_warns-1, 0)" ) );
        $TSUE["TSUE_Database"]->update("tsue_member_profile", $BuildQuery, "memberid=" . $warnedMember["memberid"]);
        alert_member($warnedMember["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "warns", 0, "warn_lifted");
        $Output = get_phrase("warned_warn_has_been_lifted", $warnedMember["membername"], $TSUE["TSUE_Member"]->info["membername"]);
        logAction($Output);
        ajax_message($Output, "", false, get_phrase("warned_lift_warn") . ": " . $warnedMember["membername"]);
        break;
    case "ban_member":
        globalize("post", array( "memberid" => "INT", "reason" => "TRIM", "end_date" => "TRIM" ));
        if( !has_permission("canban_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        if( $TSUE["do"] == "ban" ) 
        {
            if( $end_date && substr_count($end_date, "/") == 2 ) 
            {
                list($day, $month, $year) = tsue_explode("/", $end_date);
                $end_date = strtotime((string) $year . "/" . $month . "/" . $day);
                if( !checkdate($month, $day, $year) || $end_date <= TIMENOW ) 
                {
                    ajax_message(get_phrase("banned_invalid_date"), "-ERROR-");
                }

            }

            $banMember = array( "memberid" => $memberid, "banned_by" => $TSUE["TSUE_Member"]->info["memberid"], "ban_date" => TIMENOW, "end_date" => ($end_date ? $end_date : 0), "reason" => ($reason ? strip_tags($reason) : "") );
            $TSUE["TSUE_Database"]->replace("tsue_member_bans", $banMember);
            $Output = get_phrase("banned_member_has_been_banned", $membername, $TSUE["TSUE_Member"]->info["membername"]);
            logAction($Output);
            ajax_message($Output);
        }

        eval("\$staff_ban_member_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_ban_member_form") . "\";");
        ajax_message($staff_ban_member_form, "", false, get_phrase("banned_ban_member") . ": " . $membername);
        break;
    case "lift_ban":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canban_member") || !$memberid || is_member_of("unregistered") || $memberid == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $bannedMember = $TSUE["TSUE_Database"]->query_result("SELECT b.*, m.membername, mm.membername AS bannedBy FROM tsue_member_bans b LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_members mm ON (b.banned_by=mm.memberid) WHERE b.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$bannedMember ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_member_bans", "memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
        $Output = get_phrase("banned_ban_has_been_lifted", $bannedMember["membername"], $TSUE["TSUE_Member"]->info["membername"]);
        logAction($Output);
        ajax_message($Output, "", false, get_phrase("banned_lift_ban") . ": " . $bannedMember["membername"]);
        break;
    case "member_history":
        globalize("post", array( "memberid" => "INT" ));
        if( !has_permission("canview_member_history") || !$memberid || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
        if( !$checkMember || !$checkMember["membername"] ) 
        {
            ajax_message(get_phrase("member_not_found"), "-ERROR-");
        }

        $membername = strip_tags($checkMember["membername"]);
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        require_once(REALPATH . "library/functions/functions_getInvites.php");
        $tabs["downloadHistory"] = prepareDownloadHistory($memberid, false, false);
        $tabs["uploadHistory"] = prepareUploadHistory($memberid, false);
        $tabs["stats"] = prepareStats($memberid, false);
        $tabs["warnHistory"] = prepareWarns($memberid, false);
        $tabs["muteHistory"] = prepareMutes($memberid, false);
        $tabs["subscriptions"] = prepareSubscriptions($memberid, true);
        $tabs["hitrun"] = prepareHitRunWarns($memberid, false);
        $tabs["staff_notes"] = prepareStaffNotes($memberid);
        $tabs["ip_history"] = prepareIPHistory($memberid);
        $tabs["invitetree"] = prepareInviteList($memberid);
        eval("\$staff_member_history_tabs = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_member_history_tabs") . "\";");
        ajax_message($staff_member_history_tabs, NULL, true, get_phrase("history_link") . ": " . $membername);
}


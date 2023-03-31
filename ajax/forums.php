<?php 
define("SCRIPTNAME", "forums.php");
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
    case "threadPreview":
        globalize("post", array( "forumid" => "INT", "threadid" => "INT" ));
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        $Output = $TSUE_Forums->showThreadFirstPostForAjax($forumid, $threadid);
        ajax_message($Output, "", false);
        break;
    case "subscribeToThread":
        globalize("post", array( "threadid" => "INT", "email_notification" => "INT" ));
        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !has_forum_permission("cansubscribe_to_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            $buildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "threadid" => $threadid, "email_notification" => $email_notification );
            $TSUE["TSUE_Database"]->replace("tsue_forums_thread_subscribe", $buildQuery);
            ajax_message(get_phrase("thread_subscription_added"), false, "");
        }
        else
        {
            $instantChecked = $noInstantChecked = "";
            $emailNotification = $TSUE["TSUE_Database"]->query_result("SELECT email_notification FROM tsue_forums_thread_subscribe WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
            if( $emailNotification && $emailNotification["email_notification"] == "1" ) 
            {
                $instantChecked = " checked=\"checked\"";
            }
            else
            {
                $noInstantChecked = " checked=\"checked\"";
            }

            eval("\$subscribeToThisThread = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_subscribe_to_this_thread") . "\";");
            ajax_message($subscribeToThisThread, false, "", get_phrase("notification_method"));
        }

        break;
    case "new_thread":
        globalize("post", array( "forumid" => "INT", "title" => "TRIM", "message" => "TRIM", "attachment_ids" => "TRIM", "email_notification" => "INT", "poll_question" => "TRIM", "pollOptions" => "TRIM", "closeDaysAfter" => "INT", "multiple" => "INT" ));
        if( isMuted($TSUE["TSUE_Member"]->info["muted"], "forums") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        if( !has_forum_permission("canpost_new_thread", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $message = $TSUE["TSUE_Parser"]->clearTinymceP($message);
        $strlenOriginalText = strlenOriginalText($message);
        if( $strlenOriginalText < $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_min_post_length"] ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        check_flood("forums_new_thread");
        $BuildQuery = array( "forumid" => $forum["forumid"], "title" => $title, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "post_date" => TIMENOW, "last_post_info" => "", "last_post_date" => TIMENOW );
        if( $TSUE["TSUE_Database"]->insert("tsue_forums_threads", $BuildQuery) ) 
        {
            $threadid = $TSUE["TSUE_Database"]->insert_id();
            shoutboxAnnouncement(array( "new_thread", $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"], $forum["forumid"], $threadid, strip_tags($title) ));
            if( has_forum_permission("cansubscribe_to_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && ($email_notification == "0" || $email_notification == "1") ) 
            {
                $buildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "threadid" => $threadid, "email_notification" => $email_notification );
                $TSUE["TSUE_Database"]->replace("tsue_forums_thread_subscribe", $buildQuery);
            }

            if( has_forum_permission("canpost_polls", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $poll_question && $pollOptions ) 
            {
                $pollOptions = tsue_explode(",", $pollOptions);
                $Options = array(  );
                foreach( $pollOptions as $Option ) 
                {
                    $Option = trim($Option);
                    if( $Option ) 
                    {
                        $Options[] = $Option;
                    }

                }
                if( $Options ) 
                {
                    $buildQuery = array( "date" => TIMENOW, "active" => 1, "question" => $poll_question, "options" => implode("~", $Options), "votes" => "", "voters" => "", "multiple" => ($multiple == "1" ? 1 : 0), "closeDaysAfter" => $closeDaysAfter, "closed" => 0, "threadid" => $threadid, "createdinThread" => 1 );
                    $TSUE["TSUE_Database"]->insert("tsue_poll", $buildQuery);
                }

            }

            updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_new_thread"], $TSUE["TSUE_Member"]->info["memberid"]);
            $BuildQuery = array( "threadid" => $threadid, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "post_date" => TIMENOW, "message" => $message );
            if( $TSUE["TSUE_Database"]->insert("tsue_forums_posts", $BuildQuery) ) 
            {
                $postid = $TSUE["TSUE_Database"]->insert_id();
                profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "total_posts" => array( "escape" => 0, "value" => "total_posts+1" ) ));
            }
            else
            {
                ajax_message(get_phrase("database_error"), "-ERROR-");
            }

            if( $attachment_ids ) 
            {
                $attachment_ids = tsue_explode(",", $attachment_ids);
                if( $attachment_ids && count($attachment_ids) ) 
                {
                    foreach( $attachment_ids as $attachment_id ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $postid, "associated" => 1 ), "attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id));
                    }
                }

            }

            $last_post_info = serialize(array( "lastpostdate" => TIMENOW, "lastposter" => $TSUE["TSUE_Member"]->info["membername"], "lastposterid" => $TSUE["TSUE_Member"]->info["memberid"] ));
            $TSUE["TSUE_Database"]->update("tsue_forums_threads", array( "last_post_info" => array( "escape" => 1, "value" => $last_post_info ) ), "threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
            $TSUE["TSUE_Database"]->update("tsue_forums", array( "last_post_threadid" => $threadid, "last_post_info" => array( "escape" => 1, "value" => $last_post_info ), "threadcount" => array( "escape" => 0, "value" => "threadcount + 1" ) ), "forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
            if( 0 < $forum["parentid"] ) 
            {
                $TSUE["TSUE_Database"]->update("tsue_forums", array( "last_post_info" => array( "escape" => 1, "value" => $last_post_info ) ), "forumid = " . $TSUE["TSUE_Database"]->escape($forum["parentid"]));
            }

            deleteCache("TSUEPlugin_recentThreads_");
            echo $threadid;
        }
        else
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        break;
    case "deleteThread":
        globalize("post", array( "threadid" => "INT" ));
        check_flood("forums_delete_thread");
        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, title, memberid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canDeleteThreads = has_forum_permission("candelete_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( !$canDeleteThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $posts = $TSUE["TSUE_Database"]->query("SELECT postid, memberid FROM tsue_forums_posts WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( $TSUE["TSUE_Database"]->num_rows($posts) ) 
        {
            while( $post = $TSUE["TSUE_Database"]->fetch_assoc($posts) ) 
            {
                $TSUE_Forums->deletePost($post["postid"], $post["memberid"]);
            }
        }

        $TSUE_Forums->deleteThread($threadid, $forum["forumid"], $thread["memberid"]);
        exit( $forum["forumid"] );
    case "forums_post_reply":
        globalize("post", array( "message" => "TRIM", "threadid" => "INT", "forumid" => "INT", "attachment_ids" => "TRIM" ));
        $thread = $TSUE["TSUE_Database"]->query_result("SELECT title, memberid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) || isMuted($TSUE["TSUE_Member"]->info["muted"], "forums") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts", "canreply_threads" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $message = $TSUE["TSUE_Parser"]->clearTinymceP($message);
        $strlenOriginalText = strlenOriginalText($message);
        if( $strlenOriginalText < $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_min_post_length"] ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        check_flood("forums_post_reply");
        $BuildQuery = array( "threadid" => $threadid, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "post_date" => TIMENOW, "message" => $message );
        if( $TSUE["TSUE_Database"]->insert("tsue_forums_posts", $BuildQuery) ) 
        {
            $postid = $TSUE["TSUE_Database"]->insert_id();
            shoutboxAnnouncement(array( "new_forum_reply", $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"], $forum["forumid"], $threadid, strip_tags($thread["title"]), $postid ));
            updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_new_replies"], $TSUE["TSUE_Member"]->info["memberid"]);
            profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "total_posts" => array( "escape" => 0, "value" => "total_posts+1" ) ));
            if( $attachment_ids ) 
            {
                $attachment_ids = tsue_explode(",", $attachment_ids);
                if( $attachment_ids && count($attachment_ids) ) 
                {
                    foreach( $attachment_ids as $attachment_id ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $postid, "associated" => 1 ), "attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id));
                    }
                }

            }

            if( $thread["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
            {
                alert_member($thread["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "thread_posts", $threadid, "new_reply", $postid);
            }

            $TSUE_Forums->handleSubscribedThreads($threadid, $forumid, strip_tags($thread["title"]), $TSUE["TSUE_Member"]->info["memberid"], $postid);
            $threadOwner = $thread["memberid"];
            $quoteCache = array(  );
            $pattern = "#\\[quote=(.*?)\\](.*?)\\[\\/quote\\]#esi";
            $replace = "\\handleQuotedReply('\$1','\$2', " . $threadid . ", " . $postid . ", " . $threadOwner . ")";
            $text = $message;
            while( preg_match($pattern, $text) ) 
            {
                $text = preg_replace($pattern, $replace, $text);
            }
            $last_post_info = serialize(array( "lastpostdate" => TIMENOW, "lastposter" => $TSUE["TSUE_Member"]->info["membername"], "lastposterid" => $TSUE["TSUE_Member"]->info["memberid"] ));
            $TSUE["TSUE_Database"]->update("tsue_forums_threads", array( "last_post_date" => TIMENOW, "last_post_info" => array( "escape" => 1, "value" => $last_post_info ), "reply_count" => array( "escape" => 0, "value" => "reply_count + 1" ) ), "threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
            $TSUE["TSUE_Database"]->update("tsue_forums", array( "last_post_threadid" => $threadid, "last_post_info" => array( "escape" => 1, "value" => $last_post_info ), "replycount" => array( "escape" => 0, "value" => "replycount + 1" ) ), "forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
            if( 0 < $forum["parentid"] ) 
            {
                $TSUE["TSUE_Database"]->update("tsue_forums", array( "last_post_info" => array( "escape" => 1, "value" => $last_post_info ) ), "forumid = " . $TSUE["TSUE_Database"]->escape($forum["parentid"]));
            }

            deleteCache("TSUEPlugin_recentThreads_");
            $post = array( "message" => $message, "postid" => $postid, "threadid" => $threadid, "forumid" => $forum["forumid"], "groupname" => $TSUE["TSUE_Member"]->info["groupname"], "signature" => $TSUE["TSUE_Member"]->info["signature"] );
            ajax_message($TSUE_Forums->prepareSinglePostData($post, $forum));
        }
        else
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        break;
    case "forums_delete_post":
        globalize("post", array( "postid" => "INT", "threadid" => "INT", "forumid" => "INT" ));
        check_flood("forums_delete_post");
        $post = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, t.locked, t.memberid as threadOwner FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) WHERE p.postid = " . $TSUE["TSUE_Database"]->escape($postid) . " AND p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND t.forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        if( !$post ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        if( $post["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canDelete = has_forum_permission("candelete_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
        if( !$canDelete ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE_Forums->deletePost($postid, $post["memberid"]);
        $threadHasStillPosts = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.post_date, t.threadid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON (p.memberid=m.memberid) WHERE p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " ORDER BY p.post_date DESC");
        if( !$threadHasStillPosts ) 
        {
            $TSUE_Forums->deleteThread($threadid, $forumid, $post["threadOwner"]);
            exit( "ALL DELETED" );
        }

        $last_post_info = "";
        $last_post_threadid = 0;
        $last_post_date = 0;
        $findLastPosts = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.post_date, t.threadid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON (p.memberid=m.memberid) WHERE t.forumid = " . $TSUE["TSUE_Database"]->escape($forumid) . " ORDER BY p.post_date DESC LIMIT 1");
        if( $findLastPosts ) 
        {
            $last_post_info = serialize(array( "lastpostdate" => $findLastPosts["post_date"], "lastposter" => $findLastPosts["membername"], "lastposterid" => $findLastPosts["memberid"] ));
            $last_post_threadid = $findLastPosts["threadid"];
            $last_post_date = $findLastPosts["post_date"];
        }

        $buildQuery = array( "replycount" => array( "escape" => 0, "value" => "IF(replycount > 0, replycount - 1, 0)" ), "last_post_info" => $last_post_info, "last_post_threadid" => $last_post_threadid );
        $TSUE["TSUE_Database"]->update("tsue_forums", $buildQuery, "forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        if( 0 < $forum["parentid"] ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_forums", array( "last_post_info" => array( "escape" => 1, "value" => $last_post_info ) ), "forumid = " . $TSUE["TSUE_Database"]->escape($forum["parentid"]));
        }

        $buildQuery = array( "reply_count" => array( "escape" => 0, "value" => "IF(reply_count > 0, reply_count - 1, 0)" ), "last_post_info" => $last_post_info, "last_post_date" => $last_post_date );
        $TSUE["TSUE_Database"]->update("tsue_forums_threads", $buildQuery, "threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        deleteCache("TSUEPlugin_recentThreads_");
        ajax_message(get_phrase("message_deleted"));
        break;
    case "delete_post_image":
        globalize("post", array( "attachment_id" => "INT" ));
        $Attachment = $TSUE["TSUE_Database"]->query_result("SELECT content_id, filename FROM tsue_attachments WHERE attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id) . " AND content_type = 'posts'");
        if( !$Attachment ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        check_flood("forums_delete_image");
        $post = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, t.locked, t.memberid as threadOwner, f.forumid FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) INNER JOIN tsue_forums f USING(forumid) WHERE p.postid = " . $TSUE["TSUE_Database"]->escape($Attachment["content_id"]));
        if( !$post ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$post["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$post["forumid"]];
        if( $post["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canDelete = has_forum_permission("candelete_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
        if( !$canDelete ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id=" . $TSUE["TSUE_Database"]->escape($attachment_id));
        $filename = REALPATH . "/data/posts/" . $Attachment["filename"];
        if( is_file($filename) ) 
        {
            @unlink($filename);
        }

        ajax_message(get_phrase("attachment_has_been_deleted", strip_tags($Attachment["filename"])));
        break;
    case "moveThread":
        globalize("post", array( "threadid" => "INT", "title" => "TRIM", "newforumid" => "INT" ));
        check_flood("forums_move_thread");
        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, title, memberid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canMoveThreads = has_forum_permission("canmove_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( !$canMoveThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            if( !$title || !$newforumid || $forum["forumid"] == $newforumid ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }
            else
            {
                $TSUE_Forums->moveThread($newforumid, $threadid, $forum);
                echo $newforumid;
                exit();
            }

        }
        else
        {
            $title = strip_tags($thread["title"]);
            $forumList = $TSUE_Forums->prepareForumListSelectbox("newforumid", $forum["forumid"]);
            eval("\$moveThread = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_moveThread") . "\";");
            ajax_message($moveThread, false, "", get_phrase("forums_move_thread"));
        }

        break;
    case "massMoveThreads":
        globalize("post", array( "threadids" => "TRIM", "newforumid" => "INT" ));
        $threadids = implode(",", array_map("intval", explode(",", $threadids)));
        $threads = $TSUE["TSUE_Database"]->query("SELECT forumid, title, memberid, locked FROM tsue_forums_threads WHERE threadid IN (" . $threadids . ")");
        if( !$TSUE["TSUE_Database"]->num_rows($threads) ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $thread = $TSUE["TSUE_Database"]->fetch_object($threads);
        $forumid = $thread->forumid;
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($threads) ) 
        {
            if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
            }

        }
        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canMoveThreads = has_forum_permission("canmove_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( !$canMoveThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            if( !$newforumid || $forum["forumid"] == $newforumid ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }
            else
            {
                $threadids = explode(",", $threadids);
                foreach( $threadids as $threadid ) 
                {
                    $TSUE_Forums->moveThread($newforumid, $threadid, $forum);
                }
            }

        }
        else
        {
            ajax_message($TSUE["TSUE_Language"]->phrase["forums_move_thread_destination"] . ": " . $TSUE_Forums->prepareForumListSelectbox("newforumid", $forum["forumid"]) . " <input type=\"button\" name=\"massMoveThreads\" value=\"" . get_phrase("button_save") . "\" class=\"submit\" />");
        }

        break;
    case "massLockThreads":
    case "massUnLockThreads":
        globalize("post", array( "threadids" => "TRIM" ));
        $threadids = implode(",", array_map("intval", explode(",", $threadids)));
        $threads = $TSUE["TSUE_Database"]->query("SELECT forumid, title, memberid, locked FROM tsue_forums_threads WHERE threadid IN (" . $threadids . ")");
        if( !$TSUE["TSUE_Database"]->num_rows($threads) ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $thread = $TSUE["TSUE_Database"]->fetch_object($threads);
        $forumid = $thread->forumid;
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($threads) ) 
        {
            if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
            }

        }
        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canLockThreads = has_forum_permission("canlock_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( !$canLockThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->update("tsue_forums_threads", array( "locked" => ($TSUE["action"] == "massUnLockThreads" ? 0 : 1) ), "threadid IN (" . $threadids . ")");
        deleteCache("TSUEPlugin_recentThreads_");
        break;
    case "massStickyThreads":
    case "massUnStickyThreads":
        globalize("post", array( "threadids" => "TRIM" ));
        $threadids = implode(",", array_map("intval", explode(",", $threadids)));
        $threads = $TSUE["TSUE_Database"]->query("SELECT forumid, title, memberid, locked FROM tsue_forums_threads WHERE threadid IN (" . $threadids . ")");
        if( !$TSUE["TSUE_Database"]->num_rows($threads) ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $thread = $TSUE["TSUE_Database"]->fetch_object($threads);
        $forumid = $thread->forumid;
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($threads) ) 
        {
            if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
            }

        }
        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canStickyThreads = has_forum_permission("cansticky_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( !$canStickyThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->update("tsue_forums_threads", array( "sticky" => ($TSUE["action"] == "massUnStickyThreads" ? 0 : 1) ), "threadid IN (" . $threadids . ")");
        deleteCache("TSUEPlugin_recentThreads_");
        break;
    case "editThread":
        globalize("post", array( "threadid" => "INT", "title" => "TRIM", "sticky" => "INT", "locked" => "INT", "pid" => "INT" ));
        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, title, memberid, sticky, locked, pid FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canEditThreads = has_forum_permission("canedit_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $thread["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
        if( !$canEditThreads ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            if( !$title ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }
            else
            {
                $BuildQuery = array( "title" => $title );
                if( has_forum_permission("canlock_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
                {
                    $BuildQuery["locked"] = $locked;
                }

                if( has_forum_permission("cansticky_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
                {
                    $BuildQuery["sticky"] = $sticky;
                }

                $availablePrefixes = array( "0" );
                $Prefixes = $TSUE["TSUE_Database"]->query("SELECT pid,viewpermissions FROM tsue_forums_thread_prefixes");
                if( $TSUE["TSUE_Database"]->num_rows($Prefixes) ) 
                {
                    while( $Prefix = $TSUE["TSUE_Database"]->fetch_assoc($Prefixes) ) 
                    {
                        if( $Prefix["viewpermissions"] ) 
                        {
                            $Prefix["viewpermissions"] = unserialize($Prefix["viewpermissions"]);
                            if( in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Prefix["viewpermissions"]) ) 
                            {
                                $availablePrefixes[] = $Prefix["pid"];
                            }

                        }
                        else
                        {
                            $availablePrefixes[] = $Prefix["pid"];
                        }

                    }
                }

                if( $availablePrefixes && in_array($pid, $availablePrefixes) ) 
                {
                    $BuildQuery["pid"] = $pid;
                }

                $TSUE["TSUE_Database"]->update("tsue_forums_threads", $BuildQuery, "threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
                exit();
            }

        }
        else
        {
            $title = strip_tags($thread["title"]);
            $lockedChecked = ($thread["locked"] == 1 ? " checked=\"checked\"" : "");
            $stickyChecked = ($thread["sticky"] == 1 ? " checked=\"checked\"" : "");
            $threadPrefixes = "";
            $TSUE["TSUE_Settings"]->loadSettings("forums_thread_prefixes");
            if( !empty($TSUE["TSUE_Settings"]->settings["forums_thread_prefixes"]) ) 
            {
                $count = 0;
                $threadPrefixes = "\r\n\t\t\t\t<table width=\"100%\" cellpadding=\"5\" cellspacing=\"0\">\r\n\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t<td>\r\n\t\t\t\t\t\t\t<label>\r\n\t\t\t\t\t\t\t\t<input type=\"radio\" name=\"pid\" value=\"0\"" . ((0 == $thread["pid"] ? " checked=\"checked\"" : "")) . " />\r\n\t\t\t\t\t\t\t\t<span>" . get_phrase("no_prefix") . "</span> \r\n\t\t\t\t\t\t\t</label>\r\n\t\t\t\t\t\t</td>";
                foreach( $TSUE["TSUE_Settings"]->settings["forums_thread_prefixes"] as $Prefix ) 
                {
                    if( $Prefix["viewpermissions"] ) 
                    {
                        $Prefix["viewpermissions"] = unserialize($Prefix["viewpermissions"]);
                        if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Prefix["viewpermissions"]) ) 
                        {
                            continue;
                        }

                    }

                    if( $count % 3 == 0 ) 
                    {
                        $threadPrefixes .= "\r\n\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t<tr>";
                    }

                    $threadPrefixes .= "\r\n\t\t\t\t\t<td>\r\n\t\t\t\t\t\t<label>\r\n\t\t\t\t\t\t\t<input type=\"radio\" name=\"pid\" value=\"" . $Prefix["pid"] . "\"" . (($Prefix["pid"] == $thread["pid"] ? " checked=\"checked\"" : "")) . " />\r\n\t\t\t\t\t\t\t<span class=\"prefixButton " . $Prefix["cssname"] . "\">" . $Prefix["pname"] . "</span> \r\n\t\t\t\t\t\t</label>\r\n\t\t\t\t\t</td>";
                    $count++;
                }
                $threadPrefixes .= "\r\n\t\t\t\t\t</tr>\r\n\t\t\t\t</table>";
                if( $count == 0 ) 
                {
                    $threadPrefixes = "";
                }

            }

            $edit_thread_lock = $edit_thread_sticky = "";
            if( has_forum_permission("canlock_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                eval("\$edit_thread_lock = \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_thread_lock") . "\";");
            }

            if( has_forum_permission("cansticky_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                eval("\$edit_thread_sticky = \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_thread_sticky") . "\";");
            }

            eval("\$editThread = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_editThread") . "\";");
            ajax_message($editThread, false, "", get_phrase("forums_edit_thread"));
        }

        break;
    case "forums_get_reply":
        globalize("post", array( "postid" => "INT", "threadid" => "INT", "forumid" => "INT" ));
        check_flood("forums_get_reply");
        $post = $TSUE["TSUE_Database"]->query_result("SELECT p.message, t.forumid, t.locked, m.memberid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON(p.memberid=m.memberid) WHERE p.postid = " . $TSUE["TSUE_Database"]->escape($postid) . " AND p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND t.forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        if( !$post ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $message = $TSUE["TSUE_Parser"]->clearTinymceP($post["message"]);
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        if( $post["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts", "canreply_threads" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        ajax_message("[QUOTE=" . strip_tags($post["membername"]) . "|" . $postid . "]" . $message . "[/QUOTE]");
        break;
    case "forums_edit_post":
        globalize("post", array( "postid" => "INT", "threadid" => "INT", "forumid" => "INT" ));
        $post = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.message, p.edit_reason, t.locked FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) WHERE p.postid = " . $TSUE["TSUE_Database"]->escape($postid) . " AND p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND t.forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        if( !$post ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$forumid]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$forumid];
        if( $post["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canEdit = has_forum_permission("canedit_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
        if( !$canEdit ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            globalize("post", array( "message" => "TRIM", "edit_reason" => "TRIM" ));
            $message = $TSUE["TSUE_Parser"]->clearTinymceP($message);
            $strlenOriginalText = strlenOriginalText($message);
            if( $strlenOriginalText < $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_min_post_length"] ) 
            {
                ajax_message(get_phrase("valid_message_error"), "-ERROR-");
            }

            $BuildQuery = array( "message" => $message, "edited_membername" => $TSUE["TSUE_Member"]->info["membername"], "edited_memberid" => $TSUE["TSUE_Member"]->info["memberid"], "edited_date" => TIMENOW, "edit_reason" => ($edit_reason ? $edit_reason : $post["edit_reason"]) );
            $TSUE["TSUE_Database"]->update("tsue_forums_posts", $BuildQuery, "postid=" . $TSUE["TSUE_Database"]->escape($postid));
            $message = $TSUE["TSUE_Parser"]->parse($message);
            $reason_for_editing = get_phrase("last_edited_by", strip_tags($BuildQuery["edited_membername"]), convert_relative_time($BuildQuery["edited_date"]), ($BuildQuery["edit_reason"] ? get_phrase("reason") . " " . strip_tags($BuildQuery["edit_reason"]) : ""));
            eval("\$message .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("show_reason_for_editing") . "\";");
            $attachedFiles = "";
            $TSUE_Forums->prepareAttachmentCache();
            if( isset($TSUE_Forums->attachmentCache[$postid]) ) 
            {
                $canDelete = has_forum_permission("candelete_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_posts", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
                $attachedFiles = $TSUE_Forums->prepareAttachments($TSUE_Forums->attachmentCache[$postid], $postid, $canDelete);
            }

            ajax_message($message . $attachedFiles);
        }
        else
        {
            $upload_button = "";
            if( has_forum_permission("canupload", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
            {
                $content_type = "posts";
                eval("\$upload_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_javascript") . "\";");
                eval("\$upload_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_upload_button_overlay") . "\";");
            }

            $message = html_clean($TSUE["TSUE_Parser"]->clearTinymceP($post["message"]));
            $post_id = $postid;
            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("reason_for_editing") . "\";");
            eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_ajax_editor") . "\";");
            ajax_message($Output, false, "", get_phrase("message_edit"));
        }

        break;
    case "forums_search":
        globalize("post", array( "keywords" => "TRIM", "title_only" => "INT", "membername" => "TRIM", "newer_than" => "TRIM", "forums" => "ARRAY", "result_type" => "TRIM", "this_member_only" => "INT", "min_nr_of_replies" => "INT", "order_by" => "TRIM" ));
        if( !in_array($result_type, array( "threads", "posts" )) ) 
        {
            $result_type = "threads";
        }

        if( !in_array($order_by, array( "most_recent", "most_replies", "most_views" )) ) 
        {
            $order_by = "most_recent";
        }

        $kLength = strlen($keywords);
        $mLength = strlen($membername);
        if( $kLength < 3 && $mLength < $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"] ) 
        {
            ajax_message(get_phrase("message_search_keyword_length"), "-ERROR-");
        }

        check_flood("forums_search");
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !has_permission("cansearch_in_forums") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $threadTitleLooking = $postMessageLooking = "";
        if( 3 <= $kLength ) 
        {
            $threadTitleLooking = "AND " . explodeSearchKeywords("t.title", $keywords);
            if( !$title_only ) 
            {
                $postMessageLooking = "AND " . explodeSearchKeywords("p.message", $keywords);
            }

        }

        $threadMemberSQL = $postMemberSQL = "";
        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"] <= $mLength ) 
        {
            $searchMember = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($membername));
            if( $searchMember ) 
            {
                if( $this_member_only ) 
                {
                    $threadMemberSQL = "AND t.memberid = " . $TSUE["TSUE_Database"]->escape($searchMember["memberid"]);
                }
                else
                {
                    $postMemberSQL = "AND p.memberid = " . $TSUE["TSUE_Database"]->escape($searchMember["memberid"]);
                }

            }
            else
            {
                ajax_message(get_phrase("no_results_found"), "-ERROR-");
            }

        }

        $threadDateCut = $postDateCut = "";
        if( $newer_than && substr_count($newer_than, "/") == 2 && strlen($newer_than) == 10 ) 
        {
            $newer_than = intval(strtotime(str_replace("/", "-", $newer_than)));
            if( $newer_than ) 
            {
                $threadDateCut = "AND t.post_date > " . $TSUE["TSUE_Database"]->escape($newer_than);
                $postDateCut = "AND p.post_date > " . $TSUE["TSUE_Database"]->escape($newer_than);
            }

        }

        $minRepliesCut = "";
        if( $min_nr_of_replies ) 
        {
            $minRepliesCut = "AND t.reply_count >= " . $TSUE["TSUE_Database"]->escape($min_nr_of_replies);
        }

        $forumIN = "";
        if( $forums != "" && $forums != "-1" ) 
        {
            $forums = tsue_explode(",", $forums);
            if( !empty($forums) ) 
            {
                $fids = array(  );
                foreach( $forums as $fid ) 
                {
                    $fid = intval($fid);
                    if( $fid ) 
                    {
                        $fids[] = $fid;
                    }

                }
                if( !empty($fids) ) 
                {
                    $forumIN = " AND t.forumid IN (" . implode(",", $fids) . ")";
                }

            }

        }

        $threadCache = $postCache = array(  );
        if( $threadTitleLooking || $threadMemberSQL || $threadDateCut || $minRepliesCut || $forumIN ) 
        {
            $Query = $TSUE["TSUE_Database"]->query("\r\n\t\t\tSELECT SQL_NO_CACHE t.threadid, f.forumid, f.password\r\n\t\t\tFROM tsue_forums_threads t \r\n\t\t\tINNER JOIN tsue_forums f USING(forumid) \r\n\t\t\tWHERE 1=1 " . $threadTitleLooking . " " . $threadMemberSQL . " " . $threadDateCut . " " . $minRepliesCut . " " . $forumIN);
            if( $TSUE["TSUE_Database"]->num_rows($Query) ) 
            {
                while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($Query) ) 
                {
                    if( isset($TSUE_Forums->availableForums[$Thread["forumid"]]) && has_forum_permission("canview_thread_list", $TSUE_Forums->forumPermissions[$Thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Thread["forumid"], $Thread["password"]) ) 
                    {
                        $threadCache[] = $Thread["threadid"];
                    }

                }
                if( !empty($threadCache) && $result_type == "posts" ) 
                {
                    $Query = $TSUE["TSUE_Database"]->query("\r\n\t\t\t\t\tSELECT SQL_NO_CACHE postid FROM tsue_forums_posts \r\n\t\t\t\t\tWHERE threadid IN (" . implode(",", $threadCache) . ") \r\n\t\t\t\t\tGROUP BY threadid\r\n\t\t\t\t\tORDER BY post_date ASC, postid ASC");
                    if( $TSUE["TSUE_Database"]->num_rows($Query) ) 
                    {
                        while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($Query) ) 
                        {
                            $postCache[] = $Post["postid"];
                        }
                    }

                }

            }

        }

        if( !$title_only && ($postMessageLooking || $postMemberSQL || $postDateCut || $minRepliesCut || $forumIN) ) 
        {
            $Query = $TSUE["TSUE_Database"]->query("\r\n\t\t\tSELECT SQL_NO_CACHE p.postid, t.threadid, f.forumid, f.password\r\n\t\t\tFROM tsue_forums_posts p \r\n\t\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\t\tINNER JOIN tsue_forums f USING(forumid) \r\n\t\t\tWHERE 1=1 " . $postMessageLooking . " " . $postMemberSQL . " " . $postDateCut . " " . $minRepliesCut . " " . $forumIN);
            if( $TSUE["TSUE_Database"]->num_rows($Query) ) 
            {
                while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($Query) ) 
                {
                    if( isset($TSUE_Forums->availableForums[$Post["forumid"]]) && has_forum_permission("canview_thread_list", $TSUE_Forums->forumPermissions[$Post["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Post["forumid"], $Post["password"]) ) 
                    {
                        $postCache[] = $Post["postid"];
                        if( !in_array($Post["threadid"], $threadCache) ) 
                        {
                            $threadCache[] = $Post["threadid"];
                        }

                    }

                }
            }

        }

        if( empty($threadCache) ) 
        {
            ajax_message(get_phrase("no_results_found"), "-ERROR-");
        }

        $BuildQuery = array( "threads" => implode(",", $threadCache), "posts" => implode(",", $postCache), "search_date" => TIMENOW, "search_type" => "search_forums", "result_type" => $result_type, "keywords" => $keywords, "title_only" => $title_only, "order_by" => $order_by );
        $TSUE["TSUE_Database"]->insert("tsue_search", $BuildQuery);
        echo $TSUE["TSUE_Database"]->insert_id();
        exit();
    case "forum_list_selectbox":
        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        ajax_message($TSUE_Forums->prepareForumListMultipleSelectbox("forums_search_multiple_textarea", "cat_content"));
        break;
    case "password_required":
        globalize("post", array( "forumid" => "INT", "password" => "TRIM" ));
        if( !$forumid ) 
        {
            ajax_message(get_phrase("forums_invalid_forum"), "-ERROR-");
        }

        if( $TSUE["do"] == "form" ) 
        {
            eval("\$forums_password_required_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_password_required_form") . "\";");
            ajax_message($forums_password_required_form, "", false, get_phrase("forums_password_required_title"));
        }
        else
        {
            if( !$password ) 
            {
                ajax_message(get_phrase("forums_password_required_error"), "-ERROR-");
            }

            $Forum = $TSUE["TSUE_Database"]->query_result("SELECT password FROM tsue_forums WHERE forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
            if( !$Forum ) 
            {
                ajax_message(get_phrase("forums_invalid_forum"), "-ERROR-");
            }

            if( $Forum["password"] != sha1($password) ) 
            {
                ajax_message(get_phrase("forums_password_required_error"), "-ERROR-");
            }

            cookie_set("tsue_fp" . $forumid, sha1($password));
            exit();
        }

}
function handleQuotedReply($QuoteDetails = "", $quoteContent = "", $threadid = 0, $postid = 0, $threadOwner = 0)
{
    global $TSUE;
    global $quoteCache;
    if( !$QuoteDetails || !$quoteContent || !$threadid || !$postid || !$threadOwner ) 
    {
        return $quoteContent;
    }

    $quoteContent = trim(str_replace("\\\"", "\"", $quoteContent));
    $explode = @tsue_explode("|", @trim($QuoteDetails));
    if( !$explode || !isset($explode["0"]) ) 
    {
        return $quoteContent;
    }

    $MemberName = trim(str_replace("\\\"", "\"", $explode["0"]));
    if( !is_valid_string($MemberName) ) 
    {
        return $quoteContent;
    }

    $searchMember = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($MemberName));
    if( $searchMember && !isset($quoteCache[$searchMember["memberid"]][$threadid][$postid]) && $searchMember["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] && $searchMember["memberid"] != $threadOwner ) 
    {
        $quoteCache[$searchMember["memberid"]][$threadid][$postid] = true;
        alert_member($searchMember["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "thread_posts", $threadid, "reply_quoted", $postid);
    }

    return $quoteContent;
}



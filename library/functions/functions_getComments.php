<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function getComments($content_type, $content_id, $comment_id = 0)
{
    global $TSUE;
    $Comments = "";
    if( $comment_id ) 
    {
        $CommentsQuery = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername, m.gender, g.groupname, g.groupstyle, p.signature \r\n\t\tFROM tsue_comments c \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_member_profile p USING(memberid) \r\n\t\tWHERE c.comment_id = " . $TSUE["TSUE_Database"]->escape($comment_id));
        if( !$TSUE["TSUE_Database"]->num_rows($CommentsQuery) ) 
        {
            $comment_id = 0;
        }

    }

    if( !$comment_id ) 
    {
        $CommentsQuery = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername, m.gender, g.groupname, g.groupstyle, p.signature \r\n\t\tFROM tsue_comments c \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_member_profile p USING(memberid) \r\n\t\tWHERE c.content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND c.content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " \r\n\t\tORDER BY c.comment_id ASC");
    }

    $allCommentsCount = $TSUE["TSUE_Database"]->num_rows($CommentsQuery);
    if( $allCommentsCount ) 
    {
        global $CommentReplies;
        $CommentReplies = array(  );
        $RepliesQuery = $TSUE["TSUE_Database"]->query("SELECT r.*, m.gender, m.membername, g.groupname, g.groupstyle \r\n\t\tFROM tsue_comments_replies r \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE r.content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND r.content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " \r\n\t\tORDER BY r.post_date ASC");
        if( $TSUE["TSUE_Database"]->num_rows($RepliesQuery) ) 
        {
            while( $Reply = $TSUE["TSUE_Database"]->fetch_assoc($RepliesQuery) ) 
            {
                $CommentReplies[$Reply["comment_id"]][] = $Reply;
            }
        }

        global $Likes;
        require_once(REALPATH . "library/classes/class_likes.php");
        $Likes = new TSUE_Likes();
        $Likes->prepareCommentLikesCache($content_id, $content_type);
        global $CanReply;
        $CanReply = has_permission("canpost_comments");
        for( $worked = 0; $Comment = $TSUE["TSUE_Database"]->fetch_assoc($CommentsQuery); $worked++ ) 
        {
            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["website_comments_perpage"] <= $worked ) 
            {
                break;
            }

            $Comments .= prepareCommentList($Comment, $content_type, $content_id);
            $last_comment_id = $Comment["comment_id"];
        }
        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["website_comments_perpage"] < $allCommentsCount ) 
        {
            eval("\$Comments .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("comments_show_more") . "\";");
        }

    }
    else
    {
        eval("\$Comments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("no_comments") . "\";");
    }

    if( has_permission("canpost_comments") && !$comment_id ) 
    {
        eval("\$Comments .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("comments_post") . "\";");
    }

    return $Comments;
}

function prepareCommentList($Comment, $content_type, $content_id)
{
    global $TSUE;
    global $CommentReplies;
    global $Likes;
    global $CanReply;
    $ModerationLinks = $Replies = $LikeLink = $LikeList = "";
    $Comment["message"] = $TSUE["TSUE_Parser"]->parse($Comment["message"]);
    if( isset($CommentReplies[$Comment["comment_id"]]) ) 
    {
        foreach( $CommentReplies[$Comment["comment_id"]] as $Reply ) 
        {
            $Reply["message"] = $TSUE["TSUE_Parser"]->parse($Reply["message"]);
            if( (has_permission("candelete_own_comments") && $Reply["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_comments")) && !is_member_of("unregistered") ) 
            {
                eval("\$ModerationLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_comment_reply") . "\";");
            }

            $_avatar = get_member_avatar($Reply["memberid"], $Reply["gender"], "s");
            $_membername = $Reply["membername"];
            $_memberid = $Reply["memberid"];
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $Reply["post_date"] = convert_relative_time($Reply["post_date"]);
            $Reply["membername"] = getMembername($Reply["membername"], $Reply["groupstyle"]);
            eval("\$Replies .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("comment_replies") . "\";");
        }
    }

    if( $content_type != "report_comments" && $content_type != "application_comments" && $content_type != "file_comments" && $content_type != "staff_messages_comments" ) 
    {
        $LikeList = $Likes->prepareCommentLikes($Comment["comment_id"], $content_type);
        if( isset($Likes->likedThisContent[$Comment["comment_id"]][$TSUE["TSUE_Member"]->info["memberid"]]) ) 
        {
            $LikeLink = $Likes->unlikeButton($Comment["comment_id"], $Comment["memberid"], $content_type, $Comment["content_id"]);
        }
        else
        {
            $LikeLink = $Likes->likeButton($Comment["comment_id"], $Comment["memberid"], $content_type, $Comment["content_id"]);
        }

    }

    $ReplyLink = "";
    if( $CanReply ) 
    {
        $Phrase = get_phrase("button_reply");
        $ReplyLink = "<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/buttons/reply_add.png\" alt=\"" . $Phrase . "\" title=\"" . $Phrase . "\" border=\"0\" class=\"button_images clickable\" />";
    }

    $ModerationLinks = "";
    if( (has_permission("candelete_own_comments") && $Comment["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_comments")) && !is_member_of("unregistered") ) 
    {
        eval("\$ModerationLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_comment") . "\";");
    }

    if( (has_permission("canedit_own_comments") && $Comment["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_comments")) && !is_member_of("unregistered") ) 
    {
        eval("\$ModerationLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_comment") . "\";");
    }

    if( has_permission("canreport") && $content_type != "report_comments" && $content_type != "application_comments" && $content_type != "staff_messages_comments" ) 
    {
        $realContentid = $content_id;
        $content_id = $Comment["comment_id"];
        eval("\$ModerationLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post") . "\";");
        $content_id = $realContentid;
    }

    $_avatar = get_member_avatar($Comment["memberid"], $Comment["gender"], "s");
    $_membername = $Comment["membername"];
    $_memberid = $Comment["memberid"];
    $_alt = "";
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    $Comment["post_date"] = convert_relative_time($Comment["post_date"]);
    $Comment["membername"] = getMembername($Comment["membername"], $Comment["groupstyle"]);
    eval("\$Comments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("comments") . "\";");
    return $Comments;
}

function comments_show_more($last_comment_id, $content_type, $content_id)
{
    global $TSUE;
    $Comments = "";
    $CommentsQuery = $TSUE["TSUE_Database"]->query("SELECT c.*, m.gender, g.groupname, g.groupstyle, p.signature \r\n\tFROM tsue_comments c \r\n\tLEFT JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tLEFT JOIN tsue_member_profile p USING(memberid) \r\n\tWHERE c.comment_id > " . $TSUE["TSUE_Database"]->escape($last_comment_id) . " AND c.content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND c.content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " \r\n\tORDER BY c.comment_id ASC");
    $allCommentsCount = $TSUE["TSUE_Database"]->num_rows($CommentsQuery);
    if( $allCommentsCount ) 
    {
        global $CommentReplies;
        $CommentReplies = array(  );
        $RepliesQuery = $TSUE["TSUE_Database"]->query("SELECT r.*, m.gender, g.groupname, g.groupstyle, p.signature \r\n\t\tFROM tsue_comments_replies r \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_member_profile p USING(memberid) \r\n\t\tWHERE r.content_type = " . $TSUE["TSUE_Database"]->escape($content_type) . " AND r.content_id = " . $TSUE["TSUE_Database"]->escape($content_id) . " \r\n\t\tORDER BY r.post_date ASC");
        if( $TSUE["TSUE_Database"]->num_rows($RepliesQuery) ) 
        {
            while( $Reply = $TSUE["TSUE_Database"]->fetch_assoc($RepliesQuery) ) 
            {
                $CommentReplies[$Reply["comment_id"]][] = $Reply;
            }
        }

        global $Likes;
        require_once(REALPATH . "library/classes/class_likes.php");
        $Likes = new TSUE_Likes();
        $Likes->prepareCommentLikesCache($content_id, $content_type);
        global $CanReply;
        $CanReply = has_permission("canpost_comments");
        for( $worked = 0; $Comment = $TSUE["TSUE_Database"]->fetch_assoc($CommentsQuery); $worked++ ) 
        {
            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["website_comments_perpage"] <= $worked ) 
            {
                break;
            }

            $Comments .= preparecommentlist($Comment, $content_type, $content_id);
            $last_comment_id = $Comment["comment_id"];
        }
        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["website_comments_perpage"] < $allCommentsCount ) 
        {
            eval("\$Comments .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("comments_show_more") . "\";");
        }

    }
    else
    {
        eval("\$Comments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("no_comments") . "\";");
    }

    return $Comments;
}

function getReply($reply_id, $content_type, $content_id)
{
    global $TSUE;
    $RepliesQuery = $TSUE["TSUE_Database"]->query("SELECT r.*, m.gender, g.groupstyle \r\n\tFROM tsue_comments_replies r \r\n\tLEFT JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE r.reply_id = " . $TSUE["TSUE_Database"]->escape($reply_id));
    if( $TSUE["TSUE_Database"]->num_rows($RepliesQuery) ) 
    {
        $ModerationLinks = "";
        $Reply = $TSUE["TSUE_Database"]->fetch_assoc($RepliesQuery);
        $Reply["message"] = $TSUE["TSUE_Parser"]->parse($Reply["message"]);
        if( (has_permission("candelete_own_comments") && $Reply["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_comments")) && !is_member_of("unregistered") ) 
        {
            eval("\$ModerationLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_comment_reply") . "\";");
        }

        $_avatar = get_member_avatar($Reply["memberid"], $Reply["gender"], "s");
        $_membername = $Reply["membername"];
        $_memberid = $Reply["memberid"];
        $_alt = "";
        eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
        $Reply["post_date"] = convert_relative_time($Reply["post_date"]);
        $Reply["membername"] = getMembername($Reply["membername"], $Reply["groupstyle"]);
        eval("\$Reply = \"" . $TSUE["TSUE_Template"]->LoadTemplate("comment_replies") . "\";");
        return $Reply;
    }

}



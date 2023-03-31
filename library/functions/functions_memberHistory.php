<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function prepareIPHistory($memberid = 0)
{
    global $TSUE;
    if( !$memberid || is_member_of("unregistered") || !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    $IPs = $TSUE["TSUE_Database"]->query("SELECT ipaddress FROM tsue_member_ip_history WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
    if( !$TSUE["TSUE_Database"]->num_rows($IPs) ) 
    {
        return get_phrase("no_results_found");
    }

    $List = array(  );
    while( $Row = $TSUE["TSUE_Database"]->fetch_assoc($IPs) ) 
    {
        $ipaddress = trim($Row["ipaddress"]);
        eval("\$List[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ipdetails") . "\";");
    }
    $listOfIP = implode("   ", $List);
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ipdetails_main") . "\";");
    return $Output;
}

function prepareRecentActivity($memberid = 0)
{
    global $TSUE;
    if( !$memberid || !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    $Privacy = $TSUE["TSUE_Database"]->query_result("SELECT allow_view_profile FROM tsue_member_privacy WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
    $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
    $PassiveUser = array( "memberid" => $memberid, "allow_view_profile" => $Privacy["allow_view_profile"] );
    if( !canViewProfile($ActiveUser, $PassiveUser) ) 
    {
        ajax_message(get_phrase("membercp_limited_view"), "-ERROR-");
    }

    $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membername, m.gender, g.groupstyle FROM tsue_members m INNER JOIN tsue_membergroups g USING(membergroupid) WHERE m.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
    if( !$Member ) 
    {
        ajax_message(get_phrase("member_not_found"), "-ERROR-");
    }

    $Membername = getMembername($Member["membername"], $Member["groupstyle"]);
    global $allActivities;
    $allActivities = array(  );
function saveActivity($Date, $Activity)
{
    global $allActivities;
    if( !isset($allActivities[$Date]) ) 
    {
        $allActivities[$Date][] = $Activity;
    }
    else
    {
        while( isset($allActivities[$Date]) ) 
        {
            $Date++;
        }
        $allActivities[$Date][] = $Activity;
    }

}

    $recentProfileComments = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername as receiverName, g.groupstyle as receiverGroupstyle FROM tsue_comments c INNER JOIN tsue_members m ON(c.content_id=m.memberid) INNER JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'profile_comments' ORDER BY c.post_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentProfileComments) ) 
    {
        while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentProfileComments) ) 
        {
            $_memberid = $Comment["content_id"];
            $_membername = getMembername($Comment["receiverName"], $Comment["receiverGroupstyle"]);
            eval("\$member_info_direct_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_direct_link") . "\";");
            $_alt = "";
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Comment["post_date"]);
            $recentActivity = get_phrase("x_posted_a_comment_on_y_profile", $Membername, $member_info_direct_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Comment["post_date"], $Activity);
        }
    }

    $recentProfileCommentReplies = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername as receiverName, g.groupstyle as receiverGroupstyle FROM tsue_comments_replies c INNER JOIN tsue_members m ON(c.content_id=m.memberid) INNER JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'profile_comments' ORDER BY c.post_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentProfileCommentReplies) ) 
    {
        while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentProfileCommentReplies) ) 
        {
            $_memberid = $Comment["content_id"];
            $_membername = getMembername($Comment["receiverName"], $Comment["receiverGroupstyle"]);
            eval("\$member_info_direct_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_direct_link") . "\";");
            $_alt = "";
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Comment["post_date"]);
            $recentActivity = get_phrase("x_posted_a_comment_reply_on_y_profile", $Membername, $member_info_direct_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Comment["post_date"], $Activity);
        }
    }

    $recentTorrentComments = $TSUE["TSUE_Database"]->query("SELECT c.*, t.name as torrentName FROM tsue_comments c INNER JOIN tsue_torrents t ON(c.content_id=t.tid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.content_type = 'torrent_comments' ORDER BY c.post_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentTorrentComments) ) 
    {
        while( $Comment = $TSUE["TSUE_Database"]->fetch_assoc($recentTorrentComments) ) 
        {
            $tid = $Comment["content_id"];
            $torrentName = strip_tags($Comment["torrentName"]);
            eval("\$torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_link") . "\";");
            $_memberid = $memberid;
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Comment["post_date"]);
            $recentActivity = get_phrase("x_posted_a_comment_on_y_torrent", $Membername, $torrent_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Comment["post_date"], $Activity);
        }
    }

    $recentTorrentLikes = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.*, t.name as torrentName FROM tsue_liked_content l INNER JOIN tsue_torrents t ON(l.content_id=t.tid) WHERE l.like_memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND l.content_type = 'torrent' ORDER BY l.like_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentTorrentLikes) ) 
    {
        while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($recentTorrentLikes) ) 
        {
            $tid = $Like["content_id"];
            $torrentName = strip_tags($Like["torrentName"]);
            eval("\$torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_link") . "\";");
            $_memberid = $memberid;
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Like["like_date"]);
            $recentActivity = get_phrase("x_liked_torrent_y", $Membername, $torrent_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Like["like_date"], $Activity);
        }
    }

    $recentProfileLikes = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.*, m.membername as receiverName, g.groupstyle as receiverGroupstyle FROM tsue_liked_content l INNER JOIN tsue_members m ON(l.content_memberid=m.memberid) INNER JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) WHERE l.like_memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND l.content_type = 'profile_comments' ORDER BY l.like_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentProfileLikes) ) 
    {
        while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($recentProfileLikes) ) 
        {
            $_memberid = $Like["content_memberid"];
            $_membername = getMembername($Like["receiverName"], $Like["receiverGroupstyle"]);
            eval("\$member_info_direct_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_direct_link") . "\";");
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Like["like_date"]);
            $recentActivity = get_phrase("x_liked_a_comment_on_y_profile", $Membername, $member_info_direct_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Like["like_date"], $Activity);
        }
    }

    $recentTorrentCommentLikes = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.*, t.name as torrentName FROM tsue_liked_content l INNER JOIN tsue_torrents t ON(l.content_id=t.tid) WHERE l.like_memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND l.content_type = 'torrent_comments' ORDER BY l.like_date DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentTorrentCommentLikes) ) 
    {
        while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($recentTorrentCommentLikes) ) 
        {
            $tid = $Like["content_id"];
            $torrentName = strip_tags($Like["torrentName"]);
            eval("\$torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_link") . "\";");
            $_memberid = $memberid;
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($Like["like_date"]);
            $recentActivity = get_phrase("x_liked_a_comment_on_y_torrent", $Membername, $torrent_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($Like["like_date"], $Activity);
        }
    }

    $recentRequests = $TSUE["TSUE_Database"]->query("SELECT title, added FROM tsue_requests WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY added DESC LIMIT 3");
    if( $TSUE["TSUE_Database"]->num_rows($recentRequests) ) 
    {
        while( $recentRequest = $TSUE["TSUE_Database"]->fetch_assoc($recentRequests) ) 
        {
            $Request = strip_tags(substr($recentRequest["title"], 0, 85));
            eval("\$request_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("request_link") . "\";");
            $_memberid = $memberid;
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            $post_date = convert_relative_time($recentRequest["added"]);
            $recentActivity = get_phrase("x_requested_a_torrent_y", $Membername, $request_link);
            eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
            saveActivity($recentRequest["added"], $Activity);
        }
    }

    require(REALPATH . "/library/classes/class_forums.php");
    $TSUE_Forums = new forums();
    $forumThreads = $TSUE["TSUE_Database"]->query("SELECT t.threadid, t.forumid, t.title, t.post_date, f.title as forumtitle, f.password FROM tsue_forums_threads t LEFT JOIN tsue_forums f USING(forumid) WHERE t.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY t.post_date DESC LIMIT 3");
    if( $TSUE_Forums->forumCategories && $TSUE["TSUE_Database"]->num_rows($forumThreads) ) 
    {
        while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($forumThreads) ) 
        {
            if( isset($TSUE_Forums->availableForums[$Thread["forumid"]]) && has_forum_permission("canview_thread_list", $TSUE_Forums->forumPermissions[$Thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Thread["forumid"], $Thread["password"]) ) 
            {
                $Thread["title"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Thread["forumid"] . "&amp;tid=" . $Thread["threadid"] . "\">" . strip_tags($Thread["title"]) . "</a>";
                $Thread["forumtitle"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Thread["forumid"] . "\">" . strip_tags($Thread["forumtitle"]) . "</a>";
                $_memberid = $memberid;
                $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                $post_date = convert_relative_time($Thread["post_date"]);
                $recentActivity = get_phrase("x_created_a_thread_in_y", $Membername, $Thread["title"], $Thread["forumtitle"]);
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
                saveActivity($Thread["post_date"], $Activity);
            }

        }
    }

    $forumPosts = $TSUE["TSUE_Database"]->query("SELECT p.postid, p.threadid, p.post_date, t.forumid, t.title, f.title as forumtitle, f.password FROM tsue_forums_posts p LEFT JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_forums f USING(forumid) WHERE p.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY p.post_date DESC LIMIT 3");
    if( $TSUE_Forums->forumCategories && $TSUE["TSUE_Database"]->num_rows($forumPosts) ) 
    {
        while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($forumPosts) ) 
        {
            if( isset($TSUE_Forums->availableForums[$Post["forumid"]]) && has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$Post["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $TSUE_Forums->checkForumPassword($Post["forumid"], $Post["password"]) ) 
            {
                $Post["title"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Post["forumid"] . "&amp;tid=" . $Post["threadid"] . "&amp;postid=" . $Post["postid"] . "\">" . strip_tags($Post["title"]) . "</a>";
                $Post["forumtitle"] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $Post["forumid"] . "\">" . strip_tags($Post["forumtitle"]) . "</a>";
                $_memberid = $memberid;
                $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "s");
                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                $post_date = convert_relative_time($Post["post_date"]);
                $recentActivity = get_phrase("x_replied_to_thread_y_in_z", $Membername, $Post["title"], $Post["forumtitle"]);
                eval("\$Activity = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_activity") . "\";");
                saveActivity($Post["post_date"], $Activity);
            }

        }
    }

    if( $allActivities ) 
    {
        $HTML = "";
        krsort($allActivities, SORT_NUMERIC);
        foreach( $allActivities as $dateTime => $Activity ) 
        {
            $HTML .= $Activity["0"];
        }
        ajax_message($HTML);
        return NULL;
    }
    else
    {
        ajax_message(get_phrase("no_recent_activity_for_x", $Membername));
    }

}

function prepareFollowing($memberid = 0)
{
}

function prepareFollowered($memberid = 0)
{
}

function prepareWarns($memberid = 0, $ajaxMessage = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    $globalWarn = $TSUE["TSUE_Database"]->query_result("SELECT warned as autoWarnedDate FROM tsue_auto_warning WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
    $MemberWarns = $TSUE["TSUE_Database"]->query("SELECT w.*, m.membername, g.groupstyle FROM tsue_member_warns w LEFT JOIN tsue_members m ON(w.warned_by=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE w.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY w.warn_date DESC");
    if( !$TSUE["TSUE_Database"]->num_rows($MemberWarns) && !$globalWarn ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }
    else
    {
        $warnList = "";
        $count = 0;
        if( $globalWarn ) 
        {
            $_memberid = 0;
            $_membername = get_phrase("auto_warning_System");
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            $Warn["warn_date"] = convert_relative_time($globalWarn["autoWarnedDate"]);
            $Warn["end_date"] = convert_time($globalWarn["autoWarnedDate"] + $TSUE["TSUE_Settings"]->settings["auto_warning"]["warn_length"] * 24 * 60 * 60);
            $Warn["notes"] = get_phrase("auto_warn_notes");
            eval("\$warnList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("warns_list") . "\";");
            $count++;
        }

        while( $Warn = $TSUE["TSUE_Database"]->fetch_assoc($MemberWarns) ) 
        {
            $_memberid = $Warn["warned_by"];
            $_membername = getMembername($Warn["membername"], $Warn["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            $Warn["warn_date"] = convert_relative_time($Warn["warn_date"]);
            $Warn["end_date"] = ($Warn["end_date"] ? convert_time($Warn["end_date"]) : get_phrase("warns_end_date_never"));
            eval("\$warnList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("warns_list") . "\";");
            $count++;
        }
        eval("\$warns_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("warns_table") . "\";");
        if( $ajaxMessage ) 
        {
            ajax_message($warns_table, "", false, get_phrase("warns_title", $TSUE["TSUE_Member"]->info["membername"]));
        }
        else
        {
            return $warns_table;
        }

    }

}

function prepareMutes($memberid = 0, $ajaxMessage = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    $MemberMutes = $TSUE["TSUE_Database"]->query("SELECT w.*, m.membername, p.muted, g.groupstyle FROM tsue_member_mutes w LEFT JOIN tsue_members m ON(w.muted_by=m.memberid) LEFT JOIN tsue_member_profile p ON(w.memberid=p.memberid) LEFT JOIN tsue_membergroups g ON(m.membergroupid=g.membergroupid) WHERE w.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY w.mute_date DESC");
    if( !$TSUE["TSUE_Database"]->num_rows($MemberMutes) ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }
    else
    {
        $muteList = "";
        for( $count = 0; $Mute = $TSUE["TSUE_Database"]->fetch_assoc($MemberMutes); $count++ ) 
        {
            $Mute["notes"] = strip_tags($Mute["notes"]) . listMutes($Mute["muted"]);
            $_memberid = $Mute["muted_by"];
            $_membername = getMembername($Mute["membername"], $Mute["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            $Mute["mute_date"] = convert_relative_time($Mute["mute_date"]);
            $Mute["end_date"] = ($Mute["end_date"] ? convert_time($Mute["end_date"]) : get_phrase("mutes_end_date_never"));
            eval("\$muteList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("mutes_list") . "\";");
        }
        eval("\$mutes_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("mutes_table") . "\";");
        if( $ajaxMessage ) 
        {
            ajax_message($mutes_table, "", false, get_phrase("mutes_title", $TSUE["TSUE_Member"]->info["membername"]));
        }
        else
        {
            return $mutes_table;
        }

    }

}

function prepareHitRunWarns($memberid = 0, $ajaxMessage = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    if( getSetting("xbt", "active") ) 
    {
        $hitRuns = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.active, p.uploaded as total_uploaded, t.tid, t.name, t.size, t.options FROM xbt_files_users p INNER JOIN tsue_torrents t ON(p.fid=t.tid) WHERE p.uid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND p.isWarned = 1 ORDER BY p.mtime DESC");
    }
    else
    {
        $hitRuns = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.active, p.total_uploaded, t.tid, t.name, t.size, t.options FROM tsue_torrents_peers p INNER JOIN tsue_torrents t USING(tid) WHERE p.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND p.isWarned = 1 ORDER BY p.last_updated DESC");
    }

    $memberTotalHitRuns = $TSUE["TSUE_Database"]->num_rows($hitRuns);
    profileUpdate($memberid, array( "hitRuns" => $memberTotalHitRuns ));
    if( !$memberTotalHitRuns ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }
    else
    {
        $hitrun_list = "";
        for( $count = 0; $History = $TSUE["TSUE_Database"]->fetch_assoc($hitRuns); $count++ ) 
        {
            $torrentOptions = unserialize($History["options"]);
            $History["ratio"] = member_ratio($History["total_uploaded"], $History["size"]);
            $History["name"] = strip_tags($History["name"]);
            $History["size"] = friendly_size($History["size"]);
            $History["uploaded"] = friendly_size($History["total_uploaded"]);
            $History["hitRunRatio"] = (isset($torrentOptions["hitRunRatio"]) ? 0 + $torrentOptions["hitRunRatio"] : 0);
            $Image = array( "src" => getImagesFullURL() . "member_profile/inactive.png", "alt" => "", "title" => "", "class" => "middle", "id" => "", "rel" => "" );
            if( $History["active"] == 1 ) 
            {
                $Image["src"] = getImagesFullURL() . "member_profile/active.png";
            }

            $History["active"] = getImage($Image);
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$hitrun_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("hitrun_list") . "\";");
        }
        eval("\$hitrun_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("hitrun_table") . "\";");
        if( $ajaxMessage ) 
        {
            ajax_message($hitrun_table, "", false, get_phrase("stats_hitrun_warns"));
        }
        else
        {
            return $hitrun_table;
        }

    }

}

function prepareDownloadHistory($memberid = 0, $ajaxMessage = true, $useTabs = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    if( getSetting("xbt", "active") ) 
    {
        $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.announced, p.left, p.mtime as last_updated, p.active, p.port, p.uploaded as total_uploaded, p.downloaded as total_downloaded, p.up_rate as upload_speed, p.down_rate as download_speed, t.tid, t.name, t.size, t.leechers, t.seeders, t.times_completed, t.options, t.owner FROM xbt_files_users p INNER JOIN tsue_torrents t ON(p.fid=t.tid) WHERE p.uid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY p.mtime DESC");
    }
    else
    {
        $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE SUM(p.announced) AS announced, SUM(p.left) AS `left`, MAX(p.last_updated) AS last_updated, MAX(p.active) AS active, p.port, SUM(p.total_uploaded) AS total_uploaded, SUM(p.total_downloaded) AS total_downloaded, SUM(p.upload_speed) AS upload_speed, SUM(p.download_speed) AS download_speed, t.tid, t.name, t.size, t.leechers, t.seeders, t.times_completed, t.options, t.owner FROM tsue_torrents_peers p INNER JOIN tsue_torrents t USING(tid) WHERE p.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " GROUP BY p.tid, p.memberid ORDER BY p.active DESC, p.last_updated DESC");
    }

    if( !$TSUE["TSUE_Database"]->num_rows($downloadHistory) ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }

    $TSUE["TSUE_Settings"]->loadSettings("hitrun_settings");
    $hitRunActive = getSetting("hitrun_settings", "active");
    $hitRunAnnounceLimit = getSetting("hitrun_settings", "announceLimit");
    $hitRunskipMembergroups = explode(",", getSetting("hitrun_settings", "skipMembergroups"));
    $historyList = "";
    $activeTorrentsCount = $inactiveTorrentsCount = 0;
    $activeTorrents = $inactiveTorrents = array(  );
    while( $History = $TSUE["TSUE_Database"]->fetch_assoc($downloadHistory) ) 
    {
        $isActive = $History["active"] == 1;
        $torrentOptions = unserialize($History["options"]);
        $announceInterval = (getSetting("xbt", "active") ? getSetting("xbt", "announce_interval") : getSetting("global_settings", "announce_interval"));
        $badSeed = "";
        if( $hitRunActive && !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $hitRunskipMembergroups) && $TSUE["TSUE_Member"]->info["memberid"] != $History["owner"] && $History["announced"] < $hitRunAnnounceLimit ) 
        {
            $requiredSeedLimit = secondsToHours($hitRunAnnounceLimit * $announceInterval);
            $Image = array( "src" => getImagesFullURL() . "status/exclamation.png", "alt" => get_phrase("you_must_seed_this_torrent_at_least_x_hours", $requiredSeedLimit), "title" => get_phrase("you_must_seed_this_torrent_at_least_x_hours", $requiredSeedLimit), "class" => "middle", "id" => "", "rel" => "" );
            $badSeed = getImage($Image);
        }

        $badRatio = "";
        if( isset($torrentOptions["hitRunRatio"]) && $torrentOptions["hitRunRatio"] && $TSUE["TSUE_Member"]->info["memberid"] != $History["owner"] ) 
        {
            $torrentOptions["hitRunRatio"] = 0 + $torrentOptions["hitRunRatio"];
            if( member_ratio($History["total_uploaded"], $History["total_downloaded"], true) < $torrentOptions["hitRunRatio"] ) 
            {
                $Image = array( "src" => getImagesFullURL() . "status/exclamation.png", "alt" => get_phrase("torrent_requires_x_seed_ratio", $torrentOptions["hitRunRatio"]), "title" => get_phrase("torrent_requires_x_seed_ratio", $torrentOptions["hitRunRatio"]), "class" => "middle", "id" => "", "rel" => "" );
                $badRatio = getImage($Image);
            }

        }

        $fullName = strip_tags($History["name"]);
        $History["ratio"] = member_ratio($History["total_uploaded"], $History["total_downloaded"]);
        $History["name"] = substr($fullName, 0, 30);
        $History["size"] = friendly_size($History["size"]);
        $History["uploaded"] = friendly_size($History["total_uploaded"]);
        $History["upload_speed"] = friendly_size($History["upload_speed"]);
        $History["downloaded"] = friendly_size($History["total_downloaded"]);
        $History["download_speed"] = friendly_size($History["download_speed"]);
        $totalSeedTime = secondsToHours($History["announced"] * $announceInterval);
        $History["announced"] = get_phrase("torrents_peer_x_times", friendly_number_format($History["announced"]));
        $Image = array( "src" => getImagesFullURL() . "member_profile/" . ((!$isActive ? "in" : "")) . "active.png", "alt" => "", "title" => "", "class" => "middle", "id" => "", "rel" => "" );
        $History["active"] = getImage($Image);
        $History["last_updated"] = convert_relative_time($History["last_updated"]);
        if( $isActive ) 
        {
            $tdClass = ($activeTorrentsCount % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$activeTorrents[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("history_list") . "\";");
            $activeTorrentsCount++;
        }
        else
        {
            $tdClass = ($inactiveTorrentsCount % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$inactiveTorrents[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("history_list") . "\";");
            $inactiveTorrentsCount++;
        }

    }
    if( !$activeTorrents ) 
    {
        if( !$useTabs ) 
        {
            $activeTorrents = "";
        }
        else
        {
            $activeTorrents = get_phrase("you_have_no_any_active_torrent_yet");
        }

    }
    else
    {
        $historyList = implode("", $activeTorrents);
        eval("\$activeTorrents = \"" . $TSUE["TSUE_Template"]->LoadTemplate("history_table") . "\";");
    }

    if( !$inactiveTorrents ) 
    {
        if( !$useTabs ) 
        {
            $inactiveTorrents = "";
        }
        else
        {
            $inactiveTorrents = get_phrase("you_have_no_any_inactive_torrent_yet");
        }

    }
    else
    {
        $historyList = implode("", $inactiveTorrents);
        eval("\$inactiveTorrents = \"" . $TSUE["TSUE_Template"]->LoadTemplate("history_table") . "\";");
    }

    if( !$useTabs ) 
    {
        $Output = $activeTorrents . $inactiveTorrents;
    }
    else
    {
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("history") . "\";");
    }

    if( $ajaxMessage ) 
    {
        ajax_message($Output, NULL, true, get_phrase("your_viewing_your_dl_ul_history"));
    }
    else
    {
        return $Output;
    }

}

function prepareUploadHistory($memberid = 0, $ajaxMessage = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    $uploadHistory = $TSUE["TSUE_Database"]->query("SELECT tid, name, size, seeders, leechers, times_completed FROM tsue_torrents WHERE owner = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY added DESC");
    if( !$TSUE["TSUE_Database"]->num_rows($uploadHistory) ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }

    $historyList = "";
    for( $count = 0; $History = $TSUE["TSUE_Database"]->fetch_assoc($uploadHistory); $count++ ) 
    {
        $History["name"] = strip_tags($History["name"]);
        $History["size"] = friendly_size($History["size"]);
        $History["seeders"] = friendly_number_format($History["seeders"]);
        $History["leechers"] = friendly_number_format($History["leechers"]);
        $History["times_completed"] = friendly_number_format($History["times_completed"]);
        $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
        eval("\$historyList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_history_list") . "\";");
    }
    eval("\$history_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_history_table") . "\";");
    if( $ajaxMessage ) 
    {
        ajax_message($history_table, NULL, true, get_phrase("history_link"));
    }
    else
    {
        return $history_table;
    }

}

function prepareSubscriptions($memberid = 0, $returnMessage = false)
{
    global $TSUE;
    if( is_member_of("unregistered") && $returnMessage ) 
    {
        return get_phrase("permission_denied");
    }

    $memberUpgrades = $TSUE["TSUE_Database"]->query("SELECT p.start_date, p.expiry_date, p.active, u.upgrade_title, u.upgrade_length, u.upgrade_length_type, u.upgrade_price, u.upgrade_currency, t.txn_id, t.amount, t.currency, a.title as processor FROM tsue_member_upgrades_promotions p LEFT JOIN tsue_member_upgrades u USING(upgrade_id) LEFT JOIN tsue_member_upgrades_transaction t USING(txn_id) LEFT JOIN tsue_member_upgrades_api a USING(api_id) WHERE p.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " GROUP BY txn_id ORDER BY p.active DESC, p.expiry_date");
    if( !$TSUE["TSUE_Database"]->num_rows($memberUpgrades) ) 
    {
        if( $returnMessage ) 
        {
            return get_phrase("no_results_found");
        }

    }
    else
    {
        $upgradeList = "";
        for( $count = 0; $Upgrade = $TSUE["TSUE_Database"]->fetch_assoc($memberUpgrades); $count++ ) 
        {
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            if( $Upgrade["upgrade_length_type"] == "lifetime" ) 
            {
                $Upgrade["upgrade_length"] = "";
                $Upgrade["upgrade_length_type"] = get_phrase("upgrade_lifetime");
            }
            else
            {
                if( $Upgrade["upgrade_length_type"] ) 
                {
                    $Upgrade["upgrade_length_type"] = get_phrase("upgrade_" . $Upgrade["upgrade_length_type"] . ((1 < $Upgrade["upgrade_length"] ? "s" : "")));
                }

            }

            if( !$Upgrade["txn_id"] ) 
            {
                $Upgrade["txn_id"] = md5($Upgrade["start_date"] . $Upgrade["expiry_date"]);
            }

            if( $Upgrade["amount"] ) 
            {
                $upradePrice = get_phrase("upgrade_price", $Upgrade["amount"], strtoupper($Upgrade["currency"]), $Upgrade["upgrade_length"], $Upgrade["upgrade_length_type"]);
            }
            else
            {
                if( $Upgrade["upgrade_price"] ) 
                {
                    $upradePrice = get_phrase("upgrade_price", $Upgrade["upgrade_price"], strtoupper($Upgrade["upgrade_currency"]), $Upgrade["upgrade_length"], $Upgrade["upgrade_length_type"]);
                }
                else
                {
                    $upradePrice = "[N/A]";
                }

            }

            $Upgrade["start_date"] = convert_relative_time($Upgrade["start_date"]);
            if( !$Upgrade["active"] || $Upgrade["expiry_date"] <= TIMENOW ) 
            {
                $Upgrade["expiry_date"] = get_phrase("upgrade_expired");
            }
            else
            {
                $Upgrade["expiry_date"] = convert_time($Upgrade["expiry_date"]);
            }

            eval("\$upgradeList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("upgrade_member_upgrades_list") . "\";");
        }
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upgrade_member_upgrades_table") . "\";");
        return $Output;
    }

}

function prepareStats($memberid = 0, $ajaxMessage = true)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }
        else
        {
            return get_phrase("permission_denied");
        }

    }

    $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.*, p.* FROM tsue_members m INNER JOIN tsue_member_profile p USING(memberid) WHERE m.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
    if( !$Member ) 
    {
        if( $ajaxMessage ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }
        else
        {
            return get_phrase("no_results_found");
        }

    }

    $_uploaded = friendly_size($Member["uploaded"]);
    $_downloaded = friendly_size($Member["downloaded"]);
    $_buffer = ($Member["downloaded"] < $Member["uploaded"] ? friendly_size($Member["uploaded"] - $Member["downloaded"]) : 0);
    $_ratio = member_ratio($Member["uploaded"], $Member["downloaded"]);
    $_points = friendly_number_format($Member["points"]);
    $_total_posts = friendly_number_format($Member["total_posts"]);
    $_invites_left = friendly_number_format($Member["invites_left"]);
    $_total_warns = friendly_number_format($Member["total_warns"]);
    eval("\$member_stats = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_stats") . "\";");
    return $member_stats;
}

function prepareStaffNotes($memberid = 0)
{
    global $TSUE;
    if( is_member_of("unregistered") ) 
    {
        return get_phrase("permission_denied");
    }

    $Notes = $TSUE["TSUE_Database"]->query("SELECT n.*, m.membername, g.groupstyle FROM tsue_member_staff_notes n LEFT JOIN tsue_members m ON(n.staffid=m.memberid) LEFT JOIN tsue_membergroups g ON(m.membergroupid=g.membergroupid) WHERE n.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY n.added DESC");
    if( !$TSUE["TSUE_Database"]->num_rows($Notes) ) 
    {
        return get_phrase("no_results_found");
    }

    $count = 0;
    for( $staff_notes_list = ""; $Note = $TSUE["TSUE_Database"]->fetch_assoc($Notes); $count++ ) 
    {
        $_memberid = $Note["staffid"];
        $_membername = getMembername($Note["membername"], $Note["groupstyle"]);
        eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
        $Note["added"] = convert_relative_time($Note["added"]);
        $Note["note"] = html_clean($Note["note"]);
        $deleteLink = "";
        if( has_permission("candelete_staff_notes") ) 
        {
            eval("\$deleteLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_delete_a_note_link") . "\";");
        }

        eval("\$staff_notes_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_notes_list") . "\";");
    }
    eval("\$staff_notes_list_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_notes_list_table") . "\";");
    return $staff_notes_list_table;
}



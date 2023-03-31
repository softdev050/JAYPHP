<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "topten.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("topten");
$Page_Title = get_phrase("navigation_top10");
$Output = "";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_top10") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=topten&amp;pid=" . PAGEID ));
if( !($Output = $TSUE["TSUE_Cache"]->readCache("topten")) ) 
{
    $Torrents = "";
    $mostSnatchedTorrentsRows = "";
    $getTorrents = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE tid, name, leechers, seeders, times_completed \r\n\tFROM tsue_torrents \r\n\tWHERE times_completed > 0 \r\n\tORDER BY times_completed DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getTorrents) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getTorrents) ) 
        {
            $Data["name"] = strip_tags($Data["name"]);
            if( 55 < strlen($Data["name"]) ) 
            {
                $Data["name"] = substr($Data["name"], 0, 55) . "...";
            }

            $Data["times_completed"] = friendly_number_format($Data["times_completed"]);
            $Data["seeders"] = friendly_number_format($Data["seeders"]);
            $Data["leechers"] = friendly_number_format($Data["leechers"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$mostSnatchedTorrentsRows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_snatched_torrents_rows") . "\";");
        }
        eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_snatched_torrents_table") . "\";");
        unset($mostSnatchedTorrentsRows);
    }

    if( getSetting("xbt", "active") ) 
    {
        $getTorrents = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.tid, t.name, t.leechers, t.seeders, t.times_completed, SUM(p.downloaded) AS data \r\n\t\tFROM tsue_torrents t \r\n\t\tLEFT JOIN xbt_files_users p ON(p.fid=t.tid) \r\n\t\tWHERE t.times_completed > 0 \r\n\t\tGROUP BY tid ORDER BY data DESC LIMIT 10");
    }
    else
    {
        $getTorrents = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.tid, t.name, t.leechers, t.seeders, t.times_completed, SUM(p.total_downloaded) AS data \r\n\t\tFROM tsue_torrents t \r\n\t\tLEFT JOIN tsue_torrents_peers p USING(tid) \r\n\t\tWHERE t.times_completed > 0 \r\n\t\tGROUP BY tid ORDER BY data DESC LIMIT 10");
    }

    $mostDataTransferedTorrentsRows = "";
    if( $TSUE["TSUE_Database"]->num_rows($getTorrents) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getTorrents) ) 
        {
            $Data["name"] = strip_tags($Data["name"]);
            if( 40 < strlen($Data["name"]) ) 
            {
                $Data["name"] = substr($Data["name"], 0, 40) . "...";
            }

            $Data["data"] = friendly_size($Data["data"]);
            $Data["times_completed"] = friendly_number_format($Data["times_completed"]);
            $Data["seeders"] = friendly_number_format($Data["seeders"]);
            $Data["leechers"] = friendly_number_format($Data["leechers"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$mostDataTransferedTorrentsRows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_data_transfered_torrents_rows") . "\";");
        }
        eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_data_transfered_torrents_table") . "\";");
        unset($mostDataTransferedTorrentsRows);
    }

    $mostLikedTorrentsRows = "";
    $getTorrents = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE COUNT(l.content_id) AS like_count, t.tid, t.name \r\n\tFROM `tsue_liked_content` l \r\n\tINNER JOIN tsue_torrents t ON (l.content_id=t.tid) \r\n\tWHERE l.content_type = 'torrent' \r\n\tGROUP BY l.content_id ORDER BY like_count DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getTorrents) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getTorrents) ) 
        {
            $Data["name"] = strip_tags($Data["name"]);
            if( $Data["name"] ) 
            {
                if( 65 < strlen($Data["name"]) ) 
                {
                    $Data["name"] = substr($Data["name"], 0, 65) . "...";
                }

                $Data["like_count"] = get_phrase("like_x_people_like_this", friendly_number_format($Data["like_count"]));
                $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
                $Count++;
                eval("\$mostLikedTorrentsRows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_liked_torrents_rows") . "\";");
            }

        }
        eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_liked_torrents_table") . "\";");
        unset($mostLikedTorrentsRows);
    }

    if( !$Torrents ) 
    {
        $Torrents = get_phrase("message_nothing_found");
    }

    $Members = "";
    $bestUploaders = "";
    $getMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid, m.membername, m.joindate, p.uploaded, p.downloaded, g.groupstyle \r\n\tFROM tsue_members m \r\n\tINNER JOIN tsue_member_profile p USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE p.uploaded > 0 \r\n\tORDER BY p.uploaded DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getMembers) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getMembers) ) 
        {
            $_memberid = $Data["memberid"];
            $_membername = getMembername($Data["membername"], $Data["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $Data["ratio"] = member_ratio($Data["uploaded"], $Data["downloaded"]);
            $Data["uploaded"] = friendly_size($Data["uploaded"]);
            $Data["downloaded"] = friendly_size($Data["downloaded"]);
            $Data["joindate"] = convert_relative_time($Data["joindate"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$bestUploaders .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_uploaders_rows") . "\";");
        }
        eval("\$Members .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_uploaders_table") . "\";");
        unset($bestUploaders);
    }

    $bestDownloaders = "";
    $getMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid, m.membername, m.joindate, p.uploaded, p.downloaded, g.groupstyle \r\n\tFROM tsue_members m \r\n\tINNER JOIN tsue_member_profile p USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE p.downloaded > 0 \r\n\tORDER BY p.downloaded DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getMembers) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getMembers) ) 
        {
            $_memberid = $Data["memberid"];
            $_membername = getMembername($Data["membername"], $Data["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $Data["ratio"] = member_ratio($Data["uploaded"], $Data["downloaded"]);
            $Data["joindate"] = convert_relative_time($Data["joindate"]);
            $Data["downloaded"] = friendly_size($Data["downloaded"]);
            $Data["uploaded"] = friendly_size($Data["uploaded"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$bestDownloaders .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_downloaders_rows") . "\";");
        }
        eval("\$Members .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_downloaders_table") . "\";");
    }

    if( !$Members ) 
    {
        $Members = get_phrase("message_nothing_found");
    }

    $Forums = "";
    $bestPosters = "";
    $getMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid, m.membername, m.joindate, p.total_posts, g.groupstyle \r\n\tFROM tsue_members m \r\n\tINNER JOIN tsue_member_profile p USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE p.total_posts > 0 \r\n\tORDER BY p.total_posts DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getMembers) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getMembers) ) 
        {
            $_memberid = $Data["memberid"];
            $_membername = getMembername($Data["membername"], $Data["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $Data["joindate"] = convert_relative_time($Data["joindate"]);
            $Data["total_posts"] = friendly_number_format($Data["total_posts"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$bestPosters .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_posters_rows") . "\";");
        }
        eval("\$Forums .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_best_posters_table") . "\";");
        unset($bestPosters);
    }

    $mostLikedPosts = "";
    $getPosts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE COUNT(l.content_id) AS like_count, p.postid, p.threadid, t.forumid, t.title \r\n\tFROM tsue_liked_content l \r\n\tINNER JOIN tsue_forums_posts p ON (l.content_id=p.postid) \r\n\tINNER JOIN tsue_forums_threads t USING (threadid) \r\n\tWHERE l.content_type = 'thread_posts' \r\n\tGROUP BY l.content_id ORDER BY like_count DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getPosts) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getPosts) ) 
        {
            $Data["title"] = strip_tags($Data["title"]);
            if( 55 < strlen($Data["title"]) ) 
            {
                $Data["title"] = substr($Data["title"], 0, 55) . "...";
            }

            $Data["like_count"] = get_phrase("like_x_people_like_this", friendly_number_format($Data["like_count"]));
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$mostLikedPosts .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_liked_posts_rows") . "\";");
        }
        eval("\$Forums .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_liked_posts_table") . "\";");
        unset($mostLikedPosts);
    }

    $mostViewedThreads = "";
    $getThreads = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE threadid, forumid, title, view_count \r\n\tFROM tsue_forums_threads \r\n\tORDER BY view_count DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getThreads) ) 
    {
        $Count = 0;
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getThreads) ) 
        {
            $Data["title"] = strip_tags($Data["title"]);
            if( 55 < strlen($Data["title"]) ) 
            {
                $Data["title"] = substr($Data["title"], 0, 55) . "...";
            }

            $Data["view_count"] = friendly_number_format($Data["view_count"]);
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$mostViewedThreads .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_viewed_threads_rows") . "\";");
        }
        eval("\$Forums .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_viewed_threads_table") . "\";");
        unset($mostViewedThreads);
    }

    if( !$Forums ) 
    {
        $Forums = get_phrase("message_nothing_found");
    }

    $Awards = "";
    $getAwards = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE COUNT(a.memberid) as totalAwards, a.memberid, m.membername, g.groupstyle \r\n\tFROM tsue_awards_members a \r\n\tINNER JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tGROUP BY memberid ORDER BY totalAwards DESC LIMIT 10");
    if( $TSUE["TSUE_Database"]->num_rows($getAwards) ) 
    {
        $queryCache = $memberids = array(  );
        while( $Data = $TSUE["TSUE_Database"]->fetch_assoc($getAwards) ) 
        {
            $queryCache[] = $Data;
            $memberids[] = $Data["memberid"];
        }
        require_once(REALPATH . "/library/classes/class_awards.php");
        $TSUE_Awards = new TSUE_Awards($memberids);
        unset($memberids);
        $most_awarded_members = "";
        $Count = 0;
        foreach( $queryCache as $Data ) 
        {
            $memberAwards = $TSUE_Awards->getMemberAwards($Data["memberid"]);
            $got_x_awards = get_phrase("got_x_awards", friendly_number_format($Data["totalAwards"]));
            $_memberid = $Data["memberid"];
            $_membername = getMembername($Data["membername"], $Data["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
            $Count++;
            eval("\$most_awarded_members .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_awarded_members_row") . "\";");
        }
        eval("\$Awards .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top10_most_awarded_members_table") . "\";");
        unset($bestPosters);
        unset($queryCache);
    }

    if( !$Awards ) 
    {
        $Awards = get_phrase("message_nothing_found");
    }

    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("topten") . "\";");
    unset($Torrents);
    unset($Members);
    unset($Forums);
    $TSUE["TSUE_Cache"]->saveCache("topten", $Output);
}

PrintOutput($Output, $Page_Title);


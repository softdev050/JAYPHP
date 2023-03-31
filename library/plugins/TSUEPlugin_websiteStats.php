<?php 
function TSUEPlugin_websiteStats($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    global $TSUE_Forums;
    $cacheName = "TSUEPlugin_websiteStats_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("websiteStatsList");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($website_stats = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $threads = $replies = 0;
        if( !isset($TSUE_Forums->availableForums) || !$TSUE_Forums->availableForums ) 
        {
            require_once(REALPATH . "/library/classes/class_forums.php");
            $TSUE_Forums = new forums(true);
        }

        if( isset($TSUE_Forums->availableForums) && $TSUE_Forums->availableForums && count($TSUE_Forums->availableForums) ) 
        {
            foreach( $TSUE_Forums->availableForums as $forumid => $forum ) 
            {
                $threads += $forum["threadcount"];
                $replies += $forum["replycount"];
            }
        }

        $Members = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE COUNT(memberid) as totalMembers FROM tsue_members USE INDEX(PRIMARY)");
        $rMember = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE m.memberid, m.membername, g.groupstyle FROM tsue_members m USE INDEX(PRIMARY) LEFT JOIN tsue_membergroups g USING(membergroupid) ORDER BY m.memberid DESC LIMIT 1");
        $Torrents = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE COUNT(tid) as totalTorrents, SUM(seeders) as totalSeeders, SUM(leechers) as totalLeechers FROM tsue_torrents");
        $unseededTorrents = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE COUNT(tid) as unseededTorrents FROM tsue_torrents WHERE seeders = 0");
        $_memberid = $rMember["memberid"];
        $_membername = getMembername($rMember["membername"], $rMember["groupstyle"]);
        eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $totalMembers = friendly_number_format($Members["totalMembers"]);
        $max_members_limit = getSetting("global_settings", "max_members_limit", 0);
        if( $max_members_limit ) 
        {
            $totalMembers .= "/" . friendly_number_format($max_members_limit);
        }

        $website_stats = get_phrase("website_stats_plugin_contents", $totalMembers, friendly_number_format($Torrents["totalTorrents"]), friendly_number_format($Torrents["totalSeeders"]), friendly_number_format($Torrents["totalLeechers"]), friendly_number_format($threads), friendly_number_format($replies), $member_info_link, friendly_number_format($unseededTorrents["unseededTorrents"]));
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $website_stats);
        if( defined("IS_AJAX") ) 
        {
            return $website_stats;
        }

    }

    eval("\$TSUEPlugin_websiteStats = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_website_stats") . "\";");
    return $TSUEPlugin_websiteStats;
}



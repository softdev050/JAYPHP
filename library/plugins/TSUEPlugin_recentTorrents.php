<?php 
function TSUEPlugin_recentTorrents($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    if( isset($_COOKIE["tsue_rt_switch_list"]) ) 
    {
        return TSUEPlugin_recentTorrents_Switch_List($pluginPosition, $pluginOptions);
    }

    $WHERE = " WHERE t.awaitingModeration = 0";
    if( !empty($TSUE["TSUE_Member"]->info["defaultTorrentCategories"]) ) 
    {
        $categories = array(  );
        foreach( $TSUE["TSUE_Member"]->info["defaultTorrentCategories"] as $categoryID ) 
        {
            $categories[] = intval($categoryID);
        }
        if( !empty($categories) ) 
        {
            $WHERE = " WHERE t.awaitingModeration = 0 AND c.cid IN (" . implode(",", $categories) . ")";
        }

    }

    $cacheName = "TSUEPlugin_recentTorrents_" . md5($TSUE["TSUE_Member"]->info["languageid"] . $WHERE);
    $isToggled = isToggled("recentTorrents");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($Images = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.*, a.filename, i.content as IMDBContent, c.cname, cc.cname as parentCategoryName, m.membername \r\n\t\tFROM  tsue_torrents t \r\n\t\tLEFT JOIN tsue_attachments a ON(t.tid=a.content_id&&a.content_type=\"torrent_images\")\r\n\t\tLEFT JOIN tsue_imdb i USING(tid)\r\n\t\tLEFT JOIN tsue_torrents_categories c ON(t.cid=c.cid) \r\n\t\tLEFT JOIN tsue_torrents_categories cc ON(c.pid=cc.cid) \r\n\t\tLEFT JOIN tsue_members m ON (t.owner=m.memberid)\r\n\t\t" . $WHERE . " \r\n\t\tGROUP BY t.tid ORDER BY t.added DESC LIMIT " . getPluginOption($pluginOptions, "max_recent_torrents", 20));
        if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
        {
            require_once(REALPATH . "/library/functions/functions_getTorrents.php");
            $divWidthClass = ($TSUE["TSUE_Plugin"]->hasSideBarPlugins ? "widthSidebar" : "widthoutSidebar");
            $TSUE["TSUE_Template"]->loadJavascripts("scrollable");
            $Images = "\r\n\t\t\t<div class=\"items\">\r\n\t\t\t\t<div class=\"" . $divWidthClass . "\">";
            $count = 0;
            while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
            {
                if( $count && $count % 7 == 0 ) 
                {
                    $Images .= "\r\n\t\t\t\t\t</div>\r\n\t\t\t\t\t<div class=\"" . $divWidthClass . "\">";
                }

                $title = addslashes(strip_tags($Torrent["name"]));
                $title .= "<br>" . $TSUE["TSUE_Language"]->phrase["torrents_seeders"] . ": " . friendly_number_format($Torrent["seeders"]) . " / \r\n\t\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_leechers"] . ": " . friendly_number_format($Torrent["leechers"]) . " / \r\n\t\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_size"] . ": " . friendly_size($Torrent["size"]) . " /  \r\n\t\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_times_completed"] . ": " . friendly_number_format($Torrent["times_completed"]);
                $categoryName = "";
                if( $Torrent["parentCategoryName"] ) 
                {
                    $categoryName .= $Torrent["parentCategoryName"] . " > ";
                }

                $categoryName .= $Torrent["cname"];
                $title .= "<br>" . get_phrase("torrents_category", $categoryName);
                $Torrent["options"] = unserialize($Torrent["options"]);
                if( isAnonymouse($Torrent) ) 
                {
                    $Torrent["membername"] = get_phrase("torrents_anonymouse_uploader");
                }

                $owner = get_phrase("torrents_owner", convert_relative_time($Torrent["added"], false), $Torrent["membername"]);
                $title .= "<br>" . $owner;
                $title .= "<br>" . str_replace("\"", "'", getTorrentMultipliers($Torrent));
                $hasValidImage = is_valid_image($Torrent["filename"]);
                if( !$hasValidImage && $Torrent["IMDBContent"] ) 
                {
                    $IMDBContent = unserialize($Torrent["IMDBContent"]);
                    if( is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
                    {
                        $count++;
                        eval("\$Images .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_image_imdb") . "\";");
                    }

                }
                else
                {
                    if( $hasValidImage ) 
                    {
                        $count++;
                        eval("\$Images .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_image") . "\";");
                    }

                }

            }
            $Images .= "\r\n\t\t\t\t</div>\r\n\t\t\t</div>";
            if( !$count ) 
            {
                return "";
            }

            $TSUE["TSUE_Cache"]->saveCache($cacheName, $Images);
        }
        else
        {
            return NULL;
        }

    }
    else
    {
        $TSUE["TSUE_Template"]->loadJavascripts("scrollable");
    }

    $addVerticalClass = ($pluginPosition == "right" ? " vertical" : "");
    eval("\$TSUEPlugin_recentTorrents = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents") . "\";");
    return $TSUEPlugin_recentTorrents;
}

function TSUEPlugin_recentTorrents_Switch_List($pluginPosition = "", $pluginOptions = array(  ), $pn = 0)
{
    global $TSUE;
    $isToggled = isToggled("recentTorrents");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    $maxTorrents = getPluginOption($pluginOptions, "max_recent_torrents", 5);
    if( 5 < $maxTorrents ) 
    {
        $maxTorrents = 5;
    }

    $WHERE = " WHERE t.awaitingModeration = 0";
    if( !empty($TSUE["TSUE_Member"]->info["defaultTorrentCategories"]) ) 
    {
        $categories = array(  );
        foreach( $TSUE["TSUE_Member"]->info["defaultTorrentCategories"] as $categoryID ) 
        {
            $categories[] = intval($categoryID);
        }
        if( !empty($categories) ) 
        {
            $WHERE = " WHERE t.awaitingModeration = 0 AND c.cid IN (" . implode(",", $categories) . ")";
        }

    }

    $LIMIT = $maxTorrents;
    if( $pn ) 
    {
        $n = $pn * $maxTorrents;
        $LIMIT = (string) $n . ", " . $n;
    }

    $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.*, a.filename, i.content as IMDBContent, c.cname, cc.cid as parentCategoryCID, cc.cname as parentCategoryName, m.membername, g.groupstyle \r\n\tFROM  tsue_torrents t \r\n\tLEFT JOIN tsue_attachments a ON(t.tid=a.content_id&&a.content_type=\"torrent_images\")\r\n\tLEFT JOIN tsue_imdb i USING(tid)\r\n\tLEFT JOIN tsue_torrents_categories c ON(t.cid=c.cid) \r\n\tLEFT JOIN tsue_torrents_categories cc ON(c.pid=cc.cid) \r\n\tLEFT JOIN tsue_members m ON (t.owner=m.memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid)\r\n\t" . $WHERE . " \r\n\tGROUP BY t.tid ORDER BY t.added DESC LIMIT " . $LIMIT);
    if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
    {
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $count = 0;
        $recent_torrents_list = "";
        while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
        {
            $torrentName = strip_tags($Torrent["name"]);
            $torrentDetails = $TSUE["TSUE_Language"]->phrase["torrents_seeders"] . ": " . friendly_number_format($Torrent["seeders"]) . " / \r\n\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_leechers"] . ": " . friendly_number_format($Torrent["leechers"]) . " / \r\n\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_size"] . ": " . friendly_size($Torrent["size"]) . " /  \r\n\t\t\t" . $TSUE["TSUE_Language"]->phrase["torrents_times_completed"] . ": " . friendly_number_format($Torrent["times_completed"]);
            $categoryName = "";
            if( $Torrent["parentCategoryName"] ) 
            {
                $categoryName .= "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;cid=" . $Torrent["parentCategoryCID"] . "\">" . $Torrent["parentCategoryName"] . "</a> > ";
            }

            $categoryName .= "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;cid=" . $Torrent["cid"] . "\">" . $Torrent["cname"] . "</a>";
            $categoryName = get_phrase("torrents_category", $categoryName);
            $Torrent["options"] = unserialize($Torrent["options"]);
            if( isAnonymouse($Torrent) ) 
            {
                $Torrent["membername"] = get_phrase("torrents_anonymouse_uploader");
            }

            $Owner = get_phrase("torrents_owner", convert_relative_time($Torrent["added"], false), getMembername($Torrent["membername"], $Torrent["groupstyle"]));
            $Flags = str_replace("\"", "'", getTorrentMultipliers($Torrent));
            $title = addslashes($torrentName);
            $hasValidImage = is_valid_image($Torrent["filename"]);
            if( !$hasValidImage && $Torrent["IMDBContent"] ) 
            {
                $IMDBContent = unserialize($Torrent["IMDBContent"]);
                if( is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
                {
                    $count++;
                    eval("\$Image = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_image_imdb") . "\";");
                }

            }
            else
            {
                if( $hasValidImage ) 
                {
                    $count++;
                    eval("\$Image = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_image") . "\";");
                }

            }

            if( isset($Image) && $Image ) 
            {
                eval("\$recent_torrents_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_list") . "\";");
            }

        }
        if( !$count ) 
        {
            return "";
        }

        if( !defined("IS_AJAX") ) 
        {
            eval("\$TSUEPlugin_recentTorrents_Switch_List = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_torrents_switch") . "\";");
            return $TSUEPlugin_recentTorrents_Switch_List;
        }

        return $recent_torrents_list;
    }

    return show_error(get_phrase("message_nothing_found"), "-ERROR-");
}



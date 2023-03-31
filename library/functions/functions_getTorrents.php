<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function isSeeder($tid, $memberid)
{
    global $TSUE;
    if( getSetting("xbt", "active") ) 
    {
        $isSeeder = $TSUE["TSUE_Database"]->query_result("SELECT fid FROM xbt_files_users WHERE fid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND uid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND `left` = 0");
    }
    else
    {
        $isSeeder = $TSUE["TSUE_Database"]->query_result("SELECT pid FROM tsue_torrents_peers WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND `left` = 0");
    }

    return ($isSeeder ? true : false);
}

function downloadTorrent($tid = 0, $Member = "")
{
    global $TSUE;
    if( !is_array($Member) ) 
    {
        $Member = array( "permissions" => $TSUE["TSUE_Member"]->info["permissions"], "membergroupid" => $TSUE["TSUE_Member"]->info["membergroupid"], "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "passkey" => $TSUE["TSUE_Member"]->info["passkey"], "max_slot_limit" => (isset($TSUE["TSUE_Member"]->info["permissions"]["max_slot_limit"]) ? intval($TSUE["TSUE_Member"]->info["permissions"]["max_slot_limit"]) : 0), "minratio_dl_torrents" => (isset($TSUE["TSUE_Member"]->info["permissions"]["minratio_dl_torrents"]) ? 0 + $TSUE["TSUE_Member"]->info["permissions"]["minratio_dl_torrents"] : 0), "uploaded" => $TSUE["TSUE_Member"]->info["uploaded"], "downloaded" => $TSUE["TSUE_Member"]->info["downloaded"] );
    }

    $isSeeder = isseeder($tid, $Member["memberid"]);
    if( !$isSeeder && !has_permission("candownload_torrents", $Member["permissions"]) ) 
    {
        return get_phrase("permission_denied");
    }

    if( !$tid ) 
    {
        return get_phrase("torrents_not_found");
    }

    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.options, c.cdownloadpermissions, c.cviewpermissions, a.filename FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) LEFT JOIN tsue_attachments a ON (a.content_type='torrent_files' AND a.content_id=t.tid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
    if( !$Torrent ) 
    {
        return get_phrase("torrents_not_found");
    }

    $Torrent["options"] = unserialize($Torrent["options"]);
    if( !$isSeeder ) 
    {
        if( !hasViewPermission($Torrent["cviewpermissions"], $Member["membergroupid"]) || !hasViewPermission($Torrent["cdownloadpermissions"], $Member["membergroupid"]) ) 
        {
            return get_phrase("permission_denied");
        }

        if( isset($Member["max_slot_limit"]) && $Member["max_slot_limit"] ) 
        {
            if( getSetting("xbt", "active") ) 
            {
                $totalActiveTorrents = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE uid as memberid FROM xbt_files_users WHERE `uid` = " . $Member["memberid"] . " AND `active` = 1 AND `left` > 0");
            }
            else
            {
                $totalActiveTorrents = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE memberid FROM tsue_torrents_peers WHERE `memberid` = " . $Member["memberid"] . " AND `active` = 1 AND `left` > 0");
            }

            if( $totalActiveTorrents && $Member["max_slot_limit"] <= $totalActiveTorrents ) 
            {
                return get_phrase("slots_restrict_sim", $totalActiveTorrents, $Member["max_slot_limit"]);
            }

        }

        if( isset($Member["minratio_dl_torrents"]) && $Member["minratio_dl_torrents"] && member_ratio($Member["uploaded"], $Member["downloaded"], true) < $Member["minratio_dl_torrents"] ) 
        {
            return get_phrase("not_enough_ratio_to_download_torrent", number_format($Member["minratio_dl_torrents"], 2));
        }

    }

    require_once(REALPATH . "/library/classes/class_torrent.php");
    $TorrentFile = REALPATH . "/data/torrents/torrent_files/" . $Torrent["filename"];
    $Torrent["name"] = safe_names($Torrent["name"]);
    $TorrentName = $Torrent["name"] . ".torrent";
    $AnnounceURL = buildAnnounceURL($Member["passkey"]);
    if( !is_file($TorrentFile) || !($Data = @file_get_contents($TorrentFile)) ) 
    {
        return get_phrase("torrents_not_found");
    }

    $Torrent = new Torrent();
    $Torrent->load($Data);
    if( $Torrent->error ) 
    {
        return get_phrase("torrents_not_found");
    }

    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["announce_private_torrents_only"] ) 
    {
        $Torrent->setTrackers(array( $AnnounceURL ));
    }
    else
    {
        $Torrent->setTrackers(buildAnnounceURLs($Torrent->getTrackers(), $AnnounceURL));
    }

    require_once(REALPATH . "/library/functions/functions_downloadFile.php");
    downloadFile(false, $TorrentName, $Torrent->bencode());
    exit();
}

function prepareMagnetLink($Torrent)
{
    if( !getSetting("magnet_links", "active") ) 
    {
        return "";
    }

    $magnetLink = "magnet:?xt=urn:btih:" . bin2hex($Torrent["info_hash"]) . "&dn=" . urlencode($Torrent["name"]) . "&xl=" . (0 + $Torrent["size"]);
    if( $Torrent["external"] ) 
    {
        require_once(REALPATH . "/library/classes/class_torrent.php");
        $TorrentFile = REALPATH . "/data/torrents/torrent_files/" . $Torrent["torrentFilename"];
        if( !is_file($TorrentFile) || !($Data = @file_get_contents($TorrentFile)) ) 
        {
            exit( $TorrentFile );
        }

        $Torrent = new Torrent();
        $Torrent->load($Data);
        if( $Torrent->error ) 
        {
            exit( "error" );
        }

        if( strlen($Torrent->getPieces()) % 20 != 0 ) 
        {
            return "";
        }

        $buildAnnounceURLS = buildAnnounceURLs($Torrent->getTrackers(), buildAnnounceURL());
        foreach( $buildAnnounceURLS as $URL ) 
        {
            $magnetLink .= "&tr=" . urlencode($URL);
        }
    }
    else
    {
        $magnetLink .= "&tr=" . urlencode(buildAnnounceURL());
    }

    return $magnetLink;
}

function getTorrents($TorrentsQuery)
{
    global $TSUE;
    global $SelectedSearchType;
    global $keywords;
    global $hideAnonymouseTorrents;
    $keywords = html_clean($keywords);
    $Torrents = $LikeLink = $LikeList = $torrent_tags = "";
    $count = 0;
    $catNameAdded = false;
    while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
    {
        if( $Torrent["flags"] != 1 && $Torrent["size"] && $Torrent["name"] && hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            if( isset($_GET["cid"]) && !$catNameAdded ) 
            {
                $catNameAdded = true;
                if( $Torrent["parentCategoryName"] ) 
                {
                    AddBreadcrumb(array( $Torrent["parentCategoryName"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;cid=" . $Torrent["parentCategoryID"] ));
                }

                AddBreadcrumb(array( $Torrent["cname"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;cid=" . $Torrent["cid"] ));
            }

            $torrentGenres = buildTorrentGenres($Torrent["gids"]);
            $Torrent["options"] = unserialize($Torrent["options"]);
            $_memberid = $Torrent["owner"];
            $_membername = getMembername($Torrent["membername"], $Torrent["groupstyle"]);
            if( isAnonymouse($Torrent) ) 
            {
                if( $hideAnonymouseTorrents ) 
                {
                    continue;
                }

                $_memberid = 0;
                $_membername = get_phrase("torrents_anonymouse_uploader");
            }

            $torrent_delete_torrent_link = canDeleteTorrent($Torrent);
            $torrent_nuke_link = canNukeTorrent($Torrent);
            $torrent_edit_torrent_link = canEditTorrent($Torrent);
            $torrent_reseed_request = canRequestReseed($Torrent);
            $torrent_bump_link = canBumpTorrent($Torrent);
            eval("\$Torrent['membername'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $categoryImage = get_torrent_category_image($Torrent["cid"]);
            $categoryName = $Torrent["cname"];
            $Torrent["name"] = strip_tags($Torrent["name"]);
            $magnetLink = "";
            if( hasViewPermission($Torrent["cdownloadpermissions"]) ) 
            {
                $magnetLink = preparemagnetlink($Torrent);
                if( $magnetLink ) 
                {
                    eval("\$magnetLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("magnet_link") . "\";");
                }

            }

            $bookmarkLink = "";
            if( has_permission("canuse_bookmarks") ) 
            {
                eval("\$bookmarkLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate(($Torrent["bookmark"] ? "remove_bookmark" : "bookmark_link")) . "\";");
            }

            $Torrent["nfo"] = secureNFOContents($Torrent["nfo"]);
            if( strstr($Torrent["description"], "[NFO]") !== false ) 
            {
                $NFOToDescription = true;
                if( $Torrent["nfo"] ) 
                {
                    eval("\$pre_nfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("pre_nfo") . "\";");
                    $Torrent["description"] = $pre_nfo;
                }
                else
                {
                    $Torrent["description"] = $Torrent["name"];
                }

            }
            else
            {
                $Torrent["description"] = $TSUE["TSUE_Parser"]->parse($Torrent["description"]);
            }

            $Torrent["owner"] = get_phrase("torrents_owner", convert_relative_time($Torrent["added"]), $Torrent["membername"]);
            $Torrent["cname"] = get_phrase("torrents_category", (($Torrent["parentCategoryName"] ? $Torrent["parentCategoryName"] . " > " : "")) . $Torrent["cname"]);
            $Torrent["seeders"] = friendly_number_format($Torrent["seeders"]);
            $Torrent["leechers"] = friendly_number_format($Torrent["leechers"]);
            $Torrent["size"] = friendly_size($Torrent["size"]);
            $Torrent["times_completed"] = friendly_number_format($Torrent["times_completed"]);
            $torrent_nfo = torrent_nfo($Torrent);
            $torrent_trailer = "";
            $IMDBRating = "";
            $Torrent["IMDBContent"] = parseIMDB($Torrent["IMDBContent"]);
            if( $Torrent["IMDBContent"] ) 
            {
                $torrent_trailer = get_trailer($Torrent);
                $IMDBRating = IMDBRating($Torrent["IMDBContent"]["rating"]);
            }

            $get_torrent_images = get_torrent_image($Torrent["filename"], $Torrent["IMDBContent"]["title_id"]);
            $torrentFlags = getTorrentFlags($Torrent);
            $torrentMultipliers = getTorrentMultipliers($Torrent);
            $class = "";
            if( $count % 2 == 1 ) 
            {
                $class = " row2";
            }

            $torrent_moderation = "";
            if( $Torrent["awaitingModeration"] && has_permission("can_moderate_torrents") ) 
            {
                $moderationPhrase = get_phrase("torrent_awaiting_moderation", $Torrent["tid"]);
                eval("\$torrent_moderation = \"" . $TSUE["TSUE_Template"]->LoadTemplate(($TSUE["TSUE_Member"]->info["torrentStyle"] == 1 ? "torrent_moderation" : "torrent_moderation_classic")) . "\";");
            }

            eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate(($TSUE["TSUE_Member"]->info["torrentStyle"] == 1 ? "torrent_list" : "torrents_list_classic")) . "\";");
            $count++;
        }

    }
    return $Torrents;
}

function getTorrent($Torrent)
{
    global $TSUE;
    $Torrents = "";
    if( $Torrent["flags"] != 1 && $Torrent["size"] && $Torrent["name"] && hasViewPermission($Torrent["cviewpermissions"]) ) 
    {
        global $Likes;
        require_once(REALPATH . "library/classes/class_likes.php");
        $Likes = new TSUE_Likes();
        $LikeList = $Likes->getContentLikes($Torrent["tid"], "torrent");
        $LikeLink = $Likes->getValidLikeLink($Torrent["tid"], $Torrent["owner"], "torrent");
        $torrent_tags = getTags($Torrent["tags"]);
        $IMDBContent = $torrent_trailer = $IMDBRating = "";
        $Torrent["IMDBContent"] = parseIMDB($Torrent["IMDBContent"]);
        $IMDBContent = IMDBContent($Torrent["IMDBContent"], $Torrent["tid"]);
        if( $Torrent["IMDBContent"]["title_id"] ) 
        {
            $torrent_trailer = get_trailer($Torrent);
            $IMDBRating = IMDBRating($Torrent["IMDBContent"]["rating"]);
        }

        $social_media_buttons = social_media_buttons("torrent");
        require_once(REALPATH . "/library/functions/functions_getComments.php");
        $Comments = getComments("torrent_comments", $Torrent["tid"], (isset($_GET["comment_id"]) ? intval($_GET["comment_id"]) : 0));
        $torrentGenres = buildTorrentGenres($Torrent["gids"]);
        $Torrent["options"] = unserialize($Torrent["options"]);
        $_memberid = $Torrent["owner"];
        $_membername = getMembername($Torrent["membername"], $Torrent["groupstyle"]);
        if( isAnonymouse($Torrent) ) 
        {
            $_memberid = 0;
            $_membername = get_phrase("torrents_anonymouse_uploader");
        }

        $torrent_delete_torrent_link = canDeleteTorrent($Torrent);
        $torrent_nuke_link = canNukeTorrent($Torrent);
        $torrent_edit_torrent_link = canEditTorrent($Torrent);
        $torrent_reseed_request = canRequestReseed($Torrent);
        $torrent_bump_link = canBumpTorrent($Torrent);
        eval("\$Torrent['membername'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $categoryImage = get_torrent_category_image($Torrent["cid"]);
        $Torrent["name"] = strip_tags($Torrent["name"]);
        $magnetLink = "";
        if( hasViewPermission($Torrent["cdownloadpermissions"]) ) 
        {
            $magnetLink = preparemagnetlink($Torrent);
            if( $magnetLink ) 
            {
                eval("\$magnetLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("magnet_link") . "\";");
            }

        }

        $bookmarkLink = "";
        if( has_permission("canuse_bookmarks") ) 
        {
            eval("\$bookmarkLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate(($Torrent["bookmark"] ? "remove_bookmark" : "bookmark_link")) . "\";");
        }

        $orjTorrentOwner = $Torrent["owner"];
        $Torrent["nfo"] = secureNFOContents($Torrent["nfo"]);
        $torrent_nfo = torrent_nfo($Torrent);
        if( $Torrent["nfo"] ) 
        {
            eval("\$pre_nfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("pre_nfo") . "\";");
        }
        else
        {
            $pre_nfo = show_error(get_phrase("message_nothing_found"), "", false, false);
        }

        if( strstr($Torrent["description"], "[NFO]") !== false ) 
        {
            $NFOToDescription = true;
            if( $Torrent["nfo"] ) 
            {
                $Torrent["description"] = $pre_nfo;
            }
            else
            {
                $Torrent["description"] = $Torrent["name"];
            }

        }
        else
        {
            $Torrent["description"] = $TSUE["TSUE_Parser"]->parse($Torrent["description"]);
        }

        $Torrent["owner"] = get_phrase("torrents_owner", convert_relative_time($Torrent["added"]), $Torrent["membername"]);
        $Torrent["cname"] = get_phrase("torrents_category", (($Torrent["parentCategoryName"] ? $Torrent["parentCategoryName"] . " > " : "")) . $Torrent["cname"]);
        $Torrent["seeders"] = friendly_number_format($Torrent["seeders"]);
        $Torrent["leechers"] = friendly_number_format($Torrent["leechers"]);
        $Torrent["size"] = friendly_size($Torrent["size"]);
        $Torrent["times_completed"] = friendly_number_format($Torrent["times_completed"]);
        $get_torrent_images = get_torrent_images($Torrent["tid"], $Torrent["IMDBContent"]["title_id"]);
        $torrentFlags = getTorrentFlags($Torrent);
        $torrentMultipliers = getTorrentMultipliers($Torrent);
        if( isset($Torrent["options"]["hitRunRatio"]) ) 
        {
            $Torrent["options"]["hitRunRatio"] = 0 + $Torrent["options"]["hitRunRatio"];
            if( $Torrent["options"]["hitRunRatio"] ) 
            {
                $Torrents .= show_information(get_phrase("torrent_requires_x_seed_ratio", $Torrent["options"]["hitRunRatio"]), NULL, false);
            }

        }

        $torrent_moderation = "";
        eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_list") . "\";");
        eval("\$Torrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_details") . "\";");
        if( isset($_GET["accept"]) && $_GET["accept"] == "true" && has_permission("can_moderate_torrents") && $Torrent["awaitingModeration"] ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_torrents", array( "awaitingModeration" => 0 ), "tid=" . $TSUE["TSUE_Database"]->escape($Torrent["tid"]));
            if( $TSUE["TSUE_Database"]->affected_rows() ) 
            {
                $Torrent["awaitingModeration"] = 0;
                logAction(get_phrase("torrent_x_approved_by_y", $Torrent["name"], $TSUE["TSUE_Member"]->info["membername"]));
                deleteCache("TSUEPlugin_recentTorrents_");
                updateMemberPoints(getSetting("global_settings", "points_torrent_upload"), $orjTorrentOwner);
                ircAnnouncement("new_torrent", $Torrent["tid"], $Torrent["name"]);
                shoutboxAnnouncement(array( "new_torrent", $Torrent["tid"], strip_tags($Torrent["name"]), (isset($NFOToDescription) ? "[NFO]" : substr(strip_tags($Torrent["description"]), 0, 200) . " ..."), $Torrent["cid"] ));
                alert_member($orjTorrentOwner, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "torrent", $Torrent["tid"], "torrent_approved");
            }

        }

        if( $Torrent["awaitingModeration"] && has_permission("can_moderate_torrents") ) 
        {
            $Torrents = show_information(get_phrase("torrent_awaiting_moderation_accept", $Torrent["tid"]), NULL, false) . $Torrents;
        }

    }

    return $Torrents;
}

function getTags($tags = array(  ))
{
    global $TSUE;
    if( $tags ) 
    {
        $tagList = array(  );
        $tags = tsue_explode(",", $tags);
        if( $tags ) 
        {
            foreach( $tags as $tag ) 
            {
                $tag = trim(strip_tags($tag));
                if( 2 < strlen($tag) ) 
                {
                    $tagLink = urlencode($tag);
                    eval("\$tagList[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_tags_link_no_div") . "\";");
                }

            }
        }

        $tagList = implode(", ", $tagList);
        eval("\$torrent_tags = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_tags") . "\";");
        return $torrent_tags;
    }

}

function showAvailableGenres($checked = array(  ), $cid = 0)
{
    global $TSUE;
    if( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] ) 
    {
        $genreImagesFullURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_genres/";
        $torrentGenresIcons = "";
        foreach( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] as $Genre ) 
        {
            $Genre["categories"] = trim($Genre["categories"]);
            if( $Genre["categories"] ) 
            {
                $categories = explode(",", $Genre["categories"]);
            }
            else
            {
                $categories = "";
            }

            if( !$categories || !$cid || $categories && in_array($cid, $categories) ) 
            {
                $genreChecked = (in_array($Genre["gname"], $checked) ? " checked=\"checked\"" : "");
                eval("\$torrentGenresIcons .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrentGenresIcons") . "\";");
            }

        }
        if( !$torrentGenresIcons ) 
        {
            $torrentGenresIcons = show_error(get_phrase("no_genre_to_show"), "", 0, 0);
        }

        eval("\$torrentGenres = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrentGenres") . "\";");
        return $torrentGenres;
    }

}

function buildTorrentGenres($gids)
{
    global $TSUE;
    if( $gids && $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] ) 
    {
        $gids = explode("~", $gids);
        if( $gids ) 
        {
            $List = array(  );
            foreach( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] as $Genre ) 
            {
                if( in_array($Genre["gname"], $gids) ) 
                {
                    $List[] = "<a href=\"?p=torrents&amp;pid=" . PAGEID . "&amp;genre=" . urlencode($Genre["gname"]) . "\">" . $Genre["gname"] . "</a>";
                }

            }
            return get_phrase("genre") . ": " . implode(" | ", $List);
        }

    }

}

function get_trailer($Torrent)
{
    global $TSUE;
    eval("\$torrent_trailer = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_trailer") . "\";");
    return $torrent_trailer;
}

function torrent_nfo($Torrent)
{
    global $TSUE;
    $NFO = "";
    if( $Torrent["nfo"] ) 
    {
        eval("\$NFO = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_nfo") . "\";");
    }

    return $NFO;
}

function secureNFOContents($NFO, $remoteAscii = true, $parseURL = true, $replaceAsciiWith = "#")
{
    global $TSUE;
    $NFO = strip_tags($NFO);
    if( $remoteAscii ) 
    {
        $NFO = preg_replace("#[^a-zA-z0-9\\n\\r\\s\\t\\`\\~\\!\\@\\#\\\$\\%\\^\\&\\*\\(\\)\\_\\-\\+\\=\\{\\}\\[\\]\\;\\:\\\"'\\|\\\\<\\>\\,\\.\\?\\/]#", $replaceAsciiWith, $NFO);
        $NFO = preg_replace("#[\n]+#", "\n", $NFO);
    }

    return trim(($parseURL ? $TSUE["TSUE_Parser"]->parseHyperLinks($NFO, false) : $NFO));
}

function canRequestReseed($Torrent)
{
    global $TSUE;
    $torrent_reseed_request = "";
    if( has_permission("canrequest_reseed") && $Torrent["seeders"] == 0 ) 
    {
        eval("\$torrent_reseed_request = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_reseed_request") . "\";");
    }

    return $torrent_reseed_request;
}

function canEditTorrent($Torrent)
{
    global $TSUE;
    $torrent_edit_torrent_link = "";
    if( has_permission("canedit_torrents") || has_permission("canedit_own_torrents") && $Torrent["owner"] && $Torrent["owner"] === $TSUE["TSUE_Member"]->info["memberid"] ) 
    {
        eval("\$torrent_edit_torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_edit_torrent_link") . "\";");
    }

    return $torrent_edit_torrent_link;
}

function canDeleteTorrent($Torrent)
{
    global $TSUE;
    $torrent_delete_torrent_link = "";
    if( has_permission("candelete_torrents") || has_permission("candelete_own_torrents") && $Torrent["owner"] && $Torrent["owner"] === $TSUE["TSUE_Member"]->info["memberid"] ) 
    {
        eval("\$torrent_delete_torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_delete_torrent_link") . "\";");
    }

    return $torrent_delete_torrent_link;
}

function canNukeTorrent($Torrent)
{
    global $TSUE;
    $nuke_torrent_link = "";
    if( has_permission("cannuke_torrents") ) 
    {
        eval("\$nuke_torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate((isset($Torrent["options"]["nuked"]) && $Torrent["options"]["nuked"] ? "unnuke_torrent_link" : "nuke_torrent_link")) . "\";");
    }

    return $nuke_torrent_link;
}

function canBumpTorrent($Torrent)
{
    global $TSUE;
    $bump_torrent_link = "";
    if( has_permission("canbump_torrents") ) 
    {
        eval("\$bump_torrent_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("bump_torrent_link") . "\";");
    }

    return $bump_torrent_link;
}

function deleteTorrent($tid = 0, $deletePhrase = "", $torrentOwner = 0, $awaitingModeration = 0)
{
    global $TSUE;
    if( !$tid ) 
    {
        return false;
    }

    $attachments = $TSUE["TSUE_Database"]->query("SELECT content_type, filename FROM tsue_attachments WHERE content_type IN ('torrent_images', 'torrent_files', 'torrent_screenshots') AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    if( $TSUE["TSUE_Database"]->num_rows($attachments) ) 
    {
        while( $attachment = $TSUE["TSUE_Database"]->fetch_assoc($attachments) ) 
        {
            if( $attachment["content_type"] == "torrent_files" || $attachment["content_type"] == "nfo" ) 
            {
                $file = REALPATH . "/data/torrents/" . $attachment["content_type"] . "/" . $attachment["filename"];
                if( is_file($file) ) 
                {
                    @unlink($file);
                }

            }
            else
            {
                deleteImages($attachment["filename"]);
            }

        }
        $TSUE["TSUE_Database"]->delete("tsue_attachments", "content_type IN ('torrent_images', 'torrent_files', 'torrent_screenshots') AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    }

    $IMDB = $TSUE["TSUE_Database"]->query_result("SELECT content FROM tsue_imdb WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
    if( $IMDB ) 
    {
        $IMDBContent = unserialize($IMDB["content"]);
        if( $IMDBContent["title_id"] && is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
        {
            @unlink(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg");
        }

    }

    $TSUE["TSUE_Database"]->delete("tsue_bookmarks", "tid = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("tsue_comments", "content_type = 'torrent_comments' AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "content_type = 'torrent_comments' AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("tsue_imdb", "tid = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("tsue_liked_content", "content_type = 'torrent' AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("tsue_torrents_peers", "tid = " . $TSUE["TSUE_Database"]->escape($tid));
    $TSUE["TSUE_Database"]->delete("xbt_files_users", "fid = " . $TSUE["TSUE_Database"]->escape($tid));
    if( getSetting("xbt", "active") ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "flags" => "1" ), "tid = " . $TSUE["TSUE_Database"]->escape($tid));
    }
    else
    {
        $TSUE["TSUE_Database"]->delete("tsue_torrents", "tid = " . $TSUE["TSUE_Database"]->escape($tid));
    }

    deleteCache("TSUEPlugin_recentTorrents_");
    if( $torrentOwner && !$awaitingModeration ) 
    {
        updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_torrent_upload"], $torrentOwner, false);
    }

    if( $deletePhrase ) 
    {
        logAction($deletePhrase);
    }

    return true;
}

function deleteImages($filename)
{
    foreach( array( "l", "m", "s" ) as $torrentImageFolder ) 
    {
        $_filePath = REALPATH . "/data/torrents/torrent_images/" . $torrentImageFolder . "/" . $filename;
        if( is_file($_filePath) ) 
        {
            @unlink($_filePath);
        }

    }
}

function prepareTorrentCategoriesCheckbox($cid = array(  ), $showCategoryImage = false, $submitButtons = true, $forceRowLimit = false)
{
    global $TSUE;
    $MainCategories = $SubCategories = array(  );
    $Categories = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE cid, pid, cname, cviewpermissions FROM tsue_torrents_categories ORDER by `sort` ASC");
    while( $C = $TSUE["TSUE_Database"]->fetch_assoc($Categories) ) 
    {
        if( 0 < $C["pid"] ) 
        {
            if( hasViewPermission($C["cviewpermissions"]) ) 
            {
                $Image = "";
                if( $showCategoryImage ) 
                {
                    $Image = "<img src=\"" . get_torrent_category_image($C["cid"]) . "\" style=\"max-width: 24px;\" class=\"middle\" />";
                }

                $SubCategories[$C["pid"]][] = "\r\n\t\t\t\t<tr>\r\n\t\t\t\t\t<td valign=\"top\">\r\n\t\t\t\t\t\t" . $Image . " <input type=\"checkbox\" rel=\"category_" . $C["pid"] . "\" id=\"category_" . $C["cid"] . "\" name=\"cid[]\" value=\"" . $C["cid"] . "\"" . ((in_array($C["cid"], $cid) ? " checked=\"checked\"" : "")) . " /> <span class=\"small\"><a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=32&amp;cid=" . $C["cid"] . "\">" . $C["cname"] . "</a></span>\r\n\t\t\t\t\t</td>\r\n\t\t\t\t</tr>";
            }

        }
        else
        {
            $MainCategories[] = $C;
        }

    }
    $Output = "\r\n\t<table width=\"100%\" align=\"left\" cellpadding=\"5\" cellspacing=\"0\" id=\"torrentCategoriesCheckboxes\">\r\n\t\t<tr>";
    $Count = 0;
    $rowLimit = getSetting("global_settings", "torrent_category_row_limit", 0);
    if( !$rowLimit ) 
    {
        $rowLimit = 3;
    }

    if( $forceRowLimit && $forceRowLimit != $rowLimit ) 
    {
        $rowLimit = $forceRowLimit;
    }

    foreach( $MainCategories as $MainCategory ) 
    {
        if( hasViewPermission($MainCategory["cviewpermissions"]) ) 
        {
            if( $Count % $rowLimit == 0 ) 
            {
                $Output .= "\r\n\t\t\t\t</tr>\r\n\t\t\t\t<tr class=\"hidden\">\r\n\t\t\t\t\t<td colspan=\"" . $rowLimit . "\" class=\"hidden\"></td>\r\n\t\t\t\t</tr>\r\n\t\t\t\t<tr>";
            }

            $Image = "";
            if( $showCategoryImage ) 
            {
                $Image = "<img src=\"" . get_torrent_category_image($MainCategory["cid"]) . "\" style=\"max-width: 24px;\" class=\"middle\" />";
            }

            $Output .= "\r\n\t\t\t<td valign=\"top\">\r\n\t\t\t\t" . $Image . " \r\n\t\t\t\t<input type=\"checkbox\" rel=\"main\" id=\"category_" . $MainCategory["cid"] . "\" name=\"cid[]\" value=\"" . $MainCategory["cid"] . "\"" . ((in_array($MainCategory["cid"], $cid) ? " checked=\"checked\"" : "")) . " /> <span class=\"strong\"><a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=32&amp;cid=" . $MainCategory["cid"] . "\">" . $MainCategory["cname"] . "</a></span>";
            if( isset($SubCategories[$MainCategory["cid"]]) ) 
            {
                $Output .= "\r\n\t\t\t\t<div style=\"padding-left: 26px;\">\r\n\t\t\t\t\t<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\" align=\"left\">";
                foreach( $SubCategories[$MainCategory["cid"]] as $SubCategory ) 
                {
                    $Output .= $SubCategory;
                }
                $Output .= "\r\n\t\t\t\t\t</table>\r\n\t\t\t\t</div>";
            }

            $Output .= "\r\n\t\t\t</td>";
            $Count++;
        }

    }
    $Output .= "\r\n\t\t</tr>\r\n\t" . (($submitButtons ? "\r\n\t\t<tr>\r\n\t\t\t<td colspan=\"" . $rowLimit . "\">\r\n\t\t\t\t<div class=\"floatleft\"><input type=\"submit\" value=\"" . get_phrase("search_in_selected_categories") . "\" class=\"submit\" /></div>\r\n\t\t\t\t<div class=\"floatright\"><input type=\"submit\" value=\"" . get_phrase("rss_for_selected_categories") . "\" class=\"submit\" name=\"rss\" /></div>\r\n\t\t\t</td>\r\n\t\t</tr>" : "")) . "\r\n\t</table>";
    return $Output;
}

function prepareTorrentCategoriesSelectbox($cid = array(  ))
{
    global $TSUE;
    $MainCategories = $SubCategories = array(  );
    $Categories = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE cid, pid, cname, cviewpermissions FROM tsue_torrents_categories ORDER by `sort` ASC");
    while( $C = $TSUE["TSUE_Database"]->fetch_assoc($Categories) ) 
    {
        if( 0 < $C["pid"] ) 
        {
            if( hasViewPermission($C["cviewpermissions"]) ) 
            {
                $SubCategories[$C["pid"]][] = "\r\n\t\t\t\t<option value=\"" . $C["cid"] . "\" rel=\"category_" . $C["pid"] . "\" id=\"category_" . $C["cid"] . "\"" . ((in_array($C["cid"], $cid) ? " selected=\"selected\"" : "")) . ">&nbsp;|- " . $C["cname"] . "</option>";
            }

        }
        else
        {
            $MainCategories[] = $C;
        }

    }
    $Output = "";
    foreach( $MainCategories as $MainCategory ) 
    {
        if( hasViewPermission($MainCategory["cviewpermissions"]) ) 
        {
            $Output .= "\r\n\t\t\t<option value=\"" . $MainCategory["cid"] . "\" rel=\"category_" . $MainCategory["pid"] . "\" id=\"category_" . $MainCategory["cid"] . "\"" . ((in_array($MainCategory["cid"], $cid) ? " selected=\"selected\"" : "")) . ">" . $MainCategory["cname"] . "</option>";
            if( isset($SubCategories[$MainCategory["cid"]]) ) 
            {
                foreach( $SubCategories[$MainCategory["cid"]] as $SubCategory ) 
                {
                    $Output .= $SubCategory;
                }
            }

        }

    }
    return $Output;
}

function saveTorrent($TorrentFile, $Path = "")
{
    global $TSUE;
    require_once(REALPATH . "/library/classes/class_torrent.php");
    $TorrentFile = (($Path ? $Path : REALPATH . "/data/torrents/torrent_files/")) . $TorrentFile;
    $AnnounceURL = buildAnnounceURL();
    if( !is_file($TorrentFile) || !($Data = @file_get_contents($TorrentFile)) ) 
    {
        ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
    }

    $Torrent = new Torrent();
    $Torrent->load($Data);
    if( $Torrent->error ) 
    {
        ajax_message(get_phrase("torrent_upload_invalid_torrent_file"), "-ERROR-");
    }

    if( strlen($Torrent->getPieces()) % 20 != 0 ) 
    {
        ajax_message(get_phrase("torrent_upload_invalid_torrent_file"), "-ERROR-");
    }

    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["announce_private_torrents_only"] ) 
    {
        if( $Torrent->getPrivate() != 1 ) 
        {
            $Torrent->setPrivate(1);
        }

        $Torrent->setTrackers(array( $AnnounceURL ));
    }
    else
    {
        $Torrent->setTrackers(buildAnnounceURLs($Torrent->getTrackers(), $AnnounceURL));
    }

    $Torrent->setComment(get_phrase("torrents_torrent_comment"));
    $Torrent->setCreatedBy($TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
    $Torrent->setSource($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
    $Torrent->setCreationDate(TIMENOW);
    if( !file_put_contents($TorrentFile, $Torrent->bencode()) ) 
    {
        ajax_error(get_phrase("torrent_upload_unable_to_upload"), "-ERROR-");
    }

    return array( "info_hash" => $Torrent->getHash(), "size" => $Torrent->getSize() );
}

function buildAnnounceURLs($getTrackers = array(  ), $AnnounceURL)
{
    global $TSUE;
    $newAnnounceURLS = array(  );
    $parseServerURL = parse_url($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
    foreach( $getTrackers as $aURL ) 
    {
        $aURL = removeAllWhiteSpaces($aURL);
        if( preg_match("#" . $parseServerURL["host"] . "#i", $aURL) ) 
        {
            if( !in_array($AnnounceURL, $newAnnounceURLS) && getSetting("global_settings", "announce_add_announce_url") ) 
            {
                $newAnnounceURLS[] = $AnnounceURL;
            }

        }
        else
        {
            if( $aURL != "" ) 
            {
                $newAnnounceURLS[] = $aURL;
            }

        }

    }
    if( (!$newAnnounceURLS || !in_array($AnnounceURL, $newAnnounceURLS)) && getSetting("global_settings", "announce_add_announce_url") ) 
    {
        $newAnnounceURLS[] = $AnnounceURL;
    }

    return array_unique($newAnnounceURLS);
}

function IMDBContent($array, $tid)
{
    global $TSUE;
    $IMDBContent = "";
    $Torrent["IMDBContent"] = $array;
    $Torrent["tid"] = $tid;
    if( !empty($Torrent["IMDBContent"]) ) 
    {
        if( is_file(REALPATH . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg") ) 
        {
            $IMDBImage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg";
        }
        else
        {
            $IMDBImage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_l.png";
        }

        $Torrent["IMDBContent"]["genres"] = strip_tags(implode(" | ", $Torrent["IMDBContent"]["genres"]));
        $Torrent["IMDBContent"]["directors"] = strip_tags(implode(" | ", $Torrent["IMDBContent"]["directors"]));
        $Torrent["IMDBContent"]["writers"] = strip_tags(implode(" | ", $Torrent["IMDBContent"]["writers"]));
        $Torrent["IMDBContent"]["stars"] = strip_tags(implode(" | ", $Torrent["IMDBContent"]["stars"]));
        $Torrent["IMDBContent"]["cast"] = strip_tags(implode(" | ", $Torrent["IMDBContent"]["cast"]));
        $Torrent["IMDBContent"]["rating"] = $IMDBRating = IMDBRating($Torrent["IMDBContent"]["rating"]);
        if( !$Torrent["IMDBContent"]["rating"] ) 
        {
            $TSUE["TSUE_Language"]->phrase["imdb_votes"] = ucfirst(trim($TSUE["TSUE_Language"]->phrase["imdb_votes"])) . ": " . get_phrase("no_results_found");
        }

        eval("\$IMDBContent = \"" . $TSUE["TSUE_Template"]->LoadTemplate("imdb_details") . "\";");
        if( $Torrent["IMDBContent"]["rating"] ) 
        {
        }

        return $IMDBContent;
    }

    return false;
}

function parseIMDB($IMDBContent = "")
{
    return ($IMDBContent != "" ? unserialize($IMDBContent) : $IMDBContent);
}

function IMDBRating($rating)
{
    global $TSUE;
    $Crating = ceil($rating);
    if( !$Crating ) 
    {
        return NULL;
    }

    eval("\$IMDBRating = \"" . $TSUE["TSUE_Template"]->LoadTemplate("imdb_rating") . "\";");
    return $IMDBRating;
}

function getRecentTorrentsTimeout()
{
    global $TSUE;
    return $TSUE["TSUE_Member"]->info["lastvisit"];
}

function getTorrentMultipliers($Torrent)
{
    global $TSUE;
    $multipliers = $newIndicatorText = "";
    if( getrecenttorrentstimeout() <= $Torrent["added"] ) 
    {
        $newIndicatorText[] = "torrent_new";
    }

    if( isset($Torrent["options"]["record_stats"]) && $Torrent["options"]["record_stats"] == 0 ) 
    {
        $newIndicatorText[] = "torrent_no_record";
    }
    else
    {
        if( $Torrent["options"]["download_multiplier"] == 0 || $Torrent["download_multiplier"] == 0 || defined("HAPPY_HOURS_FREELEECH") ) 
        {
            $newIndicatorText[] = "torrent_free";
        }
        else
        {
            if( $Torrent["options"]["download_multiplier"] != 1 || $Torrent["download_multiplier"] != 1 ) 
            {
                $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_free.png", "alt" => "", "title" => get_phrase("torrent_download_multiplier") . ": " . $Torrent["options"]["download_multiplier"], "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
                $multipliers .= getImage($Image);
            }

        }

        if( $Torrent["options"]["upload_multiplier"] != 1 || $Torrent["upload_multiplier"] != 1 || defined("HAPPY_HOURS_DOUBLEUPLOAD") ) 
        {
            $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_multiple_upload.png", "alt" => "", "title" => get_phrase("torrent_upload_multiplier") . ": " . ((defined("HAPPY_HOURS_DOUBLEUPLOAD") ? 2 : $Torrent["options"]["upload_multiplier"])), "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
            $multipliers .= getImage($Image);
        }

    }

    if( $Torrent["sticky"] ) 
    {
        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/sticky.png", "alt" => "", "title" => get_phrase("sticky_torrent"), "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
        $multipliers .= getImage($Image);
    }

    if( isset($Torrent["options"]["nuked"]) && $Torrent["options"]["nuked"] ) 
    {
        $nuked_reason_x = str_replace("\"", "", strip_tags($Torrent["options"]["nuked"]));
        $nuked_reason_x = get_phrase("nuked_reason_x", $nuked_reason_x);
        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/buttons/nuke.png", "alt" => $nuked_reason_x, "title" => $nuked_reason_x, "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
        $multipliers .= getImage($Image);
    }

    if( $newIndicatorText ) 
    {
        foreach( $newIndicatorText as $text ) 
        {
            $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/" . $text . ".png", "alt" => "", "title" => get_phrase($text), "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
            $multipliers .= getImage($Image);
        }
    }

    if( $Torrent["external"] ) 
    {
        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/buttons/refresh.png", "alt" => $Torrent["tid"], "title" => $TSUE["TSUE_Language"]->phrase["refresh_external_torrent_status"], "class" => "middle clickable", "id" => "update_external_torrent", "rel" => "resized_by_tsue" );
        $multipliers .= getImage($Image);
    }

    if( 0 < getSetting("global_settings", "points_seed", 0) && ($points_x2_for_big_torrents = 0 + getSetting("global_settings", "points_x2_for_big_torrents", 0)) && 0 < $points_x2_for_big_torrents && $points_x2_for_big_torrents * 1073741824 <= $Torrent["size"] ) 
    {
        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/double_seed.png", "alt" => "", "title" => get_phrase("x2_for_big_torrents"), "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
        $multipliers .= getImage($Image);
    }

    return $multipliers;
}

function get_torrent_image($filename, $imdbFile = "", $spanClass = "")
{
    global $TSUE;
    if( is_valid_image($filename) && is_file(REALPATH . "/data/torrents/torrent_images/s/" . $filename) ) 
    {
        $ImageBIG = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/l/" . $filename;
        $ImageSmall = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/s/" . $filename;
    }
    else
    {
        if( $imdbFile && is_file(REALPATH . "/data/torrents/imdb/" . $imdbFile . ".jpg") ) 
        {
            $ImageBIG = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $imdbFile . ".jpg";
            $ImageSmall = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $imdbFile . ".jpg";
        }
        else
        {
            eval("\$return = \"" . $TSUE["TSUE_Template"]->LoadTemplate("get_torrent_images_no_image") . "\";");
            return $return;
        }

    }

    eval("\$return = \"" . $TSUE["TSUE_Template"]->LoadTemplate("get_torrent_images") . "\";");
    return $return;
}

function get_torrent_images($tid, $imdbFile = "")
{
    global $TSUE;
    $return = "";
    $Images = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE content_type = 'torrent_images' AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    if( $TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        $worked = 1;
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            if( is_valid_image($Image["filename"]) ) 
            {
                $spanClass = (1 < $worked ? "hidden" : "");
                $return .= get_torrent_image($Image["filename"], NULL, $spanClass);
                $worked++;
            }

        }
    }

    if( !$return ) 
    {
        $return = get_torrent_image("", $imdbFile);
    }

    if( !$return ) 
    {
        eval("\$return = \"" . $TSUE["TSUE_Template"]->LoadTemplate("get_torrent_images_no_image") . "\";");
    }

    return $return;
}

function get_torrent_images_for_edit($tid)
{
    global $TSUE;
    $return = "";
    $Images = $TSUE["TSUE_Database"]->query("SELECT attachment_id, filename FROM tsue_attachments WHERE content_type IN ('torrent_images','torrent_screenshots') AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    if( $TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            if( is_valid_image($Image["filename"]) ) 
            {
                eval("\$return .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("get_torrent_images_for_edit") . "\";");
            }

        }
    }

    return $return;
}

function get_torrent_screenshots($tid)
{
    global $TSUE;
    $return = "";
    $Images = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE content_type = 'torrent_screenshots' AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
    if( $TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            if( is_file(REALPATH . "/data/torrents/torrent_images/s/" . $Image["filename"]) && is_valid_image($Image["filename"]) ) 
            {
                $ImageBIG = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/l/" . $Image["filename"];
                $ImageSmall = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/s/" . $Image["filename"];
                eval("\$return .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("get_torrent_screenshots") . "\";");
            }

        }
        eval("\$return .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("clear") . "\";");
    }

    return $return;
}

function getTorrentFlags($Torrent)
{
    global $TSUE;
    $Flags = "";
    if( isset($Torrent["options"]["anonymouse"]) && $Torrent["options"]["anonymouse"] ) 
    {
        eval("\$Flags .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("flag_anonymouse_uploader") . "\";");
    }

    if( $Torrent["IMDBContent"] ) 
    {
        eval("\$Flags .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("flag_imdb_link") . "\";");
    }

    return $Flags;
}

function isAnonymouse($Torrent)
{
    global $TSUE;
    return isset($Torrent["options"]["anonymouse"]) && $Torrent["options"]["anonymouse"] && $Torrent["owner"] != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("canview_invisible_members");
}

function generateCustomUploadFields($Torrent, $isEdit)
{
    global $TSUE;
    $torrentDetails = $torrentOptions = $torrentFiles = $finishUpload = array(  );
    $Fields = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_torrents_upload_extra_fields WHERE active = 1 ORDER BY display_order ASC");
    if( $TSUE["TSUE_Database"]->num_rows($Fields) ) 
    {
        while( $Field = $TSUE["TSUE_Database"]->fetch_assoc($Fields) ) 
        {
            if( $isEdit && isset($Torrent[$Field["name"]]) && in_array($Field["type"], array( 1, 2 )) ) 
            {
                $defaultValue = array( $Torrent[$Field["name"]] );
            }
            else
            {
                $defaultValue = (!empty($Field["default_value"]) ? preg_split("/\\r?\\n/", trim($Field["default_value"]), -1, PREG_SPLIT_NO_EMPTY) : array(  ));
            }

            $tipPhrase = (!empty($Field["tip_phrase"]) ? get_phrase($Field["tip_phrase"]) : "");
            $fieldTitle = trim($Field["title"]);
            $torrentDefVal = array(  );
            if( isset($Torrent[$Field["name"]]) ) 
            {
                $torrentDefVal = explode(" | ", $Torrent[$Field["name"]]);
            }

            switch( $Field["type"] ) 
            {
                case 1:
                    $customField = "<input type=\"text\" class=\"s text\" name=\"" . $Field["name"] . "\" id=\"" . $Field["name"] . "\" value=\"" . implode("", $defaultValue) . "\" />";
                    break;
                case 2:
                    $customField = "<textarea name=\"" . $Field["name"] . "\" id=\"" . $Field["name"] . "\" class=\"tinymcePostComment\" style=\"display: inline !important;\">" . implode("", $defaultValue) . "</textarea>";
                    break;
                case 3:
                    $customField = "<select name=\"" . $Field["name"] . "\" id=\"" . $Field["name"] . "\" class=\"selectbox\">";
                    foreach( $defaultValue as $val ) 
                    {
                        $customField .= "<option value=\"" . $val . "\"" . ((in_array($val, $torrentDefVal) ? " selected=\"selected\"" : "")) . ">" . $val . "</val>";
                    }
                    $customField .= "</select>";
                    break;
                case 4:
                    $customField = "";
                    foreach( $defaultValue as $val ) 
                    {
                        $customField .= "<input type=\"checkbox\" name=\"" . $Field["name"] . "[]\" id=\"" . $Field["name"] . "\" class=\"checkbox\" value=\"" . $val . "\"" . ((in_array($val, $torrentDefVal) ? " checked=\"checked\"" : "")) . " /> " . $val . "";
                    }
                    break;
                case 5:
                    $customField = "";
                    foreach( $defaultValue as $val ) 
                    {
                        $customField .= "<input type=\"radio\" name=\"" . $Field["name"] . "[]\" id=\"" . $Field["name"] . "\" class=\"radio checkbox\" value=\"" . $val . "\"" . ((in_array($val, $torrentDefVal) ? " checked=\"checked\"" : "")) . " /> " . $val . "";
                    }
            }
            $HTMLCode = "\r\n\t\t\t<div class=\"line\">\r\n\t\t\t\t<label for=\"" . $Field["name"] . "\">" . $fieldTitle . "</label>\r\n\t\t\t\t<div>" . $customField . "</div>\r\n\t\t\t\t<div class=\"alt\">" . $tipPhrase . "</div>\r\n\t\t\t</div>";
            switch( $Field["area"] ) 
            {
                case 1:
                    $torrentDetails[] = $HTMLCode;
                    break;
                case 2:
                    $torrentOptions[] = $HTMLCode;
                    break;
                case 3:
                    $torrentFiles[] = $HTMLCode;
                    break;
                case 4:
                    $finishUpload[] = $HTMLCode;
            }
        }
    }

    return array( "torrent_details" => implode(" ", $torrentDetails), "torrent_options" => implode(" ", $torrentOptions), "torrent_files" => implode(" ", $torrentFiles), "torrent_finish_upload" => implode(" ", $finishUpload) );
}



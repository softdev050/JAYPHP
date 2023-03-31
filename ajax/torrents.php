<?php 
define("SCRIPTNAME", "torrents.php");
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

$xbtActive = getSetting("xbt", "active");
$announceInterval = ($xbtActive ? getSetting("xbt", "announce_interval") : getSetting("global_settings", "announce_interval"));
$peersTable = ($xbtActive ? "_xbt" : "");
switch( $TSUE["action"] ) 
{
    case "show_genres":
        globalize("post", array( "cid" => "INT" ));
        if( !$cid || !has_permission("canupload_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        ajax_message(showAvailableGenres(array(  ), $cid));
        break;
    case "screenshots":
        globalize("post", array( "tid" => "INT" ));
        if( !$tid || !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $screenshots = get_torrent_screenshots($tid);
        if( !$screenshots ) 
        {
            ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        }

        ajax_message($screenshots);
        break;
    case "bump_torrent":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("bump-torrent-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $Torrent["options"] = unserialize($Torrent["options"]);
        if( !hasViewPermission($Torrent["cviewpermissions"]) || !has_permission("canbump_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "added" => TIMENOW ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
        $Phrase = get_phrase("torrent_x_has_been_bumped_by_y", strip_tags($Torrent["name"]), $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]);
        logAction($Phrase);
        ajax_message($Phrase);
        break;
    case "bookmarks":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"));
        }

        if( !has_permission("canuse_bookmarks") || !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $TSUE["do"] ) 
        {
            case "add":
                globalize("post", array( "tid" => "INT" ));
                if( !$tid ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
                if( !$Torrent ) 
                {
                    ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
                }

                $Torrent["options"] = unserialize($Torrent["options"]);
                if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $buildQuery = array( "tid" => $tid, "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
                $TSUE["TSUE_Database"]->replace("tsue_bookmarks", $buildQuery);
                ajax_message(get_phrase("x_has_been_added_to_your_bookmarks", strip_tags($Torrent["name"])), "", false);
                break;
            case "remove":
                globalize("post", array( "tid" => "INT" ));
                if( !$tid ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
                if( !$Torrent ) 
                {
                    ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
                }

                $Torrent["options"] = unserialize($Torrent["options"]);
                if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $TSUE["TSUE_Database"]->delete("tsue_bookmarks", "tid=" . $TSUE["TSUE_Database"]->escape($tid) . " AND memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                ajax_message(get_phrase("x_has_been_removed_from_your_bookmarks", strip_tags($Torrent["name"])), "", false);
        }
        break;
    case "show_all_tags":
        $Tags = $TSUE["TSUE_Database"]->query("SELECT tag FROM tsue_tags ORDER BY tag ASC");
        if( !$TSUE["TSUE_Database"]->num_rows($Tags) ) 
        {
            ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        }

        $Output = "";
        while( $Tag = $TSUE["TSUE_Database"]->fetch_assoc($Tags) ) 
        {
            $tag = trim(strip_tags($Tag["tag"]));
            $tagLength = strlen($tag);
            if( 2 < $tagLength ) 
            {
                if( 33 < $tagLength ) 
                {
                    $tag = substr($tag, 0, 33) . "...";
                }

                $tagLink = urlencode($tag);
                eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_tags_link") . "\";");
            }

        }
        ajax_message($Output, "", false, get_phrase("tags"));
        break;
    case "similar_torrents":
        globalize("post", array( "tid" => "INT" ));
        if( !$tid || !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $searchString = explodeSearchKeywords("t.name", $Torrent["name"], false, true);
        $Images = array(  );
        if( $searchString ) 
        {
            $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.tid, t.name, " . $searchString . " as SCORE, a.filename FROM tsue_torrents t INNER JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) WHERE " . $searchString . " GROUP BY t.tid ORDER BY SCORE DESC, t.added DESC LIMIT 20");
            if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
            {
                while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
                {
                    if( 2 <= $Torrent["SCORE"] && $Torrent["tid"] != $tid ) 
                    {
                        $title = addslashes(strip_tags($Torrent["name"]));
                        eval("\$Images[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("similarTorrents") . "\";");
                    }

                }
            }

            if( !empty($Images) ) 
            {
                eval("\$clear = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clear") . "\";");
                ajax_message(implode(" ", $Images) . $clear, "", false);
            }

        }

        ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        break;
    case "subtitles":
        globalize("post", array( "tid" => "INT" ));
        if( !$tid || !has_permission("canview_torrents") || !has_permission("canview_subtitles") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $subTitles = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE s.*, m.membername, g.groupstyle FROM tsue_subtitles s LEFT JOIN tsue_members m ON(s.uploader=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE s.tid = " . $TSUE["TSUE_Database"]->escape($tid) . " ORDER BY s.date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($subTitles) ) 
        {
            $count = 0;
            for( $subTitleList = ""; $subTitle = $TSUE["TSUE_Database"]->fetch_assoc($subTitles); $count++ ) 
            {
                $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
                $_memberid = $subTitle["uploader"];
                $_membername = getMembername($subTitle["membername"], $subTitle["groupstyle"]);
                eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $subTitle["title"] = strip_tags($subTitle["title"]);
                $subTitle["fps"] = strip_tags($subTitle["fps"]);
                $subTitle["cd"] = intval($subTitle["cd"]);
                $subTitle["downloads"] = friendly_number_format($subTitle["downloads"]);
                $subTitle["date"] = convert_relative_time($subTitle["date"]);
                $deleteLink = "";
                $editLink = "";
                eval("\$subTitleList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_list") . "\";");
            }
            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_table_ajax") . "\";");
            ajax_message($Output);
        }

        ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        break;
    case "update_external_torrent":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canupdate_external_torrents") || !$tid || !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("update-external-torrent-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.options, c.cviewpermissions, a.filename FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) LEFT JOIN tsue_attachments a ON (a.content_type='torrent_files' AND a.content_id=t.tid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TorrentFile = REALPATH . "/data/torrents/torrent_files/" . $Torrent["filename"];
        if( !is_file($TorrentFile) || !($Data = @file_get_contents($TorrentFile)) ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        require_once(REALPATH . "/library/classes/class_torrent.php");
        $Torrent = new Torrent();
        $Torrent->load($Data);
        if( $Torrent->error ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $getTrackers = $Torrent->getTrackers();
        $infoHash = urlencode($Torrent->getHash());
        $Seeders = $Leechers = $Downloaded = 0;
        $ctx = stream_context_create(array( "http" => array( "method" => "GET", "timeout" => 5, "user_agent" => "-UT3210- uTorrent 3.2.1.0 uTorrent/3210" ) ));
        foreach( $getTrackers as $Tracker ) 
        {
            $Tracker = str_replace(array( "announce", "udp://" ), array( "scrape", "http://" ), $Tracker) . ((strpos($Tracker, "?") === false ? "?" : "&")) . "info_hash=" . $infoHash;
            $Scrape = file_get_contents($Tracker, 0, $ctx);
            if( $Scrape ) 
            {
                preg_match("#completei([0-9]+)#", $Scrape, $S);
                preg_match("#incompletei([0-9]+)#", $Scrape, $L);
                preg_match("#downloadedi([0-9]+)#", $Scrape, $D);
                if( isset($S["1"]) ) 
                {
                    $Seeders += intval($S["1"]);
                }

                if( isset($L["1"]) ) 
                {
                    $Leechers += intval($L["1"]);
                }

                if( isset($D["1"]) ) 
                {
                    $Downloaded += intval($D["1"]);
                }

            }

        }
        $buildQuery = array( "leechers" => $Leechers, "seeders" => $Seeders, "times_completed" => $Downloaded );
        $TSUE["TSUE_Database"]->update("tsue_torrents", $buildQuery, "tid = " . $TSUE["TSUE_Database"]->escape($tid));
        break;
    case "upload_torrent":
        if( !has_permission("canupload_torrents") || !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "tid" => "INT", "editTID" => "INT", "name" => "TRIM", "rDescription" => "TRIM", "cid" => "INT", "annonymouse" => "INT", "record_stats" => "INT", "hitRunRatio" => "INT", "download_multiplier" => "INT", "upload_multiplier" => "INT", "imdb" => "TRIM", "attachment_ids" => "ARRAY", "ss_attachment_ids" => "ARRAY", "uploadStep" => "INT", "sticky" => "INT", "external" => "INT", "tags" => "TRIM", "gids" => "ARRAY" ));
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        if( $editTID ) 
        {
            $TSUE["action"] = "edit_torrent";
        }

        $detectedErrors = "";
        $canset_multipliers = has_permission("canset_multipliers");
        $canset_hitrun_ratio = has_permission("canset_hitrun_ratio");
        $cansticky_torrents = has_permission("cansticky_torrents");
        if( !$name ) 
        {
            $detectedErrors[] = get_phrase("torrent_upload_invalid_name");
        }

        if( !$rDescription ) 
        {
            $detectedErrors[] = get_phrase("torrent_upload_invalid_description");
        }

        if( !$cid ) 
        {
            $detectedErrors[] = get_phrase("torrent_upload_invalid_cid");
        }
        else
        {
            $torrentCategory = $TSUE["TSUE_Database"]->query_result("SELECT cviewpermissions FROM tsue_torrents_categories WHERE cid = " . $TSUE["TSUE_Database"]->escape($cid));
            if( !$torrentCategory ) 
            {
                $detectedErrors[] = get_phrase("torrent_upload_invalid_cid");
            }
            else
            {
                if( !hasViewPermission($torrentCategory["cviewpermissions"]) ) 
                {
                    $detectedErrors[] = get_phrase("torrent_upload_invalid_cid");
                }

            }

        }

        if( !$canset_multipliers ) 
        {
            $record_stats = 1;
        }

        if( !is_numeric($download_multiplier) || !$canset_multipliers ) 
        {
            $download_multiplier = "1.0";
        }

        if( !is_numeric($upload_multiplier) || !$canset_multipliers ) 
        {
            $upload_multiplier = "1.0";
        }

        if( !is_numeric($hitRunRatio) || !$canset_hitrun_ratio ) 
        {
            $hitRunRatio = "";
        }

        if( !is_numeric($sticky) || !$cansticky_torrents ) 
        {
            $sticky = "";
        }

        if( !is_numeric($external) || !$external || getSetting("global_settings", "announce_private_torrents_only") ) 
        {
            $external = 0;
        }

        if( $gids ) 
        {
            $gCache = array(  );
            foreach( $gids as $gid ) 
            {
                $gid = intval($gid);
                if( $gid ) 
                {
                    $gCache[] = $gid;
                }

            }
            if( $gCache && $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] ) 
            {
                $List = array(  );
                foreach( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] as $Genre ) 
                {
                    if( in_array($Genre["gid"], $gCache) ) 
                    {
                        $List[] = $Genre["gname"];
                    }

                }
                $gids = implode("~", $List);
                unset($gCache);
                unset($List);
            }
            else
            {
                $gids = "";
            }

        }
        else
        {
            $gids = "";
        }

        if( $tags ) 
        {
            $tags = tsue_explode(",", clearTags(strip_tags(trim($tags))));
            if( $tags ) 
            {
                $safeTags = array(  );
                foreach( $tags as $tag ) 
                {
                    $tag = trim(strip_tags($tag));
                    if( 2 < strlen($tag) ) 
                    {
                        $tag = ucfirst(strtolower($tag));
                        if( !in_array($tag, $safeTags) && $tag ) 
                        {
                            $safeTags[] = $tag;
                            $TSUE["TSUE_Database"]->replace("tsue_tags", array( "tag" => $tag ));
                        }

                    }

                }
                $tags = implode(",", $safeTags);
                unset($safeTags);
            }
            else
            {
                $tags = "";
            }

        }

        if( empty($attachment_ids) && $TSUE["action"] == "upload_torrent" ) 
        {
            $detectedErrors[] = get_phrase("torrent_upload_invalid_torrent_file");
        }
        else
        {
            if( !empty($attachment_ids) ) 
            {
                $aCache = array(  );
                $validTorrentFileFound = false;
                foreach( $attachment_ids as $attachment_id ) 
                {
                    $attachment_id = intval($attachment_id);
                    if( 0 < $attachment_id ) 
                    {
                        $aCache[] = $attachment_id;
                    }

                }
                if( !empty($aCache) ) 
                {
                    $checkAttachments = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE attachment_id IN (" . implode(",", $aCache) . ") AND content_type = 'torrent_files'");
                    if( $TSUE["TSUE_Database"]->num_rows($checkAttachments) ) 
                    {
                        while( $attachment = $TSUE["TSUE_Database"]->fetch_assoc($checkAttachments) ) 
                        {
                            if( substr($attachment["filename"], -8) == ".torrent" ) 
                            {
                                $validTorrentFileFound = $attachment["filename"];
                            }
                            else
                            {
                                if( substr($attachment["filename"], -4) == ".nfo" ) 
                                {
                                    $validNFOFound = $attachment["filename"];
                                }

                            }

                        }
                    }

                }

                if( !$validTorrentFileFound && $TSUE["action"] == "upload_torrent" ) 
                {
                    $detectedErrors[] = get_phrase("torrent_upload_invalid_torrent_file") . " 2";
                }

            }

        }

        if( !empty($ss_attachment_ids) ) 
        {
            $ssCache = array(  );
            foreach( $ss_attachment_ids as $ss_attachment_id ) 
            {
                $ss_attachment_id = intval($ss_attachment_id);
                if( 0 < $ss_attachment_id ) 
                {
                    $ssCache[] = $ss_attachment_id;
                }

            }
        }

        if( is_array($detectedErrors) ) 
        {
            ajax_message(implode("<br>", $detectedErrors), "-ERROR-");
            break;
        }

        switch( $uploadStep ) 
        {
            case 1:
                ajax_message(get_phrase("torrent_upload_step_1"));
                break;
            case 2:
                if( $TSUE["action"] == "edit_torrent" ) 
                {
                    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, t.size, t.upload_multiplier, t.download_multiplier, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($editTID));
                    if( !$Torrent ) 
                    {
                        ajax_message(get_phrase("torrents_not_found") . " (S: " . $uploadStep . ")", "-ERROR-");
                    }

                    if( !hasViewPermission($Torrent["cviewpermissions"]) || !canEditTorrent($Torrent) ) 
                    {
                        ajax_message(get_phrase("permission_denied"), "-ERROR-");
                    }

                    $Torrent["options"] = unserialize($Torrent["options"]);
                    if( isset($validTorrentFileFound) && $validTorrentFileFound ) 
                    {
                        $saveTorrent = saveTorrent($validTorrentFileFound);
                        $_attachments = $TSUE["TSUE_Database"]->query("SELECT attachment_id,filename FROM tsue_attachments WHERE content_type = 'torrent_files' AND associated = 1 AND content_id = " . $TSUE["TSUE_Database"]->escape($editTID));
                        if( $TSUE["TSUE_Database"]->num_rows($_attachments) ) 
                        {
                            $_deleteAttachments = array(  );
                            while( $_attachment = $TSUE["TSUE_Database"]->fetch_assoc($_attachments) ) 
                            {
                                if( substr($attachment["filename"], -4) != ".nfo" ) 
                                {
                                    $_deleteAttachments[] = $_attachment["attachment_id"];
                                    $file = REALPATH . "/data/torrents/torrent_files/" . $_attachment["filename"];
                                    if( is_file($file) ) 
                                    {
                                        @unlink($file);
                                    }

                                }

                            }
                            if( $_deleteAttachments ) 
                            {
                                $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id IN (" . implode(",", $_deleteAttachments) . ")");
                            }

                        }

                    }

                    if( isset($validNFOFound) && $validNFOFound ) 
                    {
                        $_attachments = $TSUE["TSUE_Database"]->query("SELECT attachment_id,filename FROM tsue_attachments WHERE content_type = 'torrent_files' AND associated = 1 AND content_id = " . $TSUE["TSUE_Database"]->escape($editTID));
                        if( $TSUE["TSUE_Database"]->num_rows($_attachments) ) 
                        {
                            $_deleteAttachments = array(  );
                            while( $_attachment = $TSUE["TSUE_Database"]->fetch_assoc($_attachments) ) 
                            {
                                if( substr($attachment["filename"], -4) == ".nfo" ) 
                                {
                                    $_deleteAttachments[] = $_attachment["attachment_id"];
                                    $file = REALPATH . "/data/torrents/nfo/" . $_attachment["filename"];
                                    if( is_file($file) ) 
                                    {
                                        @unlink($file);
                                    }

                                }

                            }
                            if( $_deleteAttachments ) 
                            {
                                $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id IN (" . implode(",", $_deleteAttachments) . ")");
                            }

                        }

                    }

                    if( isset($aCache) && !empty($aCache) ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $tid, "associated" => 1 ), "attachment_id IN (" . implode(",", $aCache) . ")");
                    }

                    if( isset($ssCache) && !empty($ssCache) ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $tid, "associated" => 1 ), "attachment_id IN (" . implode(",", $ssCache) . ")");
                    }

                    $torrent_options = array( "anonymouse" => $annonymouse, "record_stats" => $record_stats, "upload_multiplier" => $upload_multiplier, "download_multiplier" => $download_multiplier, "nuked" => "", "hitRunRatio" => $hitRunRatio );
                    if( !$canset_multipliers ) 
                    {
                        $torrent_options["record_stats"] = $Torrent["options"]["record_stats"];
                        $torrent_options["upload_multiplier"] = $Torrent["options"]["upload_multiplier"];
                        $torrent_options["download_multiplier"] = $Torrent["options"]["download_multiplier"];
                    }

                    if( !$canset_hitrun_ratio ) 
                    {
                        $torrent_options["hitRunRatio"] = $Torrent["options"]["hitRunRatio"];
                    }

                    if( $imdb ) 
                    {
                        $torrent_options["imdb"] = $imdb;
                    }

                    $torrent_options = serialize(array_merge($Torrent["options"], $torrent_options));
                    $BuildQuery = array( "name" => $name, "description" => $rDescription, "cid" => $cid, "options" => $torrent_options, "flags" => 2, "upload_multiplier" => $upload_multiplier, "download_multiplier" => $download_multiplier, "external" => $external, "tags" => $tags, "gids" => $gids );
                    if( !$canset_multipliers ) 
                    {
                        $BuildQuery["upload_multiplier"] = $Torrent["upload_multiplier"];
                        $BuildQuery["download_multiplier"] = $Torrent["download_multiplier"];
                    }

                    if( is_numeric($sticky) && $cansticky_torrents ) 
                    {
                        $BuildQuery["sticky"] = $sticky;
                    }

                    if( isset($saveTorrent["info_hash"]) ) 
                    {
                        $BuildQuery["info_hash"] = $saveTorrent["info_hash"];
                    }

                    if( isset($saveTorrent["size"]) ) 
                    {
                        $BuildQuery["size"] = $saveTorrent["size"];
                    }

                    $editPhrase = get_phrase("torrent_has_been_edited", $name, $editTID, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]);
                    logAction($editPhrase);
                    if( $TSUE["TSUE_Database"]->update("tsue_torrents", $BuildQuery, "tid=" . $TSUE["TSUE_Database"]->escape($editTID)) ) 
                    {
                        echo "~tid~" . $editTID;
                        exit();
                    }

                    ajax_message(get_phrase("database_error"), "-ERROR-");
                }
                else
                {
                    $saveTorrent = saveTorrent($validTorrentFileFound);
                    $searchTorrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE info_hash = " . $TSUE["TSUE_Database"]->escape($saveTorrent["info_hash"]));
                    if( $searchTorrent ) 
                    {
                        ajax_message(get_phrase("torrent_this_torrent_already_exists"), "-ERROR-");
                    }

                    $awaitingModeration = get_permission("canupload_torrents") == 2;
                    $torrent_options = array( "anonymouse" => $annonymouse, "record_stats" => $record_stats, "upload_multiplier" => $upload_multiplier, "download_multiplier" => $download_multiplier, "imdb" => $imdb, "nuked" => "", "hitRunRatio" => $hitRunRatio );
                    $BuildQuery = array( "info_hash" => $saveTorrent["info_hash"], "name" => $name, "description" => $rDescription, "cid" => $cid, "size" => $saveTorrent["size"], "added" => TIMENOW, "owner" => $TSUE["TSUE_Member"]->info["memberid"], "options" => serialize($torrent_options), "nfo" => "", "flags" => 2, "ctime" => TIMENOW, "upload_multiplier" => $upload_multiplier, "download_multiplier" => $download_multiplier, "external" => $external, "tags" => $tags, "awaitingModeration" => ($awaitingModeration ? 1 : 0), "gids" => $gids );
                    if( is_numeric($sticky) ) 
                    {
                        $BuildQuery["sticky"] = $sticky;
                    }

                    if( $TSUE["TSUE_Database"]->insert("tsue_torrents", $BuildQuery) && ($tid = $TSUE["TSUE_Database"]->insert_id()) ) 
                    {
                        $uploadPhrase = get_phrase("torrent_has_been_uploaded", $name, $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]);
                        logAction($uploadPhrase);
                        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $tid, "associated" => 1 ), "attachment_id IN (" . implode(",", $aCache) . ")");
                        if( isset($ssCache) && !empty($ssCache) ) 
                        {
                            $TSUE["TSUE_Database"]->update("tsue_attachments", array( "content_id" => $tid, "associated" => 1 ), "attachment_id IN (" . implode(",", $ssCache) . ")");
                        }

                        if( !$awaitingModeration ) 
                        {
                            deleteCache(array( "TSUEPlugin_recentTorrents_", "TSUEPlugin_topUploaders_" ));
                            updateMemberPoints(getSetting("global_settings", "points_torrent_upload"), $TSUE["TSUE_Member"]->info["memberid"]);
                            ircAnnouncement("new_torrent", $tid, $name);
                        }
                        else
                        {
                            $searchMembergroups = searchPermissionInMembergroups("can_moderate_torrents");
                            if( $searchMembergroups ) 
                            {
                                $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                                if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                                {
                                    while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                                    {
                                        alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "torrent", $tid, "t_upload_awaiting_moderation");
                                    }
                                }

                            }

                        }

                        echo "~tid~" . $tid;
                        exit();
                    }

                    ajax_message(get_phrase("database_error"), "-ERROR-");
                }

                break;
            case 3:
                if( !$tid && !$editTID ) 
                {
                    ajax_message(get_phrase("torrents_not_found") . " (S: " . $uploadStep . ")", "-ERROR-");
                }

                ajax_message(get_phrase("torrent_upload_step_2"));
                break;
            case 4:
                if( !$tid && !$editTID ) 
                {
                    ajax_message(get_phrase("torrents_not_found") . " (S: " . $uploadStep . ")", "-ERROR-");
                }

                if( $imdb ) 
                {
                    if( $TSUE["action"] == "edit_torrent" ) 
                    {
                        $IMDB = $TSUE["TSUE_Database"]->query_result("SELECT content FROM tsue_imdb WHERE tid = " . $TSUE["TSUE_Database"]->escape($editTID));
                        if( $IMDB ) 
                        {
                            $IMDBContent = unserialize($IMDB["content"]);
                            if( $IMDBContent["title_id"] && is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
                            {
                                @unlink(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg");
                            }

                        }

                    }

                    require_once(REALPATH . "/library/classes/class_imdb.php");
                    $IMDB = new IMDB($imdb);
                    if( 2 < count($IMDB->movieInfo) ) 
                    {
                        $BuildQuery = array( "tid" => ($tid ? $tid : $editTID), "content" => serialize($IMDB->movieInfo) );
                        $TSUE["TSUE_Database"]->replace("tsue_imdb", $BuildQuery);
                        $IMDB->posterPath = REALPATH . "/data/torrents/imdb/";
                        $IMDB->savePoster($IMDB->movieInfo["poster"]);
                    }

                }

                if( $TSUE["action"] != "edit_torrent" && $tid ) 
                {
                    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT tid, name, description, cid, awaitingModeration FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
                    if( $Torrent && !$Torrent["awaitingModeration"] ) 
                    {
                        shoutboxAnnouncement(array( "new_torrent", $Torrent["tid"], strip_tags($Torrent["name"]), substr(strip_tags($Torrent["description"]), 0, 200) . " ...", $Torrent["cid"] ));
                    }

                }

                ajax_message(get_phrase("torrent_upload_step_3"));
                break;
            case 5:
                if( !$tid && !$editTID ) 
                {
                    ajax_message(get_phrase("torrents_not_found") . " (S: " . $uploadStep . ")", "-ERROR-");
                }

                $tid = ($tid ? $tid : $editTID);
                $checkAttachments = $TSUE["TSUE_Database"]->query("SELECT attachment_id,filename FROM tsue_attachments WHERE content_type = 'torrent_files' AND associated = 1 AND content_id = " . $TSUE["TSUE_Database"]->escape($tid));
                if( $TSUE["TSUE_Database"]->num_rows($checkAttachments) ) 
                {
                    while( $attachment = $TSUE["TSUE_Database"]->fetch_assoc($checkAttachments) ) 
                    {
                        if( substr($attachment["filename"], -4) == ".nfo" ) 
                        {
                            $file = REALPATH . "/data/torrents/nfo/" . $attachment["filename"];
                            if( is_file($file) ) 
                            {
                                $NFOContents = file_get_contents($file);
                                @unlink($file);
                                $TSUE["TSUE_Database"]->update("tsue_torrents", array( "nfo" => $NFOContents ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
                            }

                            $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id=" . $TSUE["TSUE_Database"]->escape($attachment["attachment_id"]));
                        }

                    }
                }

                ajax_message(get_phrase("torrent_upload_step_1_done"));
                break;
            case 6:
                if( !$tid && !$editTID ) 
                {
                    ajax_message(get_phrase("torrents_not_found") . " (S: " . $uploadStep . ")", "-ERROR-");
                }

                $tid = ($tid ? $tid : $editTID);
                $Fields = $TSUE["TSUE_Database"]->query("SELECT name, type FROM tsue_torrents_upload_extra_fields WHERE active = 1 ORDER BY display_order ASC");
                if( $TSUE["TSUE_Database"]->num_rows($Fields) ) 
                {
                    $updateQueries = array(  );
                    while( $Field = $TSUE["TSUE_Database"]->fetch_assoc($Fields) ) 
                    {
                        if( isset($_POST[$Field["name"]]) ) 
                        {
                            $postedField = $_POST[$Field["name"]];
                            if( in_array($Field["type"], array( 4, 5 )) ) 
                            {
                                $postedField = implode(" | ", $postedField);
                            }

                            $updateQueries[$Field["name"]] = strip_tags(trim($postedField));
                        }

                    }
                    $TSUE["TSUE_Database"]->update("tsue_torrents", $updateQueries, "tid=" . $TSUE["TSUE_Database"]->escape($tid));
                }

                if( get_permission("canupload_torrents") == 2 && $TSUE["action"] != "edit_torrent" ) 
                {
                    echo "~moderationmessage~" . get_phrase("torrent_has_been_uploaded_but_awaiting_moderation");
                }

                break;
        }
        exit();
    case "delete_torrent":
        globalize("post", array( "tid" => "INT", "reason" => "TRIM" ));
        if( !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.awaitingModeration, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        if( !hasViewPermission($Torrent["cviewpermissions"]) || !canDeleteTorrent($Torrent) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$reason ) 
        {
            eval("\$delete_torrent_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_torrent_form") . "\";");
            ajax_message($delete_torrent_form, NULL, false, get_phrase("button_delete") . ": " . strip_tags($Torrent["name"]));
        }

        check_flood("delete-torrent");
        $deletePhrase = get_phrase("torrent_has_been_deleted", strip_tags($Torrent["name"]), $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]);
        deleteTorrent($tid, $deletePhrase, $Torrent["owner"], $Torrent["awaitingModeration"]);
        $subject = get_phrase("your_torrent_has_been_deleted");
        $reply = nl2br(get_phrase("your_torrent_was_deleted_x_y_z", strip_tags($Torrent["name"]), getMembername($TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"]), strip_tags($reason)));
        sendPM($subject, $TSUE["TSUE_Member"]->info["memberid"], $Torrent["owner"], $reply);
        ajax_message($deletePhrase);
        break;
    case "nuke_torrent":
        globalize("post", array( "tid" => "INT", "reason" => "" ));
        if( !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("nuke-torrent-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $Torrent["options"] = unserialize($Torrent["options"]);
        if( !hasViewPermission($Torrent["cviewpermissions"]) || !has_permission("cannuke_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $Torrent["options"]["nuked"] ) 
        {
            ajax_message(get_phrase("already_nuked"), "-ERROR-");
        }

        if( $TSUE["do"] == "form" ) 
        {
            eval("\$nuke_torrent_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("nuke_torrent_form") . "\";");
            ajax_message($nuke_torrent_form, NULL, false, strip_tags($Torrent["name"]));
        }

        if( !$reason ) 
        {
            ajax_message(get_phrase("nuke_reason"));
        }

        $Torrent["options"]["nuked"] = $reason;
        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "options" => serialize($Torrent["options"]) ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
        if( $xbtActive ) 
        {
            $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE uid as memberid FROM xbt_files_users WHERE fid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND `active` = 1");
        }
        else
        {
            $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid FROM tsue_torrents_peers WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND `active` = 1");
        }

        if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
        {
            $subject = get_phrase("simple_torrent_has_been_nuked");
            $reply = nl2br(get_phrase("torrent_x_have_been_nuked_pm_msg", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;action=details&amp;tid=" . $tid, strip_tags($Torrent["name"]), getMembername($TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"]), strip_tags($reason)));
            while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
            {
                sendPM($subject, $TSUE["TSUE_Member"]->info["memberid"], $Peer["memberid"], $reply);
            }
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $newLink = canNukeTorrent($Torrent);
        logAction(get_phrase("torrent_has_been_nuked", strip_tags($Torrent["name"]), $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]));
        ajax_message(get_phrase("simple_torrent_has_been_nuked") . "~~~" . $newLink);
        break;
    case "unnuke_torrent":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("unnuke-torrent-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.name, t.owner, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) || !has_permission("cannuke_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Torrent["options"] = unserialize($Torrent["options"]);
        $Torrent["options"]["nuked"] = "";
        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "options" => serialize($Torrent["options"]) ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $newLink = canNukeTorrent($Torrent);
        logAction(get_phrase("torrent_has_been_un_nuked", strip_tags($Torrent["name"]), $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]));
        ajax_message(get_phrase("simple_torrent_has_been_un_nuked") . "~~~" . $newLink);
        break;
    case "delete_torrent_image":
        globalize("post", array( "attachment_id" => "INT" ));
        if( !has_permission("canview_torrents") || !has_permission("canupload_torrents") || !$attachment_id ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        $Attachment = $TSUE["TSUE_Database"]->query_result("SELECT a.filename, t.tid, t.owner, c.cviewpermissions FROM tsue_attachments a INNER JOIN tsue_torrents t ON (a.content_id=t.tid) LEFT JOIN tsue_torrents_categories c USING(cid) WHERE a.attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id) . " AND a.content_type IN ('torrent_images','torrent_screenshots')");
        if( !$Attachment ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        if( !canEditTorrent($Attachment) || !hasViewPermission($Attachment["cviewpermissions"]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        deleteImages($Attachment["filename"]);
        $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id) . " AND content_type IN ('torrent_images','torrent_screenshots')");
        exit();
    case "torrent_trailer":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("torrent-trailer-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, i.content, c.cviewpermissions FROM tsue_torrents t INNER JOIN tsue_imdb i USING(tid) LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

function safeSearchQuery($string = "", $delimer = "+")
{
    if( ctype_digit($string) || !$string ) 
    {
        return $string;
    }

    $string = preg_replace("#[^a-zA-Z0-9]#", $delimer, $string);
    $string = preg_replace("#\\" . $delimer . "\\" . $delimer . "+#", $delimer, $string);
    return trim(strtolower($string));
}

        $IMDBContent = unserialize($Torrent["content"]);
        if( $IMDBContent["title"] ) 
        {
            $q = safeSearchQuery($IMDBContent["title"]);
            $q .= "+trailer";
            $feedURL = "http://gdata.youtube.com/feeds/api/videos?q=" . $q . "&start-index=1&max-results=1";
            $sxml = simplexml_load_file($feedURL);
            if( $sxml ) 
            {
                foreach( $sxml->entry as $entry ) 
                {
                    $media = $entry->children("http://search.yahoo.com/mrss/");
                    $attrs = $media->group->player->attributes();
                    $trailer = $attrs["url"];
                }
                if( isset($trailer) && $trailer ) 
                {
                    $trailer = str_replace("watch?v=", "v/", $trailer);
                    eval("\$trailer = \"" . $TSUE["TSUE_Template"]->LoadTemplate("trailer") . "\";");
                    ajax_message($trailer, NULL, false, strip_tags($Torrent["name"]) . " - " . get_phrase("torrent_trailer"));
                }

            }

        }

        ajax_message(get_phrase("message_content_error"), "-ERROR-");
        break;
    case "refresh_imdb":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canrefresh_imdb") || !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("refresh-imdb-" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.tid, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $torrentOptions = unserialize($Torrent["options"]);
        if( !hasViewPermission($Torrent["cviewpermissions"]) || !$torrentOptions["imdb"] ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require_once(REALPATH . "/library/classes/class_imdb.php");
        $IMDB = new IMDB($torrentOptions["imdb"]);
        if( 2 < count($IMDB->movieInfo) ) 
        {
            $BuildQuery = array( "tid" => $tid, "content" => serialize($IMDB->movieInfo) );
            $TSUE["TSUE_Database"]->replace("tsue_imdb", $BuildQuery);
            $IMDB->posterPath = REALPATH . "/data/torrents/imdb/";
            if( is_file($IMDB->posterPath . $torrentOptions["imdb"] . ".jpg") ) 
            {
                @unlink($IMDB->posterPath . $torrentOptions["imdb"] . ".jpg");
            }

            $IMDB->savePoster($IMDB->movieInfo["poster"]);
            require_once(REALPATH . "/library/functions/functions_getTorrents.php");
            $Torrent["IMDBContent"] = $IMDB->movieInfo;
            ajax_message(IMDBContent($Torrent["IMDBContent"], $tid));
        }
        else
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        break;
    case "reseed_request":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canrequest_reseed") || !has_permission("canview_torrents") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("reseed-request-" . $tid, getSetting("global_settings", "reseed_request_flood_limit") * 24 * 60 * 60);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.seeders, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) || 0 < $Torrent["seeders"] ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $xbtActive ) 
        {
            $completedMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE uid as memberid FROM xbt_files_users WHERE fid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND `active` = 0 AND `left` = 0 ORDER BY `mtime` ASC LIMIT 0, 30");
        }
        else
        {
            $completedMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid FROM tsue_torrents_peers WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND `active` = 0 AND `left` = 0 ORDER BY `last_updated` ASC LIMIT 0, 30");
        }

        if( !$TSUE["TSUE_Database"]->num_rows($completedMembers) ) 
        {
            ajax_message(get_phrase("torrent_reseed_no_member_found"), "-ERROR-");
        }

        while( $CM = $TSUE["TSUE_Database"]->fetch_assoc($completedMembers) ) 
        {
            alert_member($CM["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "reseed", $tid, "request");
        }
        $reseedPhrase = get_phrase("torrent_reseed_request_sent", $Torrent["name"], $tid, $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["memberid"]);
        logAction($reseedPhrase);
        ajax_message(get_phrase("torrent_reseed_request_done"), "-INFORMATION-", true, get_phrase("torrent_reseed_request"));
        break;
    case "times_completed":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canview_torrents") || !has_permission("canview_peers") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("torrent_times_completed_" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }
        else
        {
            $Torrent["options"] = unserialize($Torrent["options"]);
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $xbtActive ) 
        {
            $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.announced, p.active, p.uploaded as total_uploaded, p.downloaded as total_downloaded, t.tid, t.name, t.size, m.memberid, m.membername, g.groupstyle FROM xbt_files_users p LEFT JOIN tsue_torrents t ON(p.fid=t.tid) LEFT JOIN tsue_members m ON(p.uid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE p.fid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND p.left= 0 AND p.downloaded > 0 ORDER BY p.active DESC, p.mtime DESC");
        }
        else
        {
            $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.peer_id, COUNT(p.memberid) AS totalDownloads, SUM(p.announced) AS announced, p.active,  SUM(p.total_uploaded) AS total_uploaded, SUM(p.total_downloaded) AS total_downloaded, t.tid, t.name, t.size, m.memberid, m.membername, g.groupstyle FROM tsue_torrents_peers p LEFT JOIN tsue_torrents t USING(tid) LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE p.tid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND p.left= 0 GROUP BY p.active, p.tid, p.memberid ORDER BY m.membername ASC");
        }

        if( !$TSUE["TSUE_Database"]->num_rows($downloadHistory) ) 
        {
            ajax_message(get_phrase("no_results_found"), "-INFORMATION-");
        }

        $TSUE["TSUE_Language"]->phrase["torrent_name"] = get_phrase("torrents_peer_membername");
        $historyList = "";
        for( $count = 0; $History = $TSUE["TSUE_Database"]->fetch_assoc($downloadHistory); $count++ ) 
        {
            $_memberid = $History["memberid"];
            $_membername = getMembername($History["membername"], $History["groupstyle"]) . " (x" . $History["totalDownloads"] . ")";
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $History["ratio"] = member_ratio($History["total_uploaded"], $History["total_downloaded"]);
            $History["size"] = friendly_size($History["size"]);
            $History["uploaded"] = friendly_size($History["total_uploaded"]);
            $History["downloaded"] = friendly_size($History["total_downloaded"]);
            $totalSeedTime = $History["announced"] * $announceInterval;
            $History["announced"] = friendly_number_format($History["announced"]) . " (" . convertSeconds($totalSeedTime) . ")";
            $Image = array( "src" => getImagesFullURL() . "member_profile/inactive.png", "alt" => "", "title" => "", "class" => "middle", "id" => "", "rel" => "" );
            if( $History["active"] == 1 ) 
            {
                $Image["src"] = getImagesFullURL() . "member_profile/active.png";
            }

            $History["active"] = getImage($Image);
            if( !$xbtActive ) 
            {
                $History["peer_id"] = friendlyPeerID($History["peer_id"]);
            }

            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$historyList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("snatch_list" . $peersTable) . "\";");
        }
        eval("\$snatch_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("snatch_table" . $peersTable) . "\";");
        ajax_message($snatch_table, NULL, true, strip_tags($History["name"]));
        break;
    case "torrent_seeders":
    case "torrent_leechers":
        globalize("post", array( "tid" => "INT", "requestedSortBy" => "TRIM", "requestedSortOrder" => "TRIM" ));
        if( !has_permission("canview_torrents") || !has_permission("canview_peers") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("torrent_peers_" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.options, t.external, t.owner, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }
        else
        {
            $Torrent["options"] = unserialize($Torrent["options"]);
            $torrentName = strip_tags($Torrent["name"]);
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $availableSortBys = explode(",", "membername,downloaded,uploaded,announced,last_updated,peer_id");
        $phrases = array( "membername" => get_phrase("torrents_peer_membername"), "downloaded" => get_phrase("stats_downloaded"), "uploaded" => get_phrase("stats_uploaded"), "announced" => get_phrase("torrents_peer_announced"), "last_updated" => get_phrase("torrents_peer_last_updated"), "peer_id" => get_phrase("peer_id") );
        if( !$requestedSortBy || !in_array($requestedSortBy, $availableSortBys) ) 
        {
            $requestedSortBy = "uploaded";
        }

        $sortBySelectbox = "\r\n\t\t\t" . get_phrase("torrents_sort_by") . ": \r\n\t\t\t<select name=\"requestedSortBy\" id=\"requestedSortBy\" class=\"s\" style=\"width: 122px;\">";
        foreach( $availableSortBys as $_n ) 
        {
            if( !($xbtActive && $_n == "peer_id") ) 
            {
                $sortBySelectbox .= "\r\n\t\t\t\t<option value=\"" . $_n . "\"" . (($requestedSortBy == $_n ? " selected=\"selected\"" : "")) . ">" . $phrases[$_n] . "</option>";
            }

        }
        $sortBySelectbox .= "\r\n\t\t\t</select>";
        $availableSortOrders = array( "ASC", "DESC" );
        $phrases = array( "ASC" => get_phrase("torrents_sort_order_asc"), "DESC" => get_phrase("torrents_sort_order_desc") );
        if( !$requestedSortOrder || !in_array($requestedSortOrder, $availableSortOrders) ) 
        {
            $requestedSortOrder = "DESC";
        }

        $sortOrderSelectbox = "\r\n\t\t\t" . get_phrase("torrents_sort_order") . ": \r\n\t\t\t<select name=\"requestedSortOrder\" id=\"requestedSortOrder\" class=\"s\" style=\"width: 122px;\">";
        foreach( $availableSortOrders as $_n ) 
        {
            $sortOrderSelectbox .= "\r\n\t\t\t<option value=\"" . $_n . "\"" . (($requestedSortOrder == $_n ? " selected=\"selected\"" : "")) . ">" . $phrases[$_n] . "</option>";
        }
        $sortOrderSelectbox .= "\r\n\t\t\t</select>";
        $selectBox = "<span class=\"floatright\">" . $sortBySelectbox . $sortOrderSelectbox . " <input type=\"button\" id=\"sortpeerlist\" class=\"submit\" value=\"" . get_phrase("button_apply") . "\" /></span>";
        if( $requestedSortBy == "membername" ) 
        {
            $requestedSortBy = "m.membername";
        }
        else
        {
            $requestedSortBy = "p." . $requestedSortBy;
        }

        if( $xbtActive ) 
        {
            $requestedSortBy = str_replace("p.last_updated", "p.mtime", $requestedSortBy);
            $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.announced, p.left, p.mtime as last_updated, p.active, p.port, p.uploaded as total_uploaded, p.downloaded as total_downloaded, p.up_rate as upload_speed, p.down_rate as download_speed, m.memberid, m.membername, m.ipaddress, g.groupstyle FROM xbt_files_users p LEFT JOIN tsue_members m ON(p.uid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE p.fid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND p.active=1 ORDER BY " . $requestedSortBy . " " . $requestedSortOrder);
        }
        else
        {
            $downloadHistory = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.*, m.membername, g.groupstyle FROM tsue_torrents_peers p LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE p.tid = " . $TSUE["TSUE_Database"]->escape($tid) . " AND p.active=1 ORDER BY " . $requestedSortBy . " " . $requestedSortOrder);
        }

        if( !$TSUE["TSUE_Database"]->num_rows($downloadHistory) ) 
        {
            if( !$Torrent["external"] ) 
            {
                $TSUE["TSUE_Database"]->update("tsue_torrents", array( "seeders" => 0, "leechers" => 0 ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
            }

            ajax_message(get_phrase("torrents_no_peers"), "-INFORMATION-");
        }

        $seedersCache = $leechersCache = array(  );
        while( $History = $TSUE["TSUE_Database"]->fetch_assoc($downloadHistory) ) 
        {
            if( $History["left"] == 0 ) 
            {
                $seedersCache[] = $History;
            }
            else
            {
                $leechersCache[] = $History;
            }

        }
        $ShowIpPort = has_permission("canview_special_details");
        if( $seedersCache ) 
        {
            $historyList = "";
            $count = 0;
            foreach( $seedersCache as $History ) 
            {
                $_memberid = $History["memberid"];
                $_membername = getMembername($History["membername"], $History["groupstyle"]);
                eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                if( $_memberid == $Torrent["owner"] && $Torrent["options"]["anonymouse"] && $_memberid != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("canview_invisible_members") ) 
                {
                    $_memberid = 0;
                    $_membername = $member_info_link = get_phrase("torrents_anonymouse_uploader");
                }

                $content_type = "peer";
                $content_id = $_memberid;
                eval("\$report = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post") . "\";");
                $ipaddress = ($ShowIpPort ? $History["ipaddress"] . ":" . $History["port"] : "");
                $History["ratio"] = member_ratio($History["total_uploaded"], $History["total_downloaded"]);
                $History["uploaded"] = friendly_size($History["total_uploaded"]);
                $History["upload_speed"] = friendly_size($History["upload_speed"]);
                $History["downloaded"] = friendly_size($History["total_downloaded"]);
                $History["download_speed"] = friendly_size($History["download_speed"]);
                $totalSeedTime = $History["announced"] * $announceInterval;
                $History["announced"] = friendly_number_format($History["announced"]) . " (" . convertSeconds($totalSeedTime) . ")";
                $History["last_updated"] = convert_relative_time($History["last_updated"]);
                if( !$xbtActive ) 
                {
                    $History["peer_id"] = friendlyPeerID($History["peer_id"]);
                }

                $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
                eval("\$historyList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("peers_list" . $peersTable) . "\";");
                $count++;
            }
            eval("\$tabs['seeders'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("peers_table" . $peersTable) . "\";");
        }
        else
        {
            $tabs["seeders"] = get_phrase("no_results_found");
        }

        if( $leechersCache ) 
        {
            $historyList = "";
            $count = 0;
            foreach( $leechersCache as $History ) 
            {
                $_memberid = $History["memberid"];
                $_membername = getMembername($History["membername"], $History["groupstyle"]);
                eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                if( $_memberid == $Torrent["owner"] && $Torrent["options"]["anonymouse"] && $_memberid != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("canview_invisible_members") ) 
                {
                    $_memberid = 0;
                    $_membername = $member_info_link = get_phrase("torrents_anonymouse_uploader");
                }

                $content_type = "peer";
                $content_id = $_memberid;
                eval("\$report = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post") . "\";");
                $ipaddress = ($ShowIpPort ? $History["ipaddress"] . ":" . $History["port"] : "");
                $History["ratio"] = member_ratio($History["total_uploaded"], $History["total_downloaded"]);
                $History["uploaded"] = friendly_size($History["total_uploaded"]);
                $History["upload_speed"] = friendly_size($History["upload_speed"]);
                $History["downloaded"] = friendly_size($History["total_downloaded"]);
                $History["download_speed"] = friendly_size($History["download_speed"]);
                $totalSeedTime = $History["announced"] * $announceInterval;
                $History["announced"] = friendly_number_format($History["announced"]) . " (" . convertSeconds($totalSeedTime) . ")";
                $History["last_updated"] = convert_relative_time($History["last_updated"]);
                if( !$xbtActive ) 
                {
                    $History["peer_id"] = friendlyPeerID($History["peer_id"]);
                }

                $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
                eval("\$historyList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("peers_list" . $peersTable) . "\";");
                $count++;
            }
            eval("\$tabs['leechers'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("peers_table" . $peersTable) . "\";");
        }
        else
        {
            $tabs["leechers"] = get_phrase("no_results_found");
        }

        $leechers = count($leechersCache);
        $seeders = count($seedersCache);
        if( !$Torrent["external"] ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_torrents", array( "seeders" => $seeders, "leechers" => $leechers ), "tid=" . $TSUE["TSUE_Database"]->escape($tid));
        }

        $TSUE["TSUE_Language"]->phrase["torrents_seeders"] = $TSUE["TSUE_Language"]->phrase["torrents_seeders"] . " (" . $seeders . ")";
        $TSUE["TSUE_Language"]->phrase["torrents_leechers"] = $TSUE["TSUE_Language"]->phrase["torrents_leechers"] . " (" . $leechers . ")";
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_seeders_leechers_tab") . "\";");
        ajax_message($Output, NULL, true, $torrentName);
        break;
    case "torrent_size":
        globalize("post", array( "tid" => "INT" ));
        if( !has_permission("canview_torrents") || !has_permission("canview_torrent_filelist") || !$tid ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        check_flood("torrent_size_" . $tid);
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.options, c.cviewpermissions, a.filename FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) LEFT JOIN tsue_attachments a ON (a.content_type='torrent_files' AND a.content_id=t.tid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }
        else
        {
            $Torrent["options"] = unserialize($Torrent["options"]);
            $torrentName = strip_tags($Torrent["name"]);
        }

        if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        require_once(REALPATH . "/library/classes/class_torrent.php");
        $TorrentFile = REALPATH . "/data/torrents/torrent_files/" . $Torrent["filename"];
        if( !is_file($TorrentFile) || !($Data = @file_get_contents($TorrentFile)) ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $Torrent = new Torrent();
        $Torrent->load($Data);
        if( $Torrent->error ) 
        {
            ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
        }

        $Files = $Torrent->getFiles();
        $historyList = "";
        $count = 0;
        foreach( $Files as $File ) 
        {
            $History["Filename"] = file_icon($File->name) . " " . strip_tags($File->name);
            $History["FileSize"] = friendly_size(0 + $File->length);
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$historyList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("files_list") . "\";");
            $count++;
        }
        eval("\$files_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("files_table") . "\";");
        ajax_message($files_table, NULL, true, $torrentName);
        break;
    case "categories_checkbox":
        if( !has_permission("canview_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "skipSubmitButtons" => "INT" ));
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        ajax_message(prepareTorrentCategoriesCheckbox($TSUE["TSUE_Member"]->info["defaultTorrentCategories"], true, !$skipSubmitButtons));
        break;
    case "uploader_application":
        globalize("post", array( "computer_running_all_the_time" => "INT", "seedbox" => "INT", "speedtest" => "TRIM", "stuff" => "TRIM" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"));
        }

        if( !has_permission("canupload_torrents") && !has_permission("canlogin_admincp") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $search = $TSUE["TSUE_Database"]->query_result("SELECT application_state FROM tsue_uploader_applications WHERE memberid = " . $TSUE["TSUE_Member"]->info["memberid"]);
        if( $search ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !preg_match("#^http:\\/\\/www\\.speedtest\\.net\\/result\\/[0-9]+\\.png\$#", $speedtest) || !$stuff ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $buildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "application_state" => "pending", "computer_running_all_the_time" => $computer_running_all_the_time, "seedbox" => $seedbox, "speedtest" => $speedtest, "stuff" => $stuff );
        if( !$TSUE["TSUE_Database"]->insert("tsue_uploader_applications", $buildQuery) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
        if( $searchMembergroups ) 
        {
            $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
            if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
            {
                while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                {
                    alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "applications", 0, "new-uploader-form");
                }
            }

        }

        ajax_message(get_phrase("uploader_application_has_been_sent"), "-DONE-");
}


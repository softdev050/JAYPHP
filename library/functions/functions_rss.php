<?php 
function prepareRSS($categories = array(  ), $pk = "", $membergroupid = 0)
{
    global $TSUE;
    if( empty($categories) || !$pk || !$membergroupid ) 
    {
        exit( "11" );
    }

    require(REALPATH . "/library/classes/class_rss.php");
    $FeedWriter = new FeedWriter();
    $FeedWriter->setTitle($TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
    $FeedWriter->setLink($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10");
    $FeedWriter->setDescription($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
    $FeedWriter->setChannelElement("language", $TSUE["TSUE_Language"]->content_language);
    $FeedWriter->setChannelElement("pubDate", date(DATE_RSS, TIMENOW));
    $Torrents = $TSUE["TSUE_Database"]->query("SELECT t.tid, t.name, t.description, t.size, t.added, t.leechers, t.seeders, t.times_completed, c.cviewpermissions, a.filename, i.content as IMDBContent \r\n\tFROM tsue_torrents t \r\n\tLEFT JOIN tsue_torrents_categories c USING(cid) \r\n\tLEFT JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) \r\n\tLEFT JOIN tsue_imdb i USING(tid) \r\n\tWHERE t.cid IN (" . implode(",", $categories) . ") ORDER BY t.added DESC LIMIT " . getSetting("global_settings", "website_rss_perpage", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_torrents_perpage"]));
    if( !$TSUE["TSUE_Database"]->num_rows($Torrents) ) 
    {
        exit( "22" );
    }

    $validBrowser = array( "ie", "safari", "chrome", "flock", "opera" );
    $useTorrentImage = in_array(get_user_browser(), $validBrowser);
    while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($Torrents) ) 
    {
        if( hasViewPermission($Torrent["cviewpermissions"], $membergroupid) ) 
        {
            $torrentImage = "";
            if( $useTorrentImage ) 
            {
                if( is_valid_image($Torrent["filename"]) && is_file(REALPATH . "/data/torrents/torrent_images/s/" . $Torrent["filename"]) ) 
                {
                    $torrentImage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/s/" . $Torrent["filename"];
                }
                else
                {
                    $Torrent["IMDBContent"] = unserialize($Torrent["IMDBContent"]);
                    if( $Torrent["IMDBContent"] && $Torrent["IMDBContent"]["title_id"] && is_file(REALPATH . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg") ) 
                    {
                        $torrentImage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $Torrent["IMDBContent"]["title_id"] . ".jpg";
                    }

                }

            }

            $itemLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=rss&action=download&tid=" . $Torrent["tid"] . "&pk=" . $pk;
            $newItem = $FeedWriter->createNewItem();
            $newItem->setTitle(strip_tags($Torrent["name"]));
            $newItem->setLink($itemLink);
            $newItem->setDescription(get_phrase("torrents_size") . ": " . friendly_size($Torrent["size"]) . " - " . get_phrase("torrents_seeders") . ": " . friendly_number_format($Torrent["seeders"]) . " - " . get_phrase("torrents_leechers") . ": " . friendly_number_format($Torrent["leechers"]) . " - " . get_phrase("torrents_times_completed") . " x " . friendly_number_format($Torrent["times_completed"]));
            $newItem->setDate($Torrent["added"]);
            if( $torrentImage ) 
            {
                $extension = file_extension($torrentImage);
                $type = "";
                switch( $extension ) 
                {
                    case "jpg":
                    case "jpeg":
                        $type = "image/jpeg";
                        break;
                    case "gif":
                        $type = "image/gif";
                        break;
                    case "png":
                        $type = "image/png";
                        break;
                }
                if( $type ) 
                {
                    $newItem->setEncloser($torrentImage, 1, $type);
                }

            }

            $newItem->addElement("guid", $itemLink, array( "isPermaLink" => "true" ));
            $FeedWriter->addItem($newItem);
        }

    }
    $FeedWriter->genarateFeed();
    exit();
}



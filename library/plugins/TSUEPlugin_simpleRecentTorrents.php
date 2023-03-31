<?php 
function TSUEPlugin_simpleRecentTorrents($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.*, a.filename, i.content as IMDBContent, c.cname, cc.cname as parentCategoryName, m.membername \r\n\t\tFROM  tsue_torrents t \r\n\t\tLEFT JOIN tsue_attachments a ON(t.tid=a.content_id&&a.content_type=\"torrent_images\")\r\n\t\tLEFT JOIN tsue_imdb i USING(tid)\r\n\t\tLEFT JOIN tsue_torrents_categories c ON(t.cid=c.cid) \r\n\t\tLEFT JOIN tsue_torrents_categories cc ON(c.pid=cc.cid) \r\n\t\tLEFT JOIN tsue_members m ON (t.owner=m.memberid)\r\n\t\tGROUP BY t.tid ORDER BY t.added DESC LIMIT 90");
    if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
    {
        $cached = array(  );
        $Images = "\r\n\t\t\t<div style=\"position: fixed; bottom: 0; left: 0; background: #fff; width: 100%; height: 102px; overflow: hidden; border-top: 2px solid #000; padding: 0; margin: 0; text-align: center;\">\r\n\t\t\t\t<div style=\"text-align: center;\">";
        while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
        {
            if( in_array(substr($Torrent["name"], 0, 15), $cached) ) 
            {
                continue;
            }

            $cached[] = substr($Torrent["name"], 0, 15);
            $title = addslashes(strip_tags($Torrent["name"]));
            $hasValidImage = is_valid_image($Torrent["filename"]);
            $img = "";
            if( !$hasValidImage && $Torrent["IMDBContent"] ) 
            {
                $IMDBContent = unserialize($Torrent["IMDBContent"]);
                if( is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
                {
                    $img = "imdb/" . $IMDBContent["title_id"] . ".jpg";
                }

            }
            else
            {
                if( $hasValidImage ) 
                {
                    $img = "torrent_images/s/" . $Torrent["filename"];
                }

            }

            if( $img ) 
            {
                $Images .= "\r\n\t\t\t\t\t<div style=\"margin: 5px 5px 0 0; display: inline-block; position: relative; height: 90px;\">\r\n\t\t\t\t\t\t<div style=\"position: absolute; bottom: -1px; left: 0; width: 100%; height: 18px; overflow: hidden; font-size: 9px; background: #000; z-index: 1; color: #fff; line-height: 1.7; opacity: 0.5;\"><div style=\"padding: 2px;\">" . substr($title, 0, 12) . "</div></div>\r\n\t\t\t\t\t\t<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/" . $img . "\" alt=\"\" style=\"height: 90px; border: 1px solid #000;\" />\r\n\t\t\t\t\t</div>";
            }

        }
        $Images .= "\r\n\t\t\t\t\t<div style=\"clear: both;\"></div>\r\n\t\t\t\t</div>\r\n\t\t\t</div>\r\n\t\t\t<script>\$('img[alt!=\"\"]').tipsy({trigger: \"hover\", gravity: \"sw\", html: true});</script>";
        return $Images;
    }

}



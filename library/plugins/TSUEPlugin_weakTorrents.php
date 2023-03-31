<?php 
function TSUEPlugin_weakTorrents($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_weakTorrents_" . $TSUE["TSUE_Member"]->info["membergroupid"] . "_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("weakTorrents");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($weakTorrents = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.tid, t.name, t.owner, t.seeders, t.leechers, t.size, t.options, t.added, t.flags, m.membername, g.groupstyle, c.cviewpermissions\r\n\t\tFROM tsue_torrents t \r\n\t\tLEFT JOIN tsue_members m on (t.owner=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_torrents_categories c USING(cid)\r\n\t\tWHERE t.awaitingModeration = 0 AND t.seeders = 0\r\n\t\tORDER BY t.added ASC LIMIT " . getPluginOption($pluginOptions, "max_weak_torrents", 10));
        if( !$TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
        {
            return NULL;
        }

        $weakTorrents = "";
        while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
        {
            if( $Torrent["flags"] != 1 && $Torrent["size"] && $Torrent["name"] && hasViewPermission($Torrent["cviewpermissions"]) ) 
            {
                $Torrent["name"] = strip_tags($Torrent["name"]);
                $Torrent["options"] = unserialize($Torrent["options"]);
                $Torrent["owner"] = get_phrase("torrents_owner", convert_relative_time($Torrent["added"]), $Torrent["membername"]);
                $Torrent["size"] = friendly_size($Torrent["size"]);
                $_memberid = $Torrent["owner"];
                $_membername = getMembername($Torrent["membername"], $Torrent["groupstyle"]);
                if( isAnonymouse($Torrent) ) 
                {
                    $_memberid = 0;
                    $_membername = get_phrase("torrents_anonymouse_uploader");
                }

                eval("\$weakTorrents .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("weak_torrents_list") . "\";");
            }

        }
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $weakTorrents);
    }

    eval("\$TSUEPlugin_weakTorrents = \"" . $TSUE["TSUE_Template"]->LoadTemplate("weak_torrents") . "\";");
    return $TSUEPlugin_weakTorrents;
}



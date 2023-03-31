<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function updatePeersTorrents()
{
    global $TSUE;
    $timeOut = buildAnnounceIntervalTimeout();
    $xbtActive = getSetting("xbt", "active");
    if( $xbtActive ) 
    {
        $TSUE["TSUE_Database"]->update("xbt_files_users", array( "active" => 0 ), "`mtime` < " . $timeOut . " AND `active` = 1");
        $TSUE["TSUE_Database"]->query("UPDATE tsue_torrents SET seeders = (SELECT COUNT(fid) FROM  xbt_files_users WHERE fid = tsue_torrents.tid AND `left` = 0 AND active = 1), leechers = (SELECT COUNT(fid) FROM  xbt_files_users WHERE fid = tsue_torrents.tid AND `left` > 0 AND active = 1)");
    }
    else
    {
        $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "active" => 0 ), "`last_updated` < " . $timeOut . " AND `active` = 1");
        $TSUE["TSUE_Database"]->query("UPDATE tsue_torrents SET seeders = (SELECT COUNT(tid) FROM  tsue_torrents_peers WHERE tid = tsue_torrents.tid AND `left` = 0 AND active = 1), leechers = (SELECT COUNT(tid) FROM  tsue_torrents_peers WHERE tid = tsue_torrents.tid AND `left` > 0 AND active = 1)");
    }

}



<?php 
define("SCRIPTNAME", "auto_uploader.php");
define("IN_INDEX", 1);
require("./library/init/init.php");
$TSUE["TSUE_Settings"]->loadSettings("auto_uploader hitrun_settings");
if( !$TSUE["TSUE_Settings"]->settings["auto_uploader"]["active"] ) 
{
    exit( "Fatal Error: E1" );
}

if( !$TSUE["TSUE_Settings"]->settings["auto_uploader"]["category"] ) 
{
    exit( "Fatal Error: E2" );
}

if( !$TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"] || !is_dir($TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"]) ) 
{
    exit( "Fatal Error: E3" );
}

if( !$TSUE["TSUE_Settings"]->settings["auto_uploader"]["owner"] ) 
{
    exit( "Fatal Error: E4" );
}

$torrent_options = array( "anonymouse" => 1, "record_stats" => 1, "upload_multiplier" => 1, "download_multiplier" => 1, "imdb" => "", "nuked" => "", "hitRunRatio" => getSetting("hitrun_settings", "defaultRatio", 0) );
$BuildQuery = array( "info_hash" => "", "name" => "", "description" => $TSUE["TSUE_Settings"]->settings["auto_uploader"]["description"], "cid" => $TSUE["TSUE_Settings"]->settings["auto_uploader"]["category"], "size" => "", "added" => TIMENOW, "owner" => $TSUE["TSUE_Settings"]->settings["auto_uploader"]["owner"], "options" => serialize($torrent_options), "nfo" => "", "flags" => 2, "ctime" => TIMENOW, "download_multiplier" => 1, "upload_multiplier" => 1 );
$BuildAttachmentQuery = array( "content_type" => "torrent_files", "content_id" => "", "upload_date" => TIMENOW, "associated" => 1, "memberid" => $TSUE["TSUE_Settings"]->settings["auto_uploader"]["owner"], "filename" => "", "filesize" => "" );
$count = 0;
$Found = array(  );
$newTorrents = scandir($TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"]);
if( $newTorrents ) 
{
    require_once(REALPATH . "/library/classes/class_imdb.php");
    require_once(REALPATH . "/library/functions/functions_getTorrents.php");
    foreach( $newTorrents as $Torrent ) 
    {
        if( file_extension($Torrent) === "torrent" ) 
        {
            $saveTorrent = saveTorrent($Torrent, $TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"]);
            $searchTorrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE info_hash = " . $TSUE["TSUE_Database"]->escape($saveTorrent["info_hash"]));
            if( !$searchTorrent ) 
            {
                $safeTorrentFileName = safe_names($Torrent);
                $BuildQuery["info_hash"] = $saveTorrent["info_hash"];
                $BuildQuery["name"] = str_replace(".torrent", "", $safeTorrentFileName);
                $BuildQuery["size"] = $saveTorrent["size"];
                if( $TSUE["TSUE_Database"]->insert("tsue_torrents", $BuildQuery) && ($tid = $TSUE["TSUE_Database"]->insert_id()) ) 
                {
                    deleteCache("TSUEPlugin_recentTorrents_");
                    $BuildAttachmentQuery["content_id"] = $tid;
                    $BuildAttachmentQuery["filename"] = $safeTorrentFileName;
                    $BuildAttachmentQuery["filesize"] = $saveTorrent["size"];
                    if( $TSUE["TSUE_Database"]->insert("tsue_attachments", $BuildAttachmentQuery) && copy($TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"] . $Torrent, REALPATH . "/data/torrents/torrent_files/" . $safeTorrentFileName) ) 
                    {
                        @unlink($TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"] . $Torrent);
                        shoutboxAnnouncement(array( "new_torrent", $tid, $BuildQuery["name"], substr(strip_tags($BuildQuery["description"]), 0, 200) . " ...", $TSUE["TSUE_Settings"]->settings["auto_uploader"]["category"] ));
                        ircAnnouncement("new_torrent", $tid, $BuildQuery["name"]);
                        $count++;
                    }
                    else
                    {
                        $TSUE["TSUE_Database"]->delete("tsue_torrents", "tid = " . $TSUE["TSUE_Database"]->escape($tid));
                        deleteCache("TSUEPlugin_recentTorrents_");
                    }

                }

            }
            else
            {
                @unlink($TSUE["TSUE_Settings"]->settings["auto_uploader"]["path"] . $Torrent);
            }

        }

    }
}

if( !$count ) 
{
    exit( "Nothing imported." );
}

exit( "Total " . $count . " torrents have been imported." );


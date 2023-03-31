<?php 
@date_default_timezone_set("GMT");
@define("REALPATH", @dirname(__FILE__) . "/");
if( isset($_GET["info_hash"]) ) 
{
    require(REALPATH . "library/classes/class_database.php");
    $TSUE["TSUE_Database"] = new TSUE_Database();
    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT seeders,leechers,times_completed FROM tsue_torrents WHERE info_hash = " . $TSUE["TSUE_Database"]->escape($_GET["info_hash"]));
    if( $Torrent ) 
    {
        exit( "d5:filesd20:" . $_GET["info_hash"] . "d8:completei" . $Torrent["seeders"] . "e10:downloadedi" . $Torrent["times_completed"] . "e10:incompletei" . $Torrent["leechers"] . "eeee" );
    }

}



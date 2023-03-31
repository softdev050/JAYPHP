<?php 
define("SCRIPTNAME", "torrent_nfo.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( $TSUE["action"] == "view_nfo" ) 
{
    globalize("get", array( "tid" => "INT", "securitytoken" => "TRIM" ));
    if( !has_permission("canview_torrents") || !$tid ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    if( !isValidToken($securitytoken) ) 
    {
        ajax_message(get_phrase("invalid_security_token"));
    }

    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.nfo, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
    if( !$Torrent || !$Torrent["nfo"] ) 
    {
        ajax_message(get_phrase("message_content_error"), "-ERROR-");
    }

    if( !hasViewPermission($Torrent["cviewpermissions"]) ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    require_once(REALPATH . "/library/functions/functions_getTorrents.php");
    $NFO = secureNFOContents($Torrent["nfo"], false, false);
    require_once(REALPATH . "/library/classes/class_nfo.php");
    $NFO = new TSUE_NFO($NFO);
    $NFO->convertToPNGShowImage();
}



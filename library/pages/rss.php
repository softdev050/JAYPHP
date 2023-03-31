<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "rss.php");
define("NO_SECURITY", 1);
define("NO_PARSER", 1);
require("./library/init/init.php");
globalize("get", array( "pk" => "TRIM", "categories" => "TRIM" ));
$passkeyLength = strlen($pk);
if( $passkeyLength != 40 && $passkeyLength != 32 ) 
{
    exit( "e1" );
}

$Member = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membergroupid, m.passkey, p.uploaded, p.downloaded, g.permissions, b.memberid AS isBanned \r\nFROM tsue_members m \r\nINNER JOIN tsue_member_profile p USING(memberid)\r\nINNER JOIN tsue_membergroups g USING(membergroupid) \r\nLEFT JOIN tsue_member_bans b USING(memberid) \r\nWHERE m.passkey = " . $TSUE["TSUE_Database"]->escape($pk));
if( !$Member || $Member["isBanned"] ) 
{
    exit( "e2" );
}

if( !has_permission("canview_torrents", $Member["permissions"]) ) 
{
    exit( "e3" );
}

if( $categories ) 
{
    $categories = array_map("intval", tsue_explode(",", $categories));
    if( !$categories ) 
    {
        exit( "e4" );
    }

    require(REALPATH . "/library/functions/functions_rss.php");
    prepareRSS($categories, $pk, $Member["membergroupid"]);
}

if( $TSUE["action"] == "download" ) 
{
    require_once(REALPATH . "/library/functions/functions_getTorrents.php");
    globalize("get", array( "tid" => "INT" ));
    $Member["permissions"] = unserialize($Member["permissions"]);
    $Member["max_slot_limit"] = (isset($Member["permissions"]["max_slot_limit"]) ? intval($Member["permissions"]["max_slot_limit"]) : 0);
    $Member["minratio_dl_torrents"] = (isset($Member["permissions"]["minratio_dl_torrents"]) ? 0 + $Member["permissions"]["minratio_dl_torrents"] : 0);
    $downloadTorrent = downloadTorrent($tid, $Member);
    if( $downloadTorrent ) 
    {
        exit( $downloadTorrent );
    }

}



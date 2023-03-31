<?php
define("SCRIPTNAME", "announce.php");
define("NO_MEMBER", 1);
define("NO_CACHE", 1);
define("NO_LANGUAGE", 1);
define("NO_SECURITY", 1);
define("NO_PARSER", 1);
define("NO_TEMPLATE", 1);
define("NO_PLUGIN", 1);
define("ANNOUNCE_LOG", false);
define("LOGFORIP", "127.0.0.1");
$loadSettings = "global_settings happy_hours xbt";
require("./library/init/init.php");
if( defined("ANNOUNCE_LOG") && ANNOUNCE_LOG == true && (LOGFORIP && MEMBER_IP == LOGFORIP || !LOGFORIP) ) 
{
    announceLog();
}

$announce_interval = getSetting("global_settings", "announce_interval");
if( getSetting("xbt", "active") ) 
{
    if( isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) && preg_match("@[pk|passkey]\\=(\\w{32})@isU", $_SERVER["QUERY_STRING"], $Result) && isset($Result["1"]) && strlen($Result["1"]) == 32 ) 
    {
        $announce_url = getSetting("xbt", "announce_url");
        if( substr($announce_url, -1) != "/" ) 
        {
            $announce_url .= "/";
        }

        header("Location: " . $announce_url . $Result["1"] . "/announce?" . $_SERVER["QUERY_STRING"]);
        exit();
    }

    _printError("XBT Error!");
}

$GetVariables = array(  );
$GetVariables["info_hash"] = (isset($_GET["info_hash"]) ? $_GET["info_hash"] : "");
$GetVariables["passkey"] = (isset($_GET["pk"]) ? trim($_GET["pk"]) : (isset($_GET["passkey"]) ? trim($_GET["passkey"]) : ""));
$GetVariables["peer_id"] = (isset($_GET["peer_id"]) ? $_GET["peer_id"] : "");
$GetVariables["port"] = (isset($_GET["port"]) ? 0 + $_GET["port"] : 0);
$GetVariables["uploaded"] = (isset($_GET["uploaded"]) ? 0 + $_GET["uploaded"] : 0);
$GetVariables["downloaded"] = (isset($_GET["downloaded"]) ? 0 + $_GET["downloaded"] : 0);
$GetVariables["left"] = (isset($_GET["left"]) ? 0 + $_GET["left"] : 0);
$GetVariables["event"] = (isset($_GET["event"]) ? trim($_GET["event"]) : "");
$GetVariables["compact"] = (isset($_GET["compact"]) ? 0 + $_GET["compact"] : 0);
if( !$GetVariables["info_hash"] && $GetVariables["passkey"] && 40 < strlen($GetVariables["passkey"]) ) 
{
    $validPasskey = substr($GetVariables["passkey"], 0, 40);
    $GetVariables["info_hash"] = str_replace($validPasskey . "?info_hash=", "", $GetVariables["passkey"]);
    $GetVariables["passkey"] = $validPasskey;
    unset($validPasskey);
}

if( !$GetVariables["info_hash"] && isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) ) 
{
    $queryStrings = explode("&", $_SERVER["QUERY_STRING"]);
    if( $queryStrings ) 
    {
        foreach( $queryStrings as $queryString ) 
        {
            list($_name, $_value) = explode("=", $queryString);
            if( $_name == "info_hash" ) 
            {
                $GetVariables["info_hash"] = urldecode($_value);
                break;
            }

        }
        unset($queryStrings);
        unset($_name);
        unset($_value);
    }

}

$_skip = array( "uploaded", "downloaded", "left", "event" );
foreach( $GetVariables as $_name => $_value ) 
{
    if( !$_value && !in_array($_name, $_skip) ) 
    {
        if( defined("INFO_HASH_LOG") && INFO_HASH_LOG == true && $_name == "info_hash" ) 
        {
            $Log = "INFO HASH CAN NOT BE EMPTY ERROR DETECTED\n\n_GET Varialbles Shown Below\n";
            foreach( $_GET as $__LEFT => $__RIGHT ) 
            {
                $Log .= (string) $__LEFT . " => " . $__RIGHT . "\n";
            }
            $Log .= "\n_SERVER Variables Shown Below\n";
            foreach( $_SERVER as $__LEFT => $__RIGHT ) 
            {
                $Log .= (string) $__LEFT . " => " . $__RIGHT . "\n";
            }
            file_put_contents(REALPATH . "data/errors/info_hash_error.log", $Log);
            unset($Log);
        }

        _printerror($_name . " can not be empty!");
    }

}
if( isset($_SERVER["HTTP_COOKIE"]) && isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) || isset($_SERVER["HTTP_ACCEPT"]) && $_SERVER["HTTP_ACCEPT"] == "text/html, */*" && isset($_SERVER["HTTP_ACCEPT_ENCODING"]) && $_SERVER["HTTP_ACCEPT_ENCODING"] == "identity" ) 
{
    _printerror("It looks like you are using a browser to download this torrent. Please use a real bittorrent software!");
}

$announce_check_peerid = getSetting("global_settings", "announce_check_peerid");
if( $announce_check_peerid ) 
{
    $shortPeerid = substr($GetVariables["peer_id"], 0, 8);
    $announce_check_peerlist = tsue_explode(",", getSetting("global_settings", "announce_check_peerlist"));
    if( $announce_check_peerid == 1 ) 
    {
        if( in_array($shortPeerid, $announce_check_peerlist) ) 
        {
            _printerror("Sorry, your client (" . strip_tags($shortPeerid) . ") is in our Blacklist.");
        }

    }
    else
    {
        if( $announce_check_peerid == 2 && !in_array($shortPeerid, $announce_check_peerlist) ) 
        {
            _printerror("Sorry, your client (" . strip_tags($shortPeerid) . ") is not in our Whitelist.");
        }

    }

}

$Member = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE m.memberid, m.membergroupid, m.ipaddress, g.permissions FROM tsue_members m LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE m.passkey=" . $TSUE["TSUE_Database"]->escape($GetVariables["passkey"]) . " LIMIT 1");
if( !$Member ) 
{
    _printerror("The specific member not found!");
}
else
{
    if( $Member["ipaddress"] != MEMBER_IP && getSetting("global_settings", "announce_check_ip") ) 
    {
        _printerror("IP address mismatch! Please login to update your IP address.");
    }

}

$memberPerms = unserialize($Member["permissions"]);
if( 0 < $GetVariables["left"] && (!has_permission("canview_torrents", $memberPerms) || !has_permission("candownload_torrents", $memberPerms)) ) 
{
    _printerror("You have no permission to use this torrent.");
}

if( getSetting("global_settings", "announce_check_slots") && 0 < $GetVariables["left"] && isset($memberPerms["max_slot_limit"]) && 0 < $memberPerms["max_slot_limit"] ) 
{
    $totalActiveTorrents = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE memberid FROM tsue_torrents_peers WHERE `memberid` = " . $Member["memberid"] . " AND `active` = 1 AND `left` > 0");
    if( $totalActiveTorrents && $memberPerms["max_slot_limit"] <= $totalActiveTorrents ) 
    {
        _printerror("You may not download any more torrents until your other downloads are complete.");
    }

}

$Torrent = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE t.tid, t.size, t.seeders, t.times_completed, t.options, c.cviewpermissions, c.cdownloadpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.info_hash=" . $TSUE["TSUE_Database"]->escape($GetVariables["info_hash"]) . " LIMIT 1");
if( !$Torrent ) 
{
    _printerror("The specific torrent not found!");
}

if( 0 < $GetVariables["left"] && (!hasViewPermission($Torrent["cviewpermissions"], $Member["membergroupid"]) || !hasViewPermission($Torrent["cdownloadpermissions"], $Member["membergroupid"])) ) 
{
    _printerror("You have no permission to use this torrent.");
}

$Torrent["options"] = unserialize($Torrent["options"]);
$orjTorrentSeeders = $Torrent["seeders"];
$peerList = "";
$Torrent["leechers"] = 0;
$Torrent["seeders"] = $Torrent["leechers"];
$Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE pid, memberid, port, active, announced, uploaded, downloaded, `left`, peer_id, last_updated, ipaddress FROM tsue_torrents_peers WHERE `tid` = " . $TSUE["TSUE_Database"]->escape($Torrent["tid"]));
if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
{
    while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
    {
        if( str_pad($Peer["peer_id"], 20) === $GetVariables["peer_id"] || $Peer["memberid"] == $Member["memberid"] && !$Peer["active"] && $Peer["port"] == $GetVariables["port"] && substr($Peer["peer_id"], 0, 8) != substr($GetVariables["peer_id"], 0, 8) ) 
        {
            $ExistingPeerStats = array( "uploaded" => $Peer["uploaded"], "downloaded" => $Peer["downloaded"], "last_updated" => $Peer["last_updated"], "announced" => $Peer["announced"], "left" => $Peer["left"], "port" => $Peer["port"], "peer_id" => $Peer["peer_id"], "ipaddress" => $Peer["ipaddress"], "pid" => $Peer["pid"], "active" => $Peer["active"] );
            $Torrent[($Peer["left"] ? "leechers" : "seeders")]++;
        }
        else
        {
            if( $Peer["active"] ) 
            {
                $peerList .= pack("Nn", ip2long($Peer["ipaddress"]), $Peer["port"]);
                $Torrent[($Peer["left"] ? "leechers" : "seeders")]++;
            }

        }

    }
}

$Uploaded = $GetVariables["uploaded"];
$Downloaded = $GetVariables["downloaded"];
$UploadSpeed = $DownloadSpeed = 0;
if( isset($ExistingPeerStats) ) 
{
    $Uploaded = max(0, $Uploaded - $ExistingPeerStats["uploaded"]);
    $Downloaded = max(0, $Downloaded - $ExistingPeerStats["downloaded"]);
    if( $ExistingPeerStats["active"] ) 
    {
        $TimeDifference = TIMENOW - $ExistingPeerStats["last_updated"];
        $UploadSpeed = ($Uploaded && $TimeDifference ? $Uploaded / $TimeDifference : 0);
        $DownloadSpeed = ($Downloaded && $TimeDifference ? $Downloaded / $TimeDifference : 0);
    }

    $q = array(  );
    $q[] = "active = " . (($GetVariables["event"] == "stopped" ? "0" : "1"));
    $q[] = "last_updated = " . TIMENOW;
    $q[] = "`left` = " . $GetVariables["left"];
    $q[] = " downloaded = " . $GetVariables["downloaded"];
    $q[] = " uploaded = " . $GetVariables["uploaded"];
    if( $GetVariables["port"] && $GetVariables["port"] != $ExistingPeerStats["port"] ) 
    {
        $q[] = "port = " . $GetVariables["port"];
    }

    if( $announce_interval / 2 < TIMENOW - $ExistingPeerStats["last_updated"] && $ExistingPeerStats["active"] ) 
    {
        $q[] = "announced = announced+1";
    }

    if( $GetVariables["peer_id"] != $ExistingPeerStats["peer_id"] ) 
    {
        $q[] = "peer_id = " . $TSUE["TSUE_Database"]->escape($GetVariables["peer_id"]);
    }

    if( MEMBER_IP != $ExistingPeerStats["ipaddress"] ) 
    {
        $q[] = "ipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP);
    }

    if( $UploadSpeed ) 
    {
        $q[] = "upload_speed = " . $UploadSpeed;
    }

    if( $DownloadSpeed ) 
    {
        $q[] = "download_speed = " . $DownloadSpeed;
    }

    if( $Uploaded ) 
    {
        $q[] = "total_uploaded = total_uploaded + " . $Uploaded;
    }

    if( $Downloaded ) 
    {
        $q[] = "total_downloaded = total_downloaded + " . $Downloaded;
    }

    $q = implode(", ", $q);
    $TSUE["TSUE_Database"]->query("\r\n\t\tUPDATE tsue_torrents_peers SET " . $q . " WHERE pid = " . $TSUE["TSUE_Database"]->escape($ExistingPeerStats["pid"]) . "\r\n\t");
}
else
{
    $newInsert = true;
    $TSUE["TSUE_Database"]->query("\r\n\t\tINSERT INTO tsue_torrents_peers SET\r\n\t\t\ttid = " . $Torrent["tid"] . ",\r\n\t\t\tmemberid = " . $Member["memberid"] . ",\r\n\t\t\tport = " . $GetVariables["port"] . ",\r\n\t\t\tactive = 1,\r\n\t\t\t`left` = " . $GetVariables["left"] . ",\r\n\t\t\tpeer_id = " . $TSUE["TSUE_Database"]->escape($GetVariables["peer_id"]) . ",\r\n\t\t\tlast_updated = " . TIMENOW . ",\r\n\t\t\tipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP) . "\r\n\t");
}

if( $TSUE["TSUE_Database"]->affected_rows() && $GetVariables["event"] ) 
{
    switch( $GetVariables["event"] ) 
    {
        case "stopped":
            if( isset($ExistingPeerStats) && !isset($newInsert) ) 
            {
                $Torrent[($GetVariables["left"] ? "leechers" : "seeders")]--;
            }

            break;
        case "completed":
            if( !isset($newInsert) ) 
            {
                $Torrent["times_completed"]++;
                if( !isset($ExistingPeerStats) ) 
                {
                    $Torrent["seeders"]++;
                }
                else
                {
                    if( isset($ExistingPeerStats) && !$GetVariables["left"] && $ExistingPeerStats["left"] ) 
                    {
                        $Torrent["seeders"]++;
                        $Torrent["leechers"]--;
                    }

                }

            }

            break;
        case "started":
        default:
            if( !isset($ExistingPeerStats) || isset($newInsert) ) 
            {
                $Torrent[($GetVariables["left"] ? "leechers" : "seeders")]++;
            }

            break;
    }
}

$buildQuery = array( "leechers" => $Torrent["leechers"], "seeders" => $Torrent["seeders"], "times_completed" => $Torrent["times_completed"], "mtime" => TIMENOW );
if( !$orjTorrentSeeders && $Torrent["seeders"] && getSetting("global_settings", "announce_bump_inactive_torrents") ) 
{
    $buildQuery["added"] = TIMENOW;
    $buildQuery["ctime"] = TIMENOW;
}

$TSUE["TSUE_Database"]->update("tsue_torrents", $buildQuery, "tid=" . $TSUE["TSUE_Database"]->escape($Torrent["tid"]));
if( $Torrent["options"]["record_stats"] ) 
{
    $profileUpdates = array(  );
    $happy_hours_start_date = getSetting("happy_hours", "start_date");
    $happy_hours_end_date = getSetting("happy_hours", "end_date");
    if( getSetting("happy_hours", "active") && $happy_hours_start_date <= TIMENOW && TIMENOW <= $happy_hours_end_date ) 
    {
        $happy_hours_freeleech = getSetting("happy_hours", "freeleech");
        $happy_hours_double_upload = getSetting("happy_hours", "doubleupload");
        if( $happy_hours_freeleech ) 
        {
            $Downloaded = 0;
        }
        else
        {
            $Downloaded = 0 + $Downloaded * $Torrent["options"]["download_multiplier"];
        }

        if( $happy_hours_double_upload ) 
        {
            $Uploaded = 0 + $Uploaded * 2;
        }
        else
        {
            $Uploaded = 0 + $Uploaded * $Torrent["options"]["upload_multiplier"];
        }

    }
    else
    {
        $Uploaded = 0 + $Uploaded * $Torrent["options"]["upload_multiplier"];
        $Downloaded = 0 + $Downloaded * $Torrent["options"]["download_multiplier"];
    }

    if( $Uploaded ) 
    {
        $profileUpdates[] = "uploaded = uploaded + " . $Uploaded;
    }

    if( $Downloaded && !has_permission("do_not_record_download_stats", $memberPerms) ) 
    {
        $profileUpdates[] = "downloaded = downloaded + " . $Downloaded;
    }

    $points_seed = getSetting("global_settings", "points_seed");
    if( $points_seed && $GetVariables["left"] == 0 && isset($ExistingPeerStats) && $ExistingPeerStats["active"] && $announce_interval - 30 < TIMENOW - $ExistingPeerStats["last_updated"] ) 
    {
        $points_seed_min_gb = 0 + getSetting("global_settings", "points_seed_min_gb", 0);
        if( !$points_seed_min_gb || $points_seed_min_gb && $points_seed_min_gb * 1073741824 <= $Torrent["size"] ) 
        {
            $points_seed_al = intval(getSetting("global_settings", "points_seed_al", 0));
            if( !$points_seed_al || $points_seed_al && $points_seed_al <= $ExistingPeerStats["announced"] ) 
            {
                $points_x2_for_big_torrents = 0 + getSetting("global_settings", "points_x2_for_big_torrents", 0);
                if( $points_x2_for_big_torrents && $points_x2_for_big_torrents * 1073741824 <= $Torrent["size"] ) 
                {
                    $points_seed *= 2;
                }

                $profileUpdates[] = "points = points + " . $points_seed;
            }

        }

    }

    if( $profileUpdates ) 
    {
        $TSUE["TSUE_Database"]->query("UPDATE tsue_member_profile SET " . implode(",", $profileUpdates) . " WHERE memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]) . " LIMIT 1");
    }

}

$trackerResponse = "d8:completei" . $Torrent["seeders"] . "e10:downloadedi" . $Torrent["times_completed"] . "e10:incompletei" . $Torrent["leechers"] . "e8:intervali" . $announce_interval . "e12:min intervali" . $announce_interval . "e5:peers" . strlen($peerList) . ":" . $peerList . "e";
if( defined("ANNOUNCE_LOG") && ANNOUNCE_LOG == true && (LOGFORIP && MEMBER_IP == LOGFORIP || !LOGFORIP) ) 
{
    $tmp = "";
    foreach( $TSUE["TSUE_Database"]->query_cache as $array ) 
    {
        list($queryTime, $query) = $array;
        $tmp .= $query . "\r\n\t\t\r\n\t\t";
    }
    $tmp .= "\r\n\tSHUTDOWN QUERIES\r\n\t\r\n\t";
    foreach( $TSUE["TSUE_Database"]->shutdown_queries as $query ) 
    {
        $tmp .= $query . "\r\n\t\t\r\n\t\t";
    }
    file_put_contents("./data/announceLog/queries-" . TIMENOW . ".txt", $tmp);
}

_sendHeaders($trackerResponse, "text/plain", false);
function announceLog()
{
    $tmp = "-----" . date("d-m-Y h:i:s", TIMENOW) . "------" . MEMBER_IP . "------\n";
    foreach( $_GET as $_l => $_r ) 
    {
        $tmp .= (string) $_l . " => " . $_r . "\n";
    }
    file_put_contents("./data/announceLog/" . TIMENOW . ".txt", $tmp);
}

function _benc_str($Str = "")
{
    return strlen($Str) . ":" . $Str;
}

function _printError($Error = "")
{
    $Error = (is_array($Error) ? implode(" -- ", $Error) : $Error);
    _sendHeaders("d14:failure reason" . strlen($Error) . ":" . $Error . "e");
}



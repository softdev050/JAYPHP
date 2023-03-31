<?php 
error_reporting(0);
header("Content-type: text/plain; Charset=utf-8");
header("Pragma: no-cache");
header("Expires: 0");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0");
if( isset($_REQUEST["titlelink"]) ) 
{
    $titlelink = $_REQUEST["titlelink"];
    if( $titlelink == "" ) 
    {
        echo "&song=";
        return 1;
    }

    echo read7html($titlelink);
}

function read7html($sURL)
{
    $aPathInfo = parse_url($sURL);
    $sHost = $aPathInfo["host"];
    $sPort = (empty($aPathInfo["port"]) ? 80 : ($sPort = $aPathInfo["port"]));
    $sPath = (empty($aPathInfo["path"]) ? "/" : ($sPath = $aPathInfo["path"]));
    $fp = fsockopen($sHost, $sPort, $errno, $errstr, "1");
    if( !$fp ) 
    {
        fclose($fp);
        return StreamTitle($sURL);
    }

    fputs($fp, "GET /7.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
    while( !feof($fp) ) 
    {
        $info = fgets($fp);
    }
    $info = str_replace("<HTML><meta http-equiv=\"Pragma\" content=\"no-cache\"></head><body>", "", $info);
    $info = str_replace("</body></html>", "", $info);
    $stats = explode(",", $info);
    if( empty($stats[1]) ) 
    {
        fclose($fp);
        return StreamTitle($sURL);
    }

    if( $stats[1] == "1" ) 
    {
        list($listeners, , $peak, $max, , $bitrate, $song) = $stats;
        return utf8_encode($song);
    }

    fclose($fp);
    return StreamTitle($sURL);
}

function StreamTitle($sURL)
{
    $aPathInfo = parse_url($sURL);
    $sHost = $aPathInfo["host"];
    $sPort = (empty($aPathInfo["port"]) ? 80 : ($sPort = $aPathInfo["port"]));
    $sPath = (empty($aPathInfo["path"]) ? "/" : ($sPath = $aPathInfo["path"]));
    $fp = fsockopen($sHost, $sPort, $errno, $errstr);
    if( !$fp ) 
    {
        return "";
    }

    fputs($fp, "GET " . $sPath . "  HTTP/1.0\r\n");
    fputs($fp, "Host: " . $sHost . "\r\n");
    fputs($fp, "Accept: */*\r\n");
    fputs($fp, "Icy-MetaData:1\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    $char = "";
    $info = "";
    while( !strpos($input, "StreamTitle") ) 
    {
        if( @feof($fp) || 300000 < @ftell($fp) ) 
        {
            exit();
        }

        $char = @fread($fp, 16);
        $input .= $char;
    }
    $input .= @fread($fp, 255);
    $startstr = "StreamTitle='";
    $endstr = "';";
    $start = strpos($input, $startstr);
    $subinput = substr($input, $start + strlen($startstr));
    $end = strpos($subinput, $endstr);
    fclose($fp);
    $output = substr($subinput, 0, $end);
    return utf8_encode($output);
}

function sonderzeichen($text)
{
    return str_replace("+", "%2B", str_replace("&", "%26", str_replace("%", "%25", $text)));
}



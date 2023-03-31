<?php 
error_reporting(0);
if( isset($_REQUEST["titlelink"]) ) 
{
    $titlelink = $_REQUEST["titlelink"];
    header("Content-type: text/plain");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0");
    if( $titlelink == "" ) 
    {
        echo "";
        return 1;
    }

    echo readContent($titlelink);
}

function readContent($sURL)
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

    fputs($fp, "GET " . $sPath . " HTTP/1.0\r\n");
    fputs($fp, "Host: " . $sHost . "\r\n");
    fputs($fp, "Accept: */*\r\n");
    fputs($fp, "Icy-MetaData:1\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    $char = "";
    $info = "";
    while( $char != Chr(255) ) 
    {
        if( @feof($fp) || 14096 < @ftell($fp) ) 
        {
            exit();
        }

        $char = @fread($fp, 1);
        $info .= $char;
    }
    fclose($fp);
    $info = str_replace("\n", "", $info);
    $info = str_replace("\r", "", $info);
    $info = str_replace("\n\r", "", $info);
    $info = str_replace("<BR>", "", $info);
    $info = str_replace(":", "=", $info);
    $info = str_replace("icy", "&icy", $info);
    $info = strtolower($info);
    parse_str($info, $output);
    if( $output["icy-br"] != "" ) 
    {
        $streambitrate = intval($output["icy-br"]);
    }

    if( $output["icy-name"] == "" ) 
    {
        return "";
    }

    return utf8_encode($output["icy-name"]);
}

function sonderzeichen($text)
{
    return str_replace("+", "%2B", str_replace("&", "%26", str_replace("%", "%25", $text)));
}



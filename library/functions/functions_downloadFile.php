<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function downloadFile($fullPath = "", $fileName = "", $contents = "")
{
    if( headers_sent() ) 
    {
        show_error("Critical Error: Headers already sent!");
    }

    if( ini_get("zlib.output_compression") ) 
    {
        ini_set("zlib.output_compression", "Off");
    }

    if( !$contents && !is_file($fullPath) ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( $fileName && $contents ) 
    {
        $filesize = strlen($contents);
        $extension = file_extension($fileName);
    }
    else
    {
        if( $fullPath && $fileName && file_exists($fullPath) ) 
        {
            $filesize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $extension = strtolower($path_parts["extension"]);
            $contents = file_get_contents($fullPath);
        }
        else
        {
            show_error(get_phrase("message_content_error"));
        }

    }

    $imageTypes = array( "gif" => "image/gif", "jpg" => "image/jpeg", "jpeg" => "image/jpeg", "jpe" => "image/jpeg", "png" => "image/png" );
    if( in_array($extension, array_keys($imageTypes)) ) 
    {
        setHeader("Content-type", $imageTypes[$extension], true);
        setDownloadFileName($fileName, true);
    }
    else
    {
        setHeader("Content-type", "unknown/unknown", true);
        setDownloadFileName($fileName);
    }

    setHeader("ETag", TIMENOW, true);
    setHeader("Content-Length", $filesize, true);
    echo $contents;
    exit();
}

function setDownloadFileName($fileName, $inline = false)
{
    global $TSUE;
    $ext = file_extension($fileName);
    $justName = str_replace("." . $ext, "", $fileName);
    $websiteURL = parse_url($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
    $siteURL = str_replace(array( "http://", "https://", "www." ), "", (isset($websiteURL["host"]) ? $websiteURL["host"] : $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]));
    $fileName = $justName . "(" . $siteURL . ")." . $ext;
    setHeader("Content-Disposition", (($inline ? "inline" : "attachment")) . "; filename=\"" . str_replace("\"", "", $fileName) . "\"", true);
}

function setHeader($name, $value, $replace = true)
{
    $value = str_replace(array( "\r", "\n" ), array( "\\r", "\\n" ), $value);
    header($name . ": " . $value, $replace);
}



<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function showFile($File, $fullDescription = true)
{
    global $TSUE;
    $screenshots = "";
    if( $File["preview"] && file_exists(REALPATH . "data/downloads/previews/" . $File["preview"]) ) 
    {
        $screenshots = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/downloads/previews/" . $File["preview"] . "\" rel=\"fancybox\"><img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/downloads/previews/" . $File["preview"] . "\" alt=\"\" /></a>";
    }

    $File["title"] = strip_tags($File["title"]);
    $File["description"] = $TSUE["TSUE_Parser"]->parse($File["description"]);
    if( !$fullDescription ) 
    {
        $File["description"] = substr(strip_tags($File["description"]), 0, 180) . " ...";
    }

    $PAGEID = PAGEID;
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_show_file") . "\";");
    return $Output;
}

function checkOnlineStatus()
{
    global $TSUE;
    if( !has_permission("canview_dm") ) 
    {
        (defined("IS_AJAX") ? ajax_message(get_phrase("permission_denied"), "-ERROR-") : exit);
    }

    if( !$TSUE["TSUE_Settings"]->settings["downloads"]["online"] ) 
    {
        if( $TSUE["TSUE_Settings"]->settings["downloads"]["offline_access"] && in_array($TSUE["TSUE_Member"]->info["membergroupid"], tsue_explode(",", $TSUE["TSUE_Settings"]->settings["downloads"]["offline_access"])) ) 
        {
            return NULL;
        }

        (defined("IS_AJAX") ? ajax_message($TSUE["TSUE_Settings"]->settings["downloads"]["offline_message"], "-ERROR-") : exit);
    }

}

function downloadLocalFile($File)
{
    global $TSUE;
    if( headers_sent() ) 
    {
        show_error("Critical Error: Headers already sent!");
    }

    if( ini_get("zlib.output_compression") ) 
    {
        ini_set("zlib.output_compression", "Off");
    }

    $isExternal = !$File["size"] && !$File["filename"] && $File["external_link"];
    if( !$isExternal ) 
    {
        $fullPath = REALPATH . "/data/downloads/files/" . $File["filename"];
        if( !is_file($fullPath) ) 
        {
            show_error(get_phrase("message_content_error"));
        }

    }

    if( !$isExternal && $TSUE["TSUE_Member"]->info["permissions"]["max_simultaneous_downloads"] ) 
    {
        $activeDownloads = $TSUE["TSUE_Database"]->row_count("SELECT sid FROM tsue_downloads_session WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND active = 1");
        if( $TSUE["TSUE_Member"]->info["permissions"]["max_simultaneous_downloads"] <= $activeDownloads ) 
        {
            show_error(get_phrase("dl_restrict_sim"));
        }

    }

    $one_day = TIMENOW - 86400;
    $daily = $TSUE["TSUE_Database"]->query_result("SELECT COUNT(*) as dl, SUM(size) as bw FROM tsue_downloads_session WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND started > " . $one_day);
    if( $TSUE["TSUE_Member"]->info["permissions"]["max_bandwidth_usage_per_day"] && $daily && $TSUE["TSUE_Member"]->info["permissions"]["max_bandwidth_usage_per_day"] <= $daily["bw"] ) 
    {
        show_error(get_phrase("dl_restrict_daily_bw"));
    }

    if( $TSUE["TSUE_Member"]->info["permissions"]["max_downloads_per_day"] && $daily && $TSUE["TSUE_Member"]->info["permissions"]["max_downloads_per_day"] <= $daily["dl"] ) 
    {
        show_error(get_phrase("dl_restrict_daily_dl"));
    }

    if( !$isExternal ) 
    {
        $download_rate = ($TSUE["TSUE_Member"]->info["permissions"]["speed_throttling_dm"] ? 0 + $TSUE["TSUE_Member"]->info["permissions"]["speed_throttling_dm"] : 4096);
        setHeader("Content-type", tsue_mime_content_type($File["filename"]), true);
        setHeader("ETag", TIMENOW, true);
        setHeader("Content-Length", $File["size"], true);
        setDownloadFileName($File["filename"]);
        if( $localFile = fopen($fullPath, "rb") ) 
        {
            $BuildQuery = array( "downloads" => array( "escape" => 0, "value" => "downloads+1" ) );
            $TSUE["TSUE_Database"]->update("tsue_downloads", $BuildQuery, "did=" . $TSUE["TSUE_Database"]->escape($File["did"]));
            $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "started" => TIMENOW, "size" => $File["size"], "active" => 1 );
            $TSUE["TSUE_Database"]->insert("tsue_downloads_session", $BuildQuery);
            $sid = $TSUE["TSUE_Database"]->insert_id();
            while( !feof($localFile) ) 
            {
                if( connection_status() != CONNECTION_NORMAL ) 
                {
                    break;
                }

                echo fread($localFile, round($download_rate * 1024));
                @flush();
                sleep(1);
            }
            fclose($localFile);
            update_session($sid);
        }
        else
        {
            show_error(get_phrase("message_content_error"));
        }

        return false;
    }

    $BuildQuery = array( "downloads" => array( "escape" => 0, "value" => "downloads+1" ) );
    $TSUE["TSUE_Database"]->update("tsue_downloads", $BuildQuery, "did=" . $TSUE["TSUE_Database"]->escape($File["did"]));
    header("Location: " . $File["external_link"]);
    exit();
}

function deleteFile($filename = "")
{
    if( !$filename ) 
    {
        return false;
    }

    $filePath = REALPATH . "data/downloads/files/" . $filename;
    if( file_exists($filePath) ) 
    {
        @unlink($filePath);
    }

}

function update_session($sid = 0)
{
    global $TSUE;
    if( $sid ) 
    {
        $BuildQuery = array( "active" => array( "escape" => 0, "value" => 0 ) );
        $TSUE["TSUE_Database"]->update("tsue_downloads_session", $BuildQuery, "sid=" . $TSUE["TSUE_Database"]->escape($sid));
    }

}

function categorySelectBox($selectedID = 0, $selectName = "Upload[cid]")
{
    global $TSUE;
    $Options = "";
    $Categories = $TSUE["TSUE_Database"]->query("SELECT cid, cname, cviewpermissions FROM tsue_downloads_categories ORDER BY `sort` ASC");
    if( $TSUE["TSUE_Database"]->num_rows($Categories) ) 
    {
        while( $Category = $TSUE["TSUE_Database"]->fetch_assoc($Categories) ) 
        {
            if( hasViewPermission($Category["cviewpermissions"]) ) 
            {
                $Options .= "\r\n\t\t\t\t<option value=\"" . $Category["cid"] . "\"" . (($selectedID == $Category["cid"] ? " selected=\"selected\"" : "")) . ">" . $Category["cname"] . "</option>";
            }

        }
    }

    if( !$Options ) 
    {
        return false;
    }

    $categorySelectBox = "\r\n\t<select name=\"" . $selectName . "\" id=\"category\">\r\n\t\t<option value=\"0\"> --- </option>\r\n\t\t" . $Options . "\r\n\t</select>";
    return $categorySelectBox;
}

function setDownloadFileName($fileName, $inline = false)
{
    setHeader("Content-Disposition", (($inline ? "inline" : "attachment")) . "; filename=\"" . str_replace("\"", "", $fileName) . "\"", true);
}

function setHeader($name, $value, $replace = true)
{
    $value = str_replace(array( "\r", "\n" ), array( "\\r", "\\n" ), $value);
    header($name . ": " . $value, $replace);
}

function tsue_mime_content_type($filename)
{
    $mime_types = array( "txt" => "text/plain", "htm" => "text/html", "html" => "text/html", "php" => "text/html", "css" => "text/css", "js" => "application/javascript", "json" => "application/json", "xml" => "application/xml", "swf" => "application/x-shockwave-flash", "flv" => "video/x-flv", "png" => "image/png", "jpe" => "image/jpeg", "jpeg" => "image/jpeg", "jpg" => "image/jpeg", "gif" => "image/gif", "bmp" => "image/bmp", "ico" => "image/vnd.microsoft.icon", "tiff" => "image/tiff", "tif" => "image/tiff", "svg" => "image/svg+xml", "svgz" => "image/svg+xml", "zip" => "application/zip", "rar" => "application/x-rar-compressed", "exe" => "application/x-msdownload", "msi" => "application/x-msdownload", "cab" => "application/vnd.ms-cab-compressed", "mp3" => "audio/mpeg", "qt" => "video/quicktime", "mov" => "video/quicktime", "pdf" => "application/pdf", "psd" => "image/vnd.adobe.photoshop", "ai" => "application/postscript", "eps" => "application/postscript", "ps" => "application/postscript", "doc" => "application/msword", "rtf" => "application/rtf", "xls" => "application/vnd.ms-excel", "ppt" => "application/vnd.ms-powerpoint", "odt" => "application/vnd.oasis.opendocument.text", "ods" => "application/vnd.oasis.opendocument.spreadsheet" );
    $ext = file_extension($filename);
    if( array_key_exists($ext, $mime_types) ) 
    {
        return $mime_types[$ext];
    }

    if( function_exists("finfo_open") ) 
    {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;
    }

    return "application/octet-stream";
}



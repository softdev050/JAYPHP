<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function fetchRemoteURL($url)
{
    global $TSUE;
    $tmpName = REALPATH . "data/cache/" . md5($url) . ".tmp";
    $fh = fopen($tmpName, "w");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FILE, $fh);
    curl_setopt($ch, CURLOPT_RANGE, "0-" . $TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"]);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fh);
    if( !($image = getimagesize($tmpName)) ) 
    {
        unlink($tmpName);
        return false;
    }

    return array( $tmpName, $image );
}

function prepareRemoteImage($tmp_name, $name, $Path)
{
    copy($tmp_name, $Path . "s/" . $name);
    copy($tmp_name, $Path . "l/" . $name);
    unlink($tmp_name);
    require_once(REALPATH . "/library/classes/class_upload.php");
    $Small = new Upload($Path . "s/" . $name);
    if( $Small->uploaded ) 
    {
        $Small->no_upload_check = true;
        $Small->image_resize = true;
        $Small->image_x = 125;
        $Small->image_y = 80;
        $Small->Process();
    }

}

function prepareImage($Image, $Path)
{
    require_once(REALPATH . "/library/classes/class_upload.php");
    $Small = new Upload($Image);
    if( $Small->uploaded ) 
    {
        $Small->image_resize = true;
        $Small->image_x = 125;
        $Small->image_y = 80;
        $Small->Process($Path . "s/");
    }

    $Original = new Upload($Image);
    if( $Original->uploaded ) 
    {
        $Original->image_resize = false;
        $Original->Process($Path . "l/");
    }

}

function checkOnlineStatus()
{
    global $TSUE;
    if( !has_permission("canview_image_gallery") ) 
    {
        (defined("IS_AJAX") ? ajax_message(get_phrase("permission_denied"), "-ERROR-") : exit);
    }

    if( !$TSUE["TSUE_Settings"]->settings["gallery"]["online"] ) 
    {
        if( $TSUE["TSUE_Settings"]->settings["gallery"]["offline_access"] && in_array($TSUE["TSUE_Member"]->info["membergroupid"], tsue_explode(",", $TSUE["TSUE_Settings"]->settings["gallery"]["offline_access"])) ) 
        {
            return NULL;
        }

        (defined("IS_AJAX") ? ajax_message($TSUE["TSUE_Settings"]->settings["gallery"]["offline_message"], "-ERROR-") : exit);
    }

}

function buildGalleryImage($Image)
{
    global $TSUE;
    $friendlyFileName = html_clean($Image["filename"]);
    if( is_file(REALPATH . "data/gallery/s/" . $Image["filename"]) && is_file(REALPATH . "data/gallery/l/" . $Image["filename"]) ) 
    {
        return "\r\n\t\t<div style=\"padding: 2px; background: #fff; border: 1px solid #ccc; float: left; margin: 0 4px 5px 0; text-align: center;\" rel=\"galleryImages\" class=\"clickable\">\r\n\t\t\t<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/gallery/l/" . $Image["filename"] . "\" class=\"fancybox\"><img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/gallery/s/" . $Image["filename"] . "\" title=\"" . $friendlyFileName . "\" alt=\"" . $friendlyFileName . "\" style=\"width: 65px; vertical-align: middle;\" /></a>\r\n\t\t</div>";
    }

}

function galleryImagesForTinyMCE()
{
    global $TSUE;
    $Output = "\r\n\t<div style=\"clear: both;\"></div>\r\n\t<div style=\"font-weight: bold; font-size: 13px; margin: 10px 10px 0 10px; padding-top: 5px; border-top: 1px solid #999;\">" . get_phrase("gallery_images_uploaded_by_you") . "</div>";
    $Images = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE content_type IN (\"image_gallery_private\", \"image_gallery_public\") AND memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND associated = 1 ORDER BY upload_date DESC");
    if( $TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        $Output .= "\t\t\t\r\n\t\t<div style=\"margin: 5px 10px 10px 10px;\">";
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            $Output .= buildgalleryimage($Image);
        }
        $Output .= "\r\n\t\t\t<div style=\"clear: both;\"></div>\r\n\t\t</div>";
    }
    else
    {
        $Output .= "\r\n\t\t<div style=\"margin: 0 0 0 10px; font-weight: bold; color: red;\">" . get_phrase("message_nothing_found") . "</div>";
    }

    $Output .= "\r\n\t<div style=\"font-weight: bold; font-size: 13px; margin: 10px 10px 0 10px; padding-top: 5px; border-top: 1px solid #999;\">" . get_phrase("public_gallery_images") . "</div>";
    $Images = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE content_type = \"image_gallery_public\" AND memberid != " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND associated = 1 ORDER BY upload_date DESC");
    if( $TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        $Output .= "\t\t\t\r\n\t\t<div style=\"margin: 5px 10px 20px 10px;\">";
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            $Output .= buildgalleryimage($Image);
        }
        $Output .= "\r\n\t\t\t<div style=\"clear: both;\"></div>\r\n\t\t</div>";
    }
    else
    {
        $Output .= "\r\n\t\t<div style=\"margin: 0 0 0 10px; font-weight: bold; color: red;\">" . get_phrase("message_nothing_found") . "</div>";
    }

    return $Output;
}



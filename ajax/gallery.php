<?php 
define("SCRIPTNAME", "gallery.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

require_once(REALPATH . "library/functions/functions_imageGallery.php");
checkOnlineStatus();
switch( $TSUE["action"] ) 
{
    case "galleryImagesForTinyMCE":
        exit( galleryImagesForTinyMCE() );
    case "upload":
    case "galleryImageUploadForTinyMCE":
        if( !has_permission("canupload_image") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] ) 
        {
            $totalUploads = $TSUE["TSUE_Database"]->row_count("SELECT memberid FROM tsue_attachments WHERE content_type IN ('image_gallery_public','image_gallery_private') AND associated = 1 AND memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
            if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] <= $totalUploads ) 
            {
                ajax_message(get_phrase("ig_you_have_already_uploaded_max_x_images", $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"]), "-ERROR-");
            }

        }

        $maxFileSize = ($TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"] ? friendly_size($TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"]) : get_phrase("unlimited"));
        $Rules = get_phrase("image_gallery_upload_rules", $maxFileSize, $TSUE["TSUE_Settings"]->settings["gallery"]["allowed_file_types"]);
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate(($TSUE["action"] == "galleryImageUploadForTinyMCE" ? "ig_upload_form_dialog" : "ig_upload_form")) . "\";");
        ajax_message($Output, "", false, get_phrase("dm_upload_a_file"));
        break;
    case "delete":
        globalize("post", array( "attachment_id" => "INT" ));
        if( !$attachment_id ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $Image = $TSUE["TSUE_Database"]->query_result("SELECT a.*, m.membername FROM tsue_attachments a LEFT JOIN tsue_members m USING (memberid) WHERE attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id) . " AND content_type IN ('image_gallery_public','image_gallery_private') AND associated = 1");
        if( !$Image ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $hasDeletePermission = has_permission("candelete_image_gallery_files") || has_permission("candelete_own_image") && $Image["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
        if( !$hasDeletePermission ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id));
        if( is_file(REALPATH . "data/gallery/s/" . $Image["filename"]) ) 
        {
            @unlink(REALPATH . "data/gallery/s/" . $Image["filename"]);
        }

        if( is_file(REALPATH . "data/gallery/l/" . $Image["filename"]) ) 
        {
            @unlink(REALPATH . "data/gallery/l/" . $Image["filename"]);
        }

}


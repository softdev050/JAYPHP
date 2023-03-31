<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "gallery.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("gallery");
$Page_Title = get_phrase("image_gallery");
$Output = "";
require_once(REALPATH . "library/functions/functions_imageGallery.php");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("image_gallery") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=gallery&amp;pid=" . PAGEID ));
checkOnlineStatus();
if( $TSUE["action"] == "upload" && has_permission("canupload_image") ) 
{
    if( strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    globalize("post", array( "securitytoken" => "TRIM" ));
    if( !isValidToken($securitytoken) ) 
    {
        show_error(get_phrase("invalid_security_token"));
    }

    if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] ) 
    {
        $totalUploads = $TSUE["TSUE_Database"]->row_count("SELECT memberid FROM tsue_attachments WHERE content_type IN ('image_gallery_public','image_gallery_private') AND associated = 1 AND memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
        if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] <= $totalUploads ) 
        {
            show_error(get_phrase("ig_you_have_already_uploaded_max_x_images", $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"]));
        }

    }

    globalize("post", array( "private" => "INT", "url" => "TRIM" ));
    if( $url ) 
    {
        if( is_valid_domain($url) ) 
        {
            $parse = parse_url($url);
            if( $parse && isset($parse["host"]) && $parse["host"] && isset($parse["path"]) && $parse["path"] ) 
            {
                $fetchRemoteURL = fetchRemoteURL($url);
                if( $fetchRemoteURL ) 
                {
                    list($tmpName, $getimagesize) = $fetchRemoteURL;
                    $dFile = array( "name" => basename($parse["path"]), "type" => $getimagesize["mime"], "tmp_name" => $tmpName, "error" => 0, "size" => filesize($tmpName) );
                    $useExternalURL = true;
                }
                else
                {
                    $uploadErrors[] = "Unable to fetch the remote data.";
                }

            }
            else
            {
                $uploadErrors[] = "Unable to parse the url.";
            }

        }
        else
        {
            $uploadErrors[] = "You have entered a wrong URL.";
        }

    }
    else
    {
        $dFile = $_FILES["image"];
    }

    if( !isset($uploadErrors) ) 
    {
        $dFile["name"] = safe_names($dFile["name"]);
        if( !is_writable(REALPATH . "data/gallery/l/") || !is_writable(REALPATH . "data/gallery/s/") ) 
        {
            $uploadErrors[] = get_phrase("upload_error2");
        }
        else
        {
            if( !$dFile["name"] || !$dFile["type"] || !$dFile["tmp_name"] || $dFile["error"] == 4 || $dFile["size"] == 0 ) 
            {
                $uploadErrors[] = get_phrase("upload_error4");
            }
            else
            {
                if( !in_array(file_extension(strtolower($dFile["name"])), tsue_explode(",", strtolower($TSUE["TSUE_Settings"]->settings["gallery"]["allowed_file_types"]))) ) 
                {
                    $uploadErrors[] = get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["gallery"]["allowed_file_types"]);
                }
                else
                {
                    if( $TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"] && $TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"] < $dFile["size"] ) 
                    {
                        $uploadErrors[] = get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["gallery"]["max_filesize"]));
                    }
                    else
                    {
                        if( file_exists(REALPATH . "data/gallery/l/" . $dFile["name"]) ) 
                        {
                            $uploadErrors[] = get_phrase("upload_error7");
                        }
                        else
                        {
                            if( !isset($useExternalURL) && !is_uploaded_file($dFile["tmp_name"]) ) 
                            {
                                $uploadErrors[] = get_phrase("upload_error8");
                            }

                        }

                    }

                }

            }

        }

        $getimagesize = getimagesize($dFile["tmp_name"]);
        if( !$getimagesize || !preg_match("#image#i", $getimagesize["mime"]) ) 
        {
            $uploadErrors[] = get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["gallery"]["allowed_file_types"] . " (" . $getimagesize["mime"] . ")");
        }

    }

    if( isset($uploadErrors) ) 
    {
        $Output .= show_error(implode("<br />", $uploadErrors), "", false);
    }
    else
    {
        if( isset($dFile) && $dFile ) 
        {
            if( isset($useExternalURL) ) 
            {
                prepareRemoteImage($dFile["tmp_name"], $dFile["name"], REALPATH . "data/gallery/");
            }
            else
            {
                prepareImage($dFile, REALPATH . "data/gallery/");
            }

            $BuildQuery = array( "content_type" => ($private ? "image_gallery_private" : "image_gallery_public"), "upload_date" => TIMENOW, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "filename" => $dFile["name"], "filesize" => $dFile["size"], "associated" => 1 );
            if( $TSUE["TSUE_Database"]->insert("tsue_attachments", $BuildQuery) ) 
            {
                $attachment_id = $TSUE["TSUE_Database"]->insert_id();
                redirect("?p=gallery&pid=" . PAGEID . "&action=viewFile&attachment_id=" . $attachment_id);
            }
            else
            {
                $Output .= show_error(get_phrase("database_error"), "", false);
                if( is_file(REALPATH . "data/gallery/s/" . $dFile["name"]) ) 
                {
                    @unlink(REALPATH . "data/gallery/s/" . $dFile["name"]);
                }

                if( is_file(REALPATH . "data/gallery/l/" . $dFile["name"]) ) 
                {
                    @unlink(REALPATH . "data/gallery/l/" . $dFile["name"]);
                }

            }

        }

    }

}

if( $TSUE["action"] == "viewFile" ) 
{
    globalize("get", array( "attachment_id" => "INT" ));
    if( !$attachment_id ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $Image = $TSUE["TSUE_Database"]->query_result("SELECT a.*, m.membername FROM tsue_attachments a LEFT JOIN tsue_members m USING (memberid) WHERE attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id) . " AND content_type IN ('image_gallery_public','image_gallery_private') AND associated = 1");
    if( !$Image ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( !file_exists(REALPATH . "data/gallery/l/" . $Image["filename"]) ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $BuildQuery = array( "view_count" => array( "escape" => 0, "value" => "view_count+1" ) );
    $TSUE["TSUE_Database"]->update("tsue_attachments", $BuildQuery, "attachment_id=" . $TSUE["TSUE_Database"]->escape($attachment_id));
    $friendlyFileName = html_clean($Image["filename"]);
    $fileSize = friendly_size($Image["filesize"]);
    $viewCount = friendly_number_format($Image["view_count"]);
    $uploadDate = convert_relative_time($Image["upload_date"], false);
    $image_gallery_file_details = get_phrase("image_gallery_file_details", $friendlyFileName, $fileSize, $viewCount, $uploadDate, $Image["membername"]);
    AddBreadcrumb(array( $friendlyFileName => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=gallery&amp;pid=" . PAGEID . "&amp;action=viewFile&amp;attachment_id=" . $attachment_id ));
    $hasDeletePermission = has_permission("candelete_image_gallery_files") || has_permission("candelete_own_image") && $Image["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
    $ig_delete_image = "";
    if( $hasDeletePermission ) 
    {
        eval("\$ig_delete_image = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ig_delete_image") . "\";");
    }

    $report_ig_foto = "";
    if( has_permission("canreport") ) 
    {
        $content_id = $Image["attachment_id"];
        eval("\$report_ig_foto = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_ig_foto") . "\";");
    }

    eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("ig_image_big_box") . "\";");
}

if( !$Output ) 
{
    $gallery_upload = "";
    if( has_permission("canupload_image") ) 
    {
        eval("\$gallery_upload = \"" . $TSUE["TSUE_Template"]->LoadTemplate("gallery_upload") . "\";");
    }

    $ig_my_images = "";
    if( !is_member_of("unregistered") ) 
    {
        eval("\$ig_my_images = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ig_my_images") . "\";");
    }

    $gallery_buttons = "";
    if( $gallery_upload || $ig_my_images ) 
    {
        eval("\$gallery_buttons = \"" . $TSUE["TSUE_Template"]->LoadTemplate("gallery_buttons") . "\";");
    }

    $WHERE = " WHERE (content_type = 'image_gallery_public' OR (content_type = 'image_gallery_private' AND memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")) AND associated = 1";
    if( has_permission("candelete_image_gallery_files") ) 
    {
        $WHERE = " WHERE content_type IN('image_gallery_public','image_gallery_private') AND associated = 1";
    }

    if( !is_member_of("unregistered") ) 
    {
        globalize("get", array( "my_images" => "INT" ));
        if( $my_images ) 
        {
            $WHERE .= " AND memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]);
        }

    }

    $CountQuery = $TSUE["TSUE_Database"]->query("SELECT attachment_id FROM tsue_attachments" . $WHERE);
    $Pagination = Pagination($TSUE["TSUE_Database"]->num_rows($CountQuery), $TSUE["TSUE_Settings"]->settings["gallery"]["files_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=gallery&amp;pid=400&amp;" . ((isset($my_images) && $my_images ? "my_images=1&amp;" : "")));
    $Images = $TSUE["TSUE_Database"]->query("SELECT a.*, m.membername FROM tsue_attachments a LEFT JOIN tsue_members m USING (memberid)" . $WHERE . " ORDER BY upload_date DESC " . $Pagination["0"]);
    if( !$TSUE["TSUE_Database"]->num_rows($Images) ) 
    {
        $Output .= show_error(get_phrase("message_nothing_found") . $gallery_upload . $ig_my_images, "", false);
    }
    else
    {
        $imageBoxes = "";
        while( $Image = $TSUE["TSUE_Database"]->fetch_assoc($Images) ) 
        {
            if( file_exists(REALPATH . "data/gallery/s/" . $Image["filename"]) ) 
            {
                $friendlyFileName = html_clean($Image["filename"]);
                $fileSize = friendly_size($Image["filesize"]);
                $viewCount = friendly_number_format($Image["view_count"]);
                $uploadDate = convert_relative_time($Image["upload_date"], false);
                $image_gallery_file_details = get_phrase("image_gallery_file_details", $friendlyFileName, $fileSize, $viewCount, $uploadDate, $Image["membername"]);
                eval("\$imageBoxes .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("ig_image_box") . "\";");
            }
            else
            {
                $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id = " . $TSUE["TSUE_Database"]->escape($Image["attachment_id"]));
                if( file_exists(REALPATH . "data/gallery/l/" . $Image["filename"]) ) 
                {
                    @unlink(REALPATH . "data/gallery/l/" . $Image["filename"]);
                }

            }

        }
        if( !$imageBoxes ) 
        {
            $Output .= show_error(get_phrase("message_nothing_found"), "", false);
        }
        else
        {
            eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("ig_image_boxes") . "\";");
        }

    }

}

PrintOutput($Output, $Page_Title);


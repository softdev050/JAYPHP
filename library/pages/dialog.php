<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "dialog.php");
define("NO_SESSION_UPDATE", 1);
require("./library/init/init.php");
globalize("get", array( "dialog" => "TRIM" ));
$Page_Title = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"];
$Content = get_phrase("permission_denied");
$JSPhrases = "";
$PAGEID = PAGEID;
$PAGEFILE = PAGEFILE;
eval("\$main_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("main_javascript") . "\";");
if( $dialog == "link" ) 
{
    $Page_Title = get_phrase("tinymce_insert_edit_link");
    eval("\$Content = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_dialog_link") . "\";");
}

if( $dialog == "image" ) 
{
    $Error = "";
    if( $TSUE["action"] == "upload" && has_permission("canupload_image") && strtolower($_SERVER["REQUEST_METHOD"]) == "post" ) 
    {
        require_once(REALPATH . "library/functions/functions_imageGallery.php");
        checkOnlineStatus();
        globalize("post", array( "securitytoken" => "TRIM", "private" => "INT" ));
        $dFile = (isset($_FILES["image"]) ? $_FILES["image"] : "");
        if( $dFile && isset($dFile["name"]) ) 
        {
            $dFile["name"] = safe_names($dFile["name"]);
        }

        if( !isValidToken($securitytoken) ) 
        {
            $uploadErrors[] = show_error(get_phrase("invalid_security_token"), "", false);
        }

        if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] ) 
        {
            $totalUploads = $TSUE["TSUE_Database"]->row_count("SELECT memberid FROM tsue_attachments WHERE content_type IN ('image_gallery_public','image_gallery_private') AND associated = 1 AND memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
            if( $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"] <= $totalUploads ) 
            {
                $uploadErrors[] = show_error(get_phrase("ig_you_have_already_uploaded_max_x_images", $TSUE["TSUE_Member"]->info["permissions"]["max_image_uploads"]), "", false);
            }

        }

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
                            if( !is_uploaded_file($dFile["tmp_name"]) ) 
                            {
                                $uploadErrors[] = get_phrase("upload_error8");
                            }

                        }

                    }

                }

            }

        }

        if( !isset($uploadErrors) ) 
        {
            $getimagesize = getimagesize($dFile["tmp_name"]);
            if( !$getimagesize || !preg_match("#image#i", $getimagesize["mime"]) ) 
            {
                $uploadErrors[] = get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["gallery"]["allowed_file_types"] . " (" . $getimagesize["mime"] . ")");
            }

        }

        if( isset($uploadErrors) ) 
        {
            $Error = show_error(implode("<br />", $uploadErrors), "", false);
        }
        else
        {
            prepareImage($dFile, REALPATH . "data/gallery/");
            $BuildQuery = array( "content_type" => ($private ? "image_gallery_private" : "image_gallery_public"), "upload_date" => TIMENOW, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "filename" => $dFile["name"], "filesize" => $dFile["size"], "associated" => 1 );
            if( $TSUE["TSUE_Database"]->insert("tsue_attachments", $BuildQuery) ) 
            {
                header("Location: ?p=dialog&dialog=image&uploaded=true");
                exit();
            }

            $Error = show_error(get_phrase("database_error"), "", false);
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

    $jsUploadedVar = "var isUploaded=false;";
    if( isset($_GET["uploaded"]) && $_GET["uploaded"] == "true" ) 
    {
        $jsUploadedVar = "var isUploaded=true;";
    }

    $Page_Title = get_phrase("tinymce_insert_edit_image");
    eval("\$Content = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_dialog_image") . "\";");
}

if( $dialog == "code" ) 
{
    $Page_Title = get_phrase("tinymce_insert_code");
    eval("\$Content = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_dialog_code") . "\";");
}

if( $dialog == "smilies" ) 
{
    if( $TSUE["TSUE_Settings"]->settings["dialog_smilies_cache"] ) 
    {
        $_SContent = "";
        foreach( $TSUE["TSUE_Settings"]->settings["dialog_smilies_cache"] as $Smilie ) 
        {
            eval("\$_SContent .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("smilie_tinymce") . "\";");
        }
        $Page_Title = get_phrase("tinymce_smilies");
        eval("\$Content = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_dialog_smilies") . "\";");
    }
    else
    {
        $Content = get_phrase("invalid_cache");
    }

}

exit( $Content );


<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "downloads.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "downloads", "comments" ));
$Page_Title = get_phrase("dm_title");
$Output = "";
$WHERE = "";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("dm_title") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID ));
globalize(array( "post", "get" ), array( "keywords" => "DECODE" ));
require_once(REALPATH . "library/functions/functions_downloads.php");
checkOnlineStatus();
if( $keywords ) 
{
    AddBreadcrumb(array( get_phrase("forums_search_results") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID . "&amp;keywords=" . urlencode($keywords) ));
    $WHERE = explodeSearchKeywords("title,description", $keywords);
    $CountQuery = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE did FROM tsue_downloads WHERE " . $WHERE);
    if( !$CountQuery ) 
    {
        $Output .= show_error(get_phrase("message_nothing_found"), "", false);
    }
    else
    {
        $Pagination = Pagination($CountQuery, $TSUE["TSUE_Settings"]->settings["downloads"]["files_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=300&amp;action=search&amp;keywords=" . urlencode($keywords) . "&amp;");
        $Files = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE * FROM tsue_downloads WHERE " . $WHERE . " ORDER BY added DESC " . $Pagination["0"]);
        if( !$TSUE["TSUE_Database"]->num_rows($Files) ) 
        {
            $Output .= show_error(get_phrase("message_nothing_found"), "", false);
        }
        else
        {
            $Output .= $Pagination["1"];
            while( $File = $TSUE["TSUE_Database"]->fetch_assoc($Files) ) 
            {
                $Output .= showFile($File, false);
            }
            $Output .= $Pagination["1"];
        }

    }

}

if( $TSUE["action"] == "edit" ) 
{
    globalize("get", array( "did" => "INT" ));
    if( !$did ) 
    {
        show_error(get_phrase("message_required_fields_error"));
    }

    $File = $TSUE["TSUE_Database"]->query_result("SELECT cid, title, description, filename, preview, memberid, external_link FROM tsue_downloads WHERE did = " . $TSUE["TSUE_Database"]->escape($did));
    if( !$File ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $canEdit = has_permission("canedit_files") || has_permission("canedit_own_files") && $File["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
    if( !$canEdit ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $Page_Title = get_phrase("button_edit") . ": " . strip_tags($File["title"]);
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID . "&amp;action=edit&amp;did=" . $did ));
    $Errors = "";
    if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" ) 
    {
        globalize("post", array( "securitytoken" => "TRIM", "importFile" => "TRIM" ));
        $importPath = REALPATH . "data/downloads/import/";
        if( !isValidToken($securitytoken) ) 
        {
            show_error(get_phrase("invalid_security_token"));
        }

        $File = array_merge($File, $_POST["File"]);
        if( !$File["title"] ) 
        {
            $Errors[] = get_phrase("dashboard_cron_entries_cron_title_error");
        }

        if( !$File["description"] ) 
        {
            $Errors[] = get_phrase("valid_description_required");
        }

        $File["cid"] = intval($File["cid"]);
        if( !$File["cid"] ) 
        {
            $Errors[] = get_phrase("you_didnt_select_a_category");
        }

        if( isset($_FILES["dFile"]) && $_FILES["dFile"]["name"] ) 
        {
            $dFile = $_FILES["dFile"];
            $dFile["name"] = safe_names($dFile["name"]);
            $checkUploadErrors = checkuploaderrors($dFile, REALPATH . "data/downloads/files/");
            if( $checkUploadErrors ) 
            {
                $uploadErrors[] = $checkUploadErrors;
            }

        }
        else
        {
            if( !empty($File["external_link"]) ) 
            {
                if( !is_valid_domain($File["external_link"]) ) 
                {
                    $Errors[] = get_phrase("invalid_url");
                }

            }
            else
            {
                if( $importFile && is_file($importPath . $importFile) ) 
                {
                    $dFile["name"] = safe_names($importFile);
                    $dFile["type"] = "application/upload";
                    $dFile["tmp_name"] = $importPath . $importFile;
                    $dFile["error"] = 0;
                    $dFile["size"] = filesize($importPath . $importFile);
                    $isImport = true;
                    $checkUploadErrors = checkuploaderrors($dFile, REALPATH . "data/downloads/files/", false);
                    if( $checkUploadErrors ) 
                    {
                        $uploadErrors[] = $checkUploadErrors;
                    }

                }

            }

        }

        if( isset($_FILES["pFile"]) && $_FILES["pFile"]["name"] ) 
        {
            $pFile = $_FILES["pFile"];
            $pFile["name"] = safe_names($pFile["name"]);
            $_AllowedImages = array( "jpg", "gif", "png", "jpeg" );
            if( !is_writable(REALPATH . "data/downloads/previews/") ) 
            {
                $uploadErrors[] = get_phrase("upload_error2");
            }
            else
            {
                if( !$pFile["name"] || !$pFile["type"] || !$pFile["tmp_name"] || $pFile["error"] == 4 || $pFile["size"] == 0 ) 
                {
                    $uploadErrors[] = get_phrase("upload_error4");
                }
                else
                {
                    if( !in_array(file_extension($pFile["name"]), $_AllowedImages) ) 
                    {
                        $uploadErrors[] = get_phrase("upload_error6", implode(", ", $_AllowedImages));
                    }
                    else
                    {
                        if( $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] && $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] < $pFile["size"] ) 
                        {
                            $uploadErrors[] = get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"]));
                        }
                        else
                        {
                            if( file_exists(REALPATH . "data/downloads/previews/" . $pFile["name"]) ) 
                            {
                                $uploadErrors[] = get_phrase("upload_error7");
                            }
                            else
                            {
                                if( !is_uploaded_file($pFile["tmp_name"]) ) 
                                {
                                    $uploadErrors[] = get_phrase("upload_error8");
                                }

                            }

                        }

                    }

                }

            }

        }

        if( isset($uploadErrors) ) 
        {
            $Errors[] = implode("<br />", $uploadErrors);
        }

        if( $Errors ) 
        {
            $Errors = show_error(implode("<br />", $Errors), "", false);
        }
        else
        {
            $newFile = (isset($dFile["name"]) && $dFile["name"] ? $dFile["name"] : "");
            if( $newFile ) 
            {
                if( is_file(REALPATH . "data/downloads/files/" . $File["filename"]) ) 
                {
                    unlink(REALPATH . "data/downloads/files/" . $File["filename"]);
                }

                $File["filename"] = $newFile;
                $File["size"] = (isset($dFile["size"]) && $dFile["size"] ? 0 + $dFile["size"] : 0);
                $File["external_link"] = "";
            }
            else
            {
                if( !is_valid_domain($File["external_link"]) ) 
                {
                    $File["external_link"] = "";
                }
                else
                {
                    if( is_file(REALPATH . "data/downloads/files/" . $File["filename"]) ) 
                    {
                        unlink(REALPATH . "data/downloads/files/" . $File["filename"]);
                    }

                    $File["filename"] = "";
                    $File["size"] = 0;
                }

            }

            if( isset($pFile["name"]) && $pFile["name"] ) 
            {
                if( is_file(REALPATH . "data/downloads/previews/" . $File["preview"]) ) 
                {
                    unlink(REALPATH . "data/downloads/previews/" . $File["preview"]);
                }

                move_uploaded_file($pFile["tmp_name"], REALPATH . "data/downloads/previews/" . $pFile["name"]);
                $File["preview"] = $pFile["name"];
            }

            if( $TSUE["TSUE_Database"]->update("tsue_downloads", $File, "did=" . $TSUE["TSUE_Database"]->escape($did)) ) 
            {
                if( $newFile ) 
                {
                    if( isset($isImport) ) 
                    {
                        if( !copy($dFile["tmp_name"], REALPATH . "data/downloads/files/" . $dFile["name"]) ) 
                        {
                            $Errors = show_error(get_phrase("upload_error8"), "", false);
                        }
                        else
                        {
                            unlink($dFile["tmp_name"]);
                        }

                    }
                    else
                    {
                        if( !move_uploaded_file($dFile["tmp_name"], REALPATH . "data/downloads/files/" . $newFile) ) 
                        {
                            $Errors = show_error(get_phrase("upload_error8"), "", false);
                        }

                    }

                }

                if( !$Errors ) 
                {
                    redirect("?p=downloads&pid=" . PAGEID . "&action=viewFile&did=" . $did);
                }

            }
            else
            {
                $Errors = show_error(get_phrase("database_error"), "", false);
            }

        }

    }

    $categorySelectBox = categorySelectBox($File["cid"], "File[cid]");
    if( !$categorySelectBox ) 
    {
        show_error(get_phrase("no_download_category"));
    }

    $File["title"] = strip_tags($File["title"]);
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_edit_form") . "\";");
}

if( $TSUE["action"] == "upload" && has_permission("canupload_dm") ) 
{
    $Page_Title = get_phrase("dm_upload_a_file");
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID . "&amp;action=upload" ));
    $Upload = array( "cid" => 0, "title" => "", "description" => "", "filename" => "", "preview" => "", "size" => 0, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "downloads" => 0, "external_link" => "" );
    $Errors = "";
    if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" ) 
    {
        globalize("post", array( "securitytoken" => "TRIM" ));
        if( !isValidToken($securitytoken) ) 
        {
            show_error(get_phrase("invalid_security_token"));
        }

        $Upload = array_merge($Upload, $_POST["Upload"]);
        if( !$Upload["title"] ) 
        {
            $Errors[] = get_phrase("dashboard_cron_entries_cron_title_error");
        }

        if( !$Upload["description"] ) 
        {
            $Errors[] = get_phrase("valid_description_required");
        }

        $Upload["cid"] = intval($Upload["cid"]);
        if( !$Upload["cid"] ) 
        {
            $Errors[] = get_phrase("you_didnt_select_a_category");
        }

        if( isset($_FILES["dFile"]) && $_FILES["dFile"]["name"] ) 
        {
            $dFile = $_FILES["dFile"];
            $dFile["name"] = safe_names($dFile["name"]);
            $checkUploadErrors = checkuploaderrors($dFile, REALPATH . "data/downloads/files/");
            if( $checkUploadErrors ) 
            {
                $uploadErrors[] = $checkUploadErrors;
            }

        }
        else
        {
            if( !empty($Upload["external_link"]) ) 
            {
                if( !is_valid_domain($Upload["external_link"]) ) 
                {
                    $Errors[] = get_phrase("invalid_url");
                }

            }
            else
            {
                globalize("post", array( "importFile" => "TRIM" ));
                $importPath = REALPATH . "data/downloads/import/";
                if( $importFile && is_file($importPath . $importFile) ) 
                {
                    $dFile["name"] = safe_names($importFile);
                    $dFile["type"] = "application/upload";
                    $dFile["tmp_name"] = $importPath . $importFile;
                    $dFile["error"] = 0;
                    $dFile["size"] = filesize($importPath . $importFile);
                    $isImport = true;
                    $checkUploadErrors = checkuploaderrors($dFile, REALPATH . "data/downloads/files/", false);
                    if( $checkUploadErrors ) 
                    {
                        $uploadErrors[] = $checkUploadErrors;
                    }

                }
                else
                {
                    $uploadErrors[] = get_phrase("upload_error4");
                }

            }

        }

        if( isset($_FILES["pFile"]) && $_FILES["pFile"]["name"] ) 
        {
            $pFile = $_FILES["pFile"];
            $pFile["name"] = safe_names($pFile["name"]);
            $_AllowedImages = array( "jpg", "gif", "png", "jpeg" );
            if( !is_writable(REALPATH . "data/downloads/previews/") ) 
            {
                $uploadErrors[] = get_phrase("upload_error2");
            }
            else
            {
                if( !$pFile["name"] || !$pFile["type"] || !$pFile["tmp_name"] || $pFile["error"] == 4 || $pFile["size"] == 0 ) 
                {
                    $uploadErrors[] = get_phrase("upload_error4");
                }
                else
                {
                    if( !in_array(file_extension($pFile["name"]), $_AllowedImages) ) 
                    {
                        $uploadErrors[] = get_phrase("upload_error6", implode(", ", $_AllowedImages));
                    }
                    else
                    {
                        if( $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] && $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] < $pFile["size"] ) 
                        {
                            $uploadErrors[] = get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"]));
                        }
                        else
                        {
                            if( file_exists(REALPATH . "data/downloads/previews/" . $pFile["name"]) ) 
                            {
                                $uploadErrors[] = get_phrase("upload_error7");
                            }
                            else
                            {
                                if( !is_uploaded_file($pFile["tmp_name"]) ) 
                                {
                                    $uploadErrors[] = get_phrase("upload_error8");
                                }
                                else
                                {
                                    $Upload["preview"] = $pFile["name"];
                                }

                            }

                        }

                    }

                }

            }

        }

        if( isset($uploadErrors) ) 
        {
            $Errors[] = implode("<br />", $uploadErrors);
        }

        if( $Errors ) 
        {
            $Errors = show_error(implode("<br />", $Errors), "", false);
        }
        else
        {
            $Upload["filename"] = (isset($dFile["name"]) && $dFile["name"] ? $dFile["name"] : "");
            $Upload["size"] = (isset($dFile["size"]) && $dFile["size"] ? 0 + $dFile["size"] : 0);
            if( $TSUE["TSUE_Database"]->insert("tsue_downloads", $Upload) ) 
            {
                $did = $TSUE["TSUE_Database"]->insert_id();
                if( $Upload["filename"] && $Upload["size"] ) 
                {
                    if( isset($isImport) ) 
                    {
                        if( !copy($dFile["tmp_name"], REALPATH . "data/downloads/files/" . $dFile["name"]) ) 
                        {
                            $Errors = show_error(get_phrase("upload_error8"), "", false);
                            $TSUE["TSUE_Database"]->delete("tsue_downloads", "did=" . $TSUE["TSUE_Database"]->escape($did));
                        }
                        else
                        {
                            unlink($dFile["tmp_name"]);
                        }

                    }
                    else
                    {
                        if( !move_uploaded_file($dFile["tmp_name"], REALPATH . "data/downloads/files/" . $dFile["name"]) ) 
                        {
                            $Errors = show_error(get_phrase("upload_error8"), "", false);
                            $TSUE["TSUE_Database"]->delete("tsue_downloads", "did=" . $TSUE["TSUE_Database"]->escape($did));
                        }

                    }

                }

                if( !$Errors ) 
                {
                    if( $Upload["preview"] ) 
                    {
                        move_uploaded_file($pFile["tmp_name"], REALPATH . "data/downloads/previews/" . $pFile["name"]);
                    }

                    $BuildQuery = array( "total_files" => array( "escape" => 0, "value" => "total_files+1" ) );
                    $TSUE["TSUE_Database"]->update("tsue_downloads_categories", $BuildQuery, "cid=" . $TSUE["TSUE_Database"]->escape($Upload["cid"]));
                    redirect("?p=downloads&pid=" . PAGEID . "&action=viewFile&did=" . $did);
                }

            }
            else
            {
                $Errors = show_error(get_phrase("database_error"), "", false);
            }

        }

    }

    $categorySelectBox = categorySelectBox($Upload["cid"]);
    if( !$categorySelectBox ) 
    {
        show_error(get_phrase("no_download_category"));
    }

    if( $Upload["title"] ) 
    {
        $Upload["title"] = strip_tags($Upload["title"]);
    }

    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_upload_form") . "\";");
}

if( $TSUE["action"] == "viewFile" ) 
{
    globalize("get", array( "did" => "INT" ));
    if( $did ) 
    {
        $File = $TSUE["TSUE_Database"]->query_result("SELECT d.*, c.cname, c.cviewpermissions FROM tsue_downloads d LEFT JOIN tsue_downloads_categories c USING(cid) WHERE d.did = " . $TSUE["TSUE_Database"]->escape($did));
        if( $File && hasViewPermission($File["cviewpermissions"]) ) 
        {
            $Page_Title = strip_tags($File["title"]);
            $File["title"] = strip_tags($File["title"]);
            $File["description"] = $TSUE["TSUE_Parser"]->parse($File["description"]);
            $File["size"] = friendly_size($File["size"]);
            $File["downloads"] = friendly_number_format($File["downloads"]);
            $File["added"] = convert_time($File["added"]);
            $TSUE["TSUE_Language"]->phrase["torrents_category"] = get_phrase("torrents_category", $File["cname"]);
            AddBreadcrumb(array( substr($Page_Title, 0, 90) => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID . "&amp;action=viewFile&amp;did=" . $did ));
            require_once(REALPATH . "/library/functions/functions_getComments.php");
            $Comments = getComments("file_comments", $File["did"], (isset($_GET["comment_id"]) ? intval($_GET["comment_id"]) : 0));
            $canDownload = has_permission("candownload_dm");
            $download_button = "";
            if( $canDownload ) 
            {
                eval("\$download_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_download_file_button") . "\";");
            }

            $canEdit = has_permission("canedit_files") || has_permission("canedit_own_files") && $File["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
            $edit_button = "";
            if( $canEdit ) 
            {
                eval("\$edit_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_edit_file_button") . "\";");
            }

            $canDelete = has_permission("candelete_files") || has_permission("candelete_own_files") && $File["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
            $delete_button = "";
            if( $canDelete ) 
            {
                eval("\$delete_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_delete_file_button") . "\";");
            }

            $screenshots = "";
            if( $File["preview"] && file_exists(REALPATH . "data/downloads/previews/" . $File["preview"]) ) 
            {
                $screenshots = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/downloads/previews/" . $File["preview"] . "\" rel=\"fancybox\"><img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/downloads/previews/" . $File["preview"] . "\" alt=\"\" /></a>";
            }

            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_view_file") . "\";");
        }
        else
        {
            show_error(get_phrase("permission_denied"));
        }

    }

}

if( $TSUE["action"] == "download" ) 
{
    globalize("get", array( "did" => "INT" ));
    if( !$did || !has_permission("candownload_dm") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $File = $TSUE["TSUE_Database"]->query_result("SELECT d.*, c.cname, c.cviewpermissions, c.cdownloadpermissions FROM tsue_downloads d LEFT JOIN tsue_downloads_categories c USING(cid) WHERE d.did = " . $TSUE["TSUE_Database"]->escape($did));
    if( $File && hasViewPermission($File["cviewpermissions"]) && hasViewPermission($File["cdownloadpermissions"]) ) 
    {
        downloadLocalFile($File);
        exit();
    }

    show_error(get_phrase("permission_denied"));
}

if( $TSUE["action"] == "viewCategory" ) 
{
    globalize("get", array( "cid" => "INT" ));
    $Category = $TSUE["TSUE_Database"]->query_result("SELECT cname, cviewpermissions FROM tsue_downloads_categories WHERE cid = " . $TSUE["TSUE_Database"]->escape($cid));
    if( !$Category ) 
    {
        $Output .= show_error(get_phrase("message_nothing_found"), "", false);
    }
    else
    {
        if( !hasViewPermission($Category["cviewpermissions"]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }
        else
        {
            AddBreadcrumb(array( $Category["cname"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=" . PAGEID . "&amp;action=viewCategory&amp;cid=" . $cid ));
            $CountQuery = $TSUE["TSUE_Database"]->query("SELECT did FROM tsue_downloads WHERE cid = " . $TSUE["TSUE_Database"]->escape($cid));
            $Pagination = Pagination($TSUE["TSUE_Database"]->num_rows($CountQuery), $TSUE["TSUE_Settings"]->settings["downloads"]["files_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=downloads&amp;pid=300&amp;action=viewCategory&amp;cid=" . $cid . "&amp;");
            $Files = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_downloads WHERE cid = " . $TSUE["TSUE_Database"]->escape($cid) . " ORDER BY added DESC " . $Pagination["0"]);
            if( !$TSUE["TSUE_Database"]->num_rows($Files) ) 
            {
                $Output .= show_error(get_phrase("message_nothing_found"), "", false);
            }
            else
            {
                $Output .= $Pagination["1"];
                while( $File = $TSUE["TSUE_Database"]->fetch_assoc($Files) ) 
                {
                    $Output .= showFile($File, false);
                }
                $Output .= $Pagination["1"];
            }

        }

    }

}

if( !$Output ) 
{
    $dm_upload = "";
    if( has_permission("canupload_dm") ) 
    {
        eval("\$dm_upload = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dm_upload") . "\";");
    }

    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("search_downloads") . "\";");
    $File = $TSUE["TSUE_Database"]->query_result("SELECT d.*, c.cname, c.cviewpermissions FROM tsue_downloads d LEFT JOIN tsue_downloads_categories c USING(cid) WHERE \tINSTR(CONCAT(',',\tcviewpermissions ,','),'," . $TSUE["TSUE_Member"]->info["membergroupid"] . ",') > 0 ORDER BY rand() LIMIT 1");
    if( !$File ) 
    {
        $Output = show_error(get_phrase("message_nothing_found") . " " . $dm_upload, "", false);
    }
    else
    {
        $Output .= showFile($File);
    }

}

PrintOutput($Output, $Page_Title);
function checkUploadErrors($dFile = array(  ), $uploadPath = "", $is_uploaded_file = true)
{
    global $TSUE;
    if( !is_writable($uploadPath) ) 
    {
        return get_phrase("upload_error2");
    }

    if( !$dFile["name"] || !$dFile["type"] || !$dFile["tmp_name"] || $dFile["error"] == 4 || $dFile["size"] == 0 ) 
    {
        return get_phrase("upload_error4");
    }

    if( !in_array(file_extension($dFile["name"]), tsue_explode(",", $TSUE["TSUE_Settings"]->settings["downloads"]["allowed_file_types"])) ) 
    {
        return get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["downloads"]["allowed_file_types"]);
    }

    if( $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] && $TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"] < $dFile["size"] ) 
    {
        return get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["downloads"]["max_filesize"]));
    }

    if( file_exists($uploadPath . $dFile["name"]) ) 
    {
        return get_phrase("upload_error7");
    }

    if( $is_uploaded_file && !is_uploaded_file($dFile["tmp_name"]) ) 
    {
        return get_phrase("upload_error8");
    }

}



<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "subtitles.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("subtitles");
$Page_Title = get_phrase("subtitles_title");
$subTitleUploadPath = REALPATH . "/data/subTitles/";
$keywords = "";
$Output = "";
$Alfabe = explode(" ", "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z 0-9");
$selectedAlfabe = (isset($_GET["a"]) ? trim($_GET["a"]) : "");
if( $selectedAlfabe && !in_array($selectedAlfabe, $Alfabe) ) 
{
    show_error(get_phrase("no_results_found"));
}

AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID ));
if( !has_permission("canview_subtitles") ) 
{
    show_error(get_phrase("permission_denied"));
}

if( $TSUE["action"] == "download" ) 
{
    globalize("get", array( "sid" => "INT" ));
    if( !$sid ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( !has_permission("candownload_subtitles") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $subTitle = $TSUE["TSUE_Database"]->query_result("SELECT title,filename FROM tsue_subtitles WHERE sid = " . $TSUE["TSUE_Database"]->escape($sid));
    if( !$subTitle ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( !is_file($subTitleUploadPath . $subTitle["filename"]) ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $TSUE["TSUE_Database"]->update("tsue_subtitles", array( "downloads" => array( "escape" => 0, "value" => "downloads+1" ) ), "sid = " . $TSUE["TSUE_Database"]->escape($sid), true);
    require_once(REALPATH . "/library/functions/functions_downloadFile.php");
    downloadFile($subTitleUploadPath . $subTitle["filename"], $subTitle["title"] . "." . file_extension($subTitle["filename"]));
    exit();
}

if( $TSUE["action"] == "delete" ) 
{
    globalize("get", array( "sid" => "INT" ));
    if( !$sid ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( !has_permission("candelete_subtitles") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $subTitle = $TSUE["TSUE_Database"]->query_result("SELECT title,filename FROM tsue_subtitles WHERE sid = " . $TSUE["TSUE_Database"]->escape($sid));
    if( !$subTitle ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $TSUE["TSUE_Database"]->delete("tsue_subtitles", "sid = " . $TSUE["TSUE_Database"]->escape($sid));
    if( file_exists($subTitleUploadPath . $subTitle["filename"]) ) 
    {
        @unlink($subTitleUploadPath . $subTitle["filename"]);
    }

    $Message = get_phrase("subtitles_subtitle_deleted", $subTitle["title"]);
    logAction($Message);
    $Output .= show_done($Message, "", false);
}

if( $TSUE["action"] == "upload" ) 
{
    if( !has_permission("canupload_subtitles") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $Page_Title = get_phrase("button_upload");
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;action=upload" ));
    if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" ) 
    {
        globalize("post", array( "securitytoken" => "TRIM" ));
        if( !isValidToken($securitytoken) ) 
        {
            show_error(get_phrase("invalid_security_token"));
        }

        globalize("post", array( "title" => "TRIM", "language" => "TRIM", "fps" => "TRIM", "cd" => "INT", "tid" => "INT" ));
        $subTitleFile = (isset($_FILES["file"]) && $_FILES["file"]["tmp_name"] ? $_FILES["file"] : false);
        if( !$title || !$fps || !$cd || !$language ) 
        {
            show_error(get_phrase("message_required_fields_error"));
        }
        else
        {
            if( !$subTitleFile ) 
            {
                show_error(get_phrase("upload_error3"));
            }
            else
            {
                if( !is_uploaded_file($subTitleFile["tmp_name"]) ) 
                {
                    show_error(get_phrase("unable_upload"));
                }
                else
                {
                    if( !is_writable($subTitleUploadPath) ) 
                    {
                        show_error(get_phrase("upload_error2"));
                    }
                    else
                    {
                        if( !$subTitleFile["size"] ) 
                        {
                            show_error(get_phrase("upload_error4"));
                        }
                        else
                        {
                            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_max_file_size"] < $subTitleFile["size"] ) 
                            {
                                show_error(get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_max_file_size"])));
                            }
                            else
                            {
                                if( !in_array(file_extension($subTitleFile["name"]), tsue_explode(",", $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_allowed_file_types"])) ) 
                                {
                                    show_error(get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_allowed_file_types"]));
                                }
                                else
                                {
                                    if( $tid ) 
                                    {
                                        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
                                        if( !$Torrent ) 
                                        {
                                            show_error(get_phrase("torrents_not_found"));
                                        }

                                    }

                                    $buildQuery = array( "title" => $title, "language" => $language, "fps" => $fps, "cd" => $cd, "uploader" => $TSUE["TSUE_Member"]->info["memberid"], "date" => TIMENOW, "filename" => "", "tid" => $tid );
                                    $TSUE["TSUE_Database"]->insert("tsue_subtitles", $buildQuery);
                                    if( $SID = $TSUE["TSUE_Database"]->insert_id() ) 
                                    {
                                        $Message = get_phrase("subtitles_subtitle_uploaded", $title);
                                        logAction($Message);
                                        $subTitleFilename = $SID . "." . file_extension($subTitleFile["name"]);
                                        if( !move_uploaded_file($subTitleFile["tmp_name"], $subTitleUploadPath . $subTitleFilename) ) 
                                        {
                                            show_error(get_phrase("subtitles_saved_but_file_error"));
                                        }
                                        else
                                        {
                                            $TSUE["TSUE_Database"]->update("tsue_subtitles", array( "filename" => $subTitleFilename ), "sid=" . $TSUE["TSUE_Database"]->escape($SID));
                                            if( !$TSUE["TSUE_Database"]->affected_rows() ) 
                                            {
                                                show_error(get_phrase("subtitles_saved_but_file_error"));
                                            }
                                            else
                                            {
                                                $Output .= show_done($Message, "", false);
                                            }

                                        }

                                    }
                                    else
                                    {
                                        show_error(get_phrase("database_error"));
                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

    }

    if( !$Output ) 
    {
        $countrySelect = countryList("languageSelect");
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_upload_form") . "\";");
        PrintOutput($Output, $Page_Title);
    }

}

if( $TSUE["action"] == "edit" ) 
{
    $FPS["30.000"] = "";
    $FPS["29.970"] = $FPS["30.000"];
    $FPS["25.000"] = $FPS["29.970"];
    $FPS["24.000"] = $FPS["25.000"];
    $FPS["23.980"] = $FPS["24.000"];
    $FPS["23.976"] = $FPS["23.980"];
    $CD["5"] = "";
    $CD["4"] = $CD["5"];
    $CD["3"] = $CD["4"];
    $CD["2"] = $CD["3"];
    $CD["1"] = $CD["2"];
    if( !has_permission("canedit_subtitles") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    globalize("get", array( "sid" => "INT" ));
    if( !$sid ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $subTitle = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_subtitles WHERE sid = " . $TSUE["TSUE_Database"]->escape($sid));
    if( !$subTitle ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $Page_Title = get_phrase("button_edit");
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;action=edit&amp;sid=" . $sid ));
    if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" ) 
    {
        globalize("post", array( "securitytoken" => "TRIM" ));
        if( !isValidToken($securitytoken) ) 
        {
            show_error(get_phrase("invalid_security_token"));
        }

        globalize("post", array( "title" => "TRIM", "language" => "TRIM", "fps" => "TRIM", "cd" => "INT", "tid" => "INT" ));
        $subTitleFile = (isset($_FILES["file"]) && $_FILES["file"]["tmp_name"] ? $_FILES["file"] : false);
        $subTitle["title"] = $title;
        $subTitle["language"] = $language;
        $subTitle["fps"] = $fps;
        $subTitle["cd"] = $cd;
        $subTitle["tid"] = $tid;
        if( !$title || !$fps || !$cd || !$language ) 
        {
            show_error(get_phrase("message_required_fields_error"));
        }
        else
        {
            if( $subTitle["tid"] ) 
            {
                $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($subTitle["tid"]));
                if( !$Torrent ) 
                {
                    show_error(get_phrase("torrents_not_found"));
                }

            }

            if( $subTitleFile ) 
            {
                if( !is_uploaded_file($subTitleFile["tmp_name"]) ) 
                {
                    show_error(get_phrase("unable_upload"));
                }
                else
                {
                    if( !is_writable($subTitleUploadPath) ) 
                    {
                        show_error(get_phrase("upload_error2"));
                    }
                    else
                    {
                        if( !$subTitleFile["size"] ) 
                        {
                            show_error(get_phrase("upload_error4"));
                        }
                        else
                        {
                            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_max_file_size"] < $subTitleFile["size"] ) 
                            {
                                show_error(get_phrase("upload_error5", friendly_size($TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_max_file_size"])));
                            }
                            else
                            {
                                if( !in_array(file_extension($subTitleFile["name"]), tsue_explode(",", $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_allowed_file_types"])) ) 
                                {
                                    show_error(get_phrase("upload_error6", $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_allowed_file_types"]));
                                }
                                else
                                {
                                    if( file_exists($subTitleUploadPath . $subTitle["filename"]) ) 
                                    {
                                        unlink($subTitleUploadPath . $subTitle["filename"]);
                                    }

                                    $subTitleFilename = $sid . "." . file_extension($subTitleFile["name"]);
                                    if( !move_uploaded_file($subTitleFile["tmp_name"], $subTitleUploadPath . $subTitleFilename) ) 
                                    {
                                        show_error(get_phrase("subtitles_saved_but_file_error"));
                                    }

                                }

                            }

                        }

                    }

                }

            }

            $buildQuery = array( "title" => $subTitle["title"], "language" => $subTitle["language"], "fps" => $subTitle["fps"], "cd" => $subTitle["cd"], "filename" => (isset($subTitleFilename) && $subTitleFilename ? $subTitleFilename : $subTitle["filename"]), "tid" => $tid );
            $TSUE["TSUE_Database"]->update("tsue_subtitles", $buildQuery, "sid = " . $TSUE["TSUE_Database"]->escape($sid));
            $Message = get_phrase("subtitles_subtitle_edited", $subTitle["title"]);
            logAction($Message);
            $Output .= show_done($Message, "", false);
        }

    }

    if( !$Output ) 
    {
        $countrySelect = countryList("languageSelect");
        $CD[$subTitle["cd"]] = " selected=\"selected\"";
        $FPS[$subTitle["fps"]] = " selected=\"selected\"";
        $title = strip_tags($subTitle["title"]);
        $tid = $subTitle["tid"];
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_edit_form") . "\";");
        PrintOutput($Output, $Page_Title);
    }

}

$whereConditions = $pagerConditions = array(  );
if( $TSUE["action"] == "search" ) 
{
    globalize(array( "post", "get" ), array( "keywords" => "DECODE" ));
    if( $keywords ) 
    {
        $cleanKeywords = html_clean($keywords);
        $pagerConditions[] = "action=search";
        $pagerConditions[] = "keywords=" . $cleanKeywords;
        $whereConditions[] = explodeSearchKeywords("s.title", $keywords);
        AddBreadcrumb(array( get_phrase("button_search") . ": " . $cleanKeywords => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;action=search&amp;keywords=" . $cleanKeywords ));
    }

}
else
{
    if( $selectedAlfabe ) 
    {
        $pagerConditions[] = "a=" . html_clean($selectedAlfabe);
        if( $selectedAlfabe == "0-9" ) 
        {
            $whereConditions[] = "s.title REGEXP '^[0-9]'";
        }
        else
        {
            $whereConditions[] = "s.title LIKE '" . $TSUE["TSUE_Database"]->escape_no_quotes($selectedAlfabe) . "%'";
        }

        AddBreadcrumb(array( get_phrase("button_search") . ": " . $selectedAlfabe => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;a=" . $selectedAlfabe ));
    }

}

if( !empty($whereConditions) ) 
{
    $whereConditions = " WHERE " . implode(" AND ", $whereConditions);
}
else
{
    $whereConditions = "";
}

$subtitlesCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE s.sid FROM tsue_subtitles s" . $whereConditions, true);
$Pagination = Pagination($subtitlesCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["subtitles_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;" . ((!empty($pagerConditions) ? implode("&amp;", $pagerConditions) . "&amp;" : "")));
if( !$subtitlesCountQuery ) 
{
    if( $keywords ) 
    {
        show_error(get_phrase("no_results_found"));
    }
    else
    {
        show_error(get_phrase("subtitles_nothing_found"));
    }

}

$subTitles = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE s.*, m.membername, g.groupstyle FROM tsue_subtitles s LEFT JOIN tsue_members m ON(s.uploader=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid)" . $whereConditions . " ORDER BY s.date DESC " . $Pagination["0"]);
$count = 0;
$subTitleList = "";
$canEdit = has_permission("canedit_subtitles");
for( $canDelete = has_permission("candelete_subtitles"); $subTitle = $TSUE["TSUE_Database"]->fetch_assoc($subTitles); $count++ ) 
{
    $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
    $_memberid = $subTitle["uploader"];
    $_membername = getMembername($subTitle["membername"], $subTitle["groupstyle"]);
    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    $subTitle["title"] = strip_tags($subTitle["title"]);
    $subTitle["fps"] = strip_tags($subTitle["fps"]);
    $subTitle["cd"] = intval($subTitle["cd"]);
    $subTitle["downloads"] = friendly_number_format($subTitle["downloads"]);
    $subTitle["date"] = convert_relative_time($subTitle["date"]);
    if( $canDelete ) 
    {
        eval("\$deleteLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_delete_link") . "\";");
    }
    else
    {
        $deleteLink = "";
    }

    if( $canEdit ) 
    {
        eval("\$editLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_edit_link") . "\";");
    }
    else
    {
        $editLink = "";
    }

    eval("\$subTitleList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_list") . "\";");
}
$Alfabe = alfabe($selectedAlfabe, $Alfabe);
eval("\$searchSubtitle = \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_search_subtitle") . "\";");
eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("subtitles_table") . "\";");
PrintOutput($Output, $Page_Title);
function Alfabe($selectedAlfabe, $Alfabe)
{
    global $TSUE;
    $Pagination = "";
    foreach( $Alfabe as $a ) 
    {
        $extraClass = ($selectedAlfabe == $a ? " selectedAlfabe" : "");
        $Pagination .= "\r\n\t\t<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=subtitles&amp;pid=" . PAGEID . "&amp;a=" . $a . "\"><div class=\"alfabe" . $extraClass . "\">" . $a . "</div></a>";
    }
    return $Pagination;
}



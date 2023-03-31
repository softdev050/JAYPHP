<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "torrents.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "comments", "torrents" ));
$TSUE["TSUE_Template"]->loadJSPhrase(array( "turn_on_suggestions", "turn_off_suggestions", "turn_on_suggestions_alt", "searching", "enter_a_search_word", "forums_search_results", "message_nothing_found" ));
$Page_Title = get_phrase("torrents_title");
$Output = "";
globalize("get", array( "tid" => "INT" ));
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_torrents") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID ));
if( !has_permission("canview_torrents") ) 
{
    show_error(get_phrase("permission_denied"));
}

if( !is_member_of("unregistered") && get_permission("force_to_read_unread_pm") && $TSUE["TSUE_Member"]->info["unread_messages"] ) 
{
    show_error(get_phrase("force_to_read_unread_pm_notice"));
}

require_once(REALPATH . "/library/functions/functions_getTorrents.php");
if( $TSUE["action"] == "download" ) 
{
    $downloadTorrent = downloadTorrent($tid);
    if( $downloadTorrent ) 
    {
        show_error($downloadTorrent);
    }

}

if( in_array($TSUE["action"], array( "upload", "edit" )) ) 
{
    if( !has_permission("canupload_torrents") ) 
    {
        show_error(get_phrase("upload_no_permission"));
    }

    $TSUE["TSUE_Settings"]->loadSettings("hitrun_settings");
    $Page_Title = get_phrase(($TSUE["action"] == "upload" ? "navigation_upload_torrent" : "navigation_edit_torrent"));
    $uploadButtonPhrase = $TSUE["TSUE_Language"]->phrase["torrent_finish_upload"];
    $ifISeditTorrent = "";
    $AnnounceURL = "";
    $Torrent = array(  );
    $Torrent["tags"] = "";
    $Torrent["cid"] = $Torrent["tags"];
    $Torrent["description"] = $Torrent["cid"];
    $Torrent["name"] = $Torrent["description"];
    $Torrent["upload_multiplier"] = 1;
    $Torrent["download_multiplier"] = $Torrent["upload_multiplier"];
    $Torrent["record_stats"] = $Torrent["download_multiplier"];
    $Torrent["annonymouse"] = $Torrent["record_stats"];
    $Torrent["imdb"] = "";
    $Torrent["options"] = array( "anonymouse" => 1, "record_stats" => 1, "upload_multiplier" => 1, "download_multiplier" => 1, "imdb" => "", "nuked" => "", "hitRunRatio" => getSetting("hitrun_settings", "defaultRatio", 0) );
    $Torrent["gids"] = array(  );
    $Torrent["sticky"] = $stickyChecked = "";
    $stickyDisabled = (!has_permission("cansticky_torrents") ? " disabled=\"disabled\"" : "");
    $annonymouseChecked = $record_statsChecked = " checked=\"checked\"";
    $torrentPreviewImages = "";
    $Torrent["external"] = "";
    $externalChecked = "";
    $externalDisabled = ($TSUE["TSUE_Settings"]->settings["global_settings"]["announce_private_torrents_only"] ? " disabled=\"disabled\"" : "");
    if( $TSUE["action"] == "edit" ) 
    {
        $uploadButtonPhrase = $TSUE["TSUE_Language"]->phrase["button_save"];
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.*, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($tid));
        if( !$Torrent ) 
        {
            show_error(get_phrase("torrents_not_found"));
        }

        AddBreadcrumb(array( get_phrase("navigation_edit_torrent") . ": " . strip_tags($Torrent["name"]) => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;action=edit&amp;tid=" . $Torrent["tid"] ));
        if( !canEditTorrent($Torrent) || !hasViewPermission($Torrent["cviewpermissions"]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        $Torrent["name"] = strip_tags($Torrent["name"]);
        $Torrent["description"] = $TSUE["TSUE_Parser"]->clearTinymceP($Torrent["description"]);
        $Torrent["options"] = unserialize($Torrent["options"]);
        $Torrent["options"]["download_multiplier"] = 0 + $Torrent["options"]["download_multiplier"];
        $Torrent["options"]["upload_multiplier"] = 0 + $Torrent["options"]["upload_multiplier"];
        $Torrent["options"]["imdb"] = (isset($Torrent["options"]["imdb"]) && $Torrent["options"]["imdb"] ? strip_tags($Torrent["options"]["imdb"]) : "");
        $Torrent["options"]["hitRunRatio"] = (isset($Torrent["options"]["hitRunRatio"]) ? 0 + $Torrent["options"]["hitRunRatio"] : "");
        if( $Torrent["gids"] ) 
        {
            $Torrent["gids"] = explode("~", $Torrent["gids"]);
        }
        else
        {
            $Torrent["gids"] = array(  );
        }

        if( !$Torrent["options"]["anonymouse"] ) 
        {
            $annonymouseChecked = "";
        }

        if( !$Torrent["options"]["record_stats"] ) 
        {
            $record_statsChecked = "";
        }

        if( $Torrent["sticky"] ) 
        {
            $stickyChecked = " checked=\"checked\"";
        }

        if( $Torrent["external"] ) 
        {
            $externalChecked = " checked=\"checked\"";
        }

        $ifISeditTorrent = "<input type=\"hidden\" name=\"editTID\" id=\"editTID\" value=\"" . $Torrent["tid"] . "\" />";
        $torrentPreviewImages = get_torrent_images_for_edit($Torrent["tid"]);
        $isEdit = true;
    }
    else
    {
        $isEdit = false;
        AddBreadcrumb(array( get_phrase("navigation_upload_torrent") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;action=upload" ));
    }

    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["show_announce_url"] ) 
    {
        $AnnounceURL = get_phrase("your_announce_url", buildAnnounceURL());
    }

    eval("\$upload_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_javascript") . "\";");
    $TSUE["TSUE_Template"]->loadJavascripts(array( "fileuploader", "wizard" ));
    $multipliersDisabled = (!has_permission("canset_multipliers") ? " disabled=\"disabled\"" : "");
    $hitRunRatioDisabled = (!has_permission("canset_hitrun_ratio") ? " disabled=\"disabled\"" : "");
    $prepareTorrentCategoriesSelectbox = prepareTorrentCategoriesSelectbox(array( intval($Torrent["cid"]) ));
    if( $Torrent["cid"] ) 
    {
        $showAvailableGenres = showAvailableGenres($Torrent["gids"], $Torrent["cid"]);
    }
    else
    {
        $showAvailableGenres = get_phrase("to_see_genres");
    }

    $generateCustomUploadFields = generateCustomUploadFields($Torrent, $isEdit);
    $autoDescription = autoDescription(1);
    eval("\$torrentGenres = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_genres_one_step_upload") . "\";");
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_torrent_one_step") . "\";");
    PrintOutput($Output, $Page_Title);
}

$Alfabe = explode(" ", "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z 0-9");
$selectedAlfabe = (isset($_GET["a"]) ? trim($_GET["a"]) : "");
if( $selectedAlfabe && !in_array($selectedAlfabe, $Alfabe) ) 
{
    show_error(get_phrase("no_results_found"));
}

$whereConditions = $pagerConditions = array(  );
$orderBY = " ORDER BY t.sticky DESC, t.added DESC";
$SelectedSearchType["both"] = "";
$SelectedSearchType["uploader"] = $SelectedSearchType["both"];
$SelectedSearchType["description"] = $SelectedSearchType["uploader"];
$SelectedSearchType["name"] = $SelectedSearchType["description"];
$SelectedSortBy["added"] = " selected=\"selected\"";
$SelectedSortBy["times_completed"] = "";
$SelectedSortBy["size"] = $SelectedSortBy["times_completed"];
$SelectedSortBy["leechers"] = $SelectedSortBy["size"];
$SelectedSortBy["seeders"] = $SelectedSortBy["leechers"];
$SelectedOrderBy["desc"] = " selected=\"selected\"";
$SelectedOrderBy["asc"] = "";
$UseFunction = "getTorrents";
$queryFunction = "query";
$hideAnonymouseTorrents = false;
$scoreSQL = $orderSQL = "";
globalize(array( "post", "get" ), array( "keywords" => "DECODE", "search_type" => "TRIM", "cid" => "ARRAY", "sortOptions" => "ARRAY", "tag" => "TRIM", "genre" => "TRIM" ));
if( $keywords ) 
{
    $TSUE["action"] == "search";
    $pagerConditions[] = "keywords=" . html_clean($keywords);
    $pagerConditions[] = "search_type=" . html_clean($search_type);
    switch( $search_type ) 
    {
        case "name":
            $SelectedSearchType["name"] = " selected=\"selected\"";
            $explodeSearchKeywords = explodeSearchKeywords("t.name", $keywords);
            $scoreSQL = $explodeSearchKeywords . " AS Score,";
            $orderSQL = "ORDER BY Score DESC";
            $whereConditions[] = $explodeSearchKeywords;
            break;
        case "description":
            $SelectedSearchType["description"] = " selected=\"selected\"";
            $explodeSearchKeywords = explodeSearchKeywords("t.description", $keywords);
            $scoreSQL = $explodeSearchKeywords . " AS Score,";
            $orderSQL = "ORDER BY Score DESC";
            $whereConditions[] = $explodeSearchKeywords;
            break;
        case "both":
        case "default":
            $SelectedSearchType["both"] = " selected=\"selected\"";
            $explodeSearchKeywords = explodeSearchKeywords("t.name,t.description", $keywords);
            $scoreSQL = $explodeSearchKeywords . " AS Score,";
            $orderSQL = "ORDER BY Score DESC";
            $whereConditions[] = $explodeSearchKeywords;
            break;
        case "uploader":
            if( !is_valid_string($keywords) ) 
            {
                show_error(get_phrase("no_results_found"));
            }

            $SelectedSearchType["uploader"] = " selected=\"selected\"";
            $whereConditions[] = "m.membername=" . $TSUE["TSUE_Database"]->escape($keywords);
            $hideAnonymouseTorrents = true;
            break;
    }
}

if( $cid ) 
{
    if( !is_array($cid) && $cid ) 
    {
        $cid = tsue_explode(",", $cid);
    }

    if( !empty($cid) ) 
    {
        $categories = array(  );
        foreach( $cid as $categoryID ) 
        {
            $categories[] = intval($categoryID);
        }
        if( !empty($categories) ) 
        {
            $TSUE["action"] == "search";
            $implodeCats = implode(",", $categories);
            $whereConditions[] = "(c.cid IN (" . $implodeCats . ") OR c.pid IN (" . $implodeCats . "))";
            $pagerConditions[] = "cid=" . $implodeCats;
        }

    }

}
else
{
    if( !empty($TSUE["TSUE_Member"]->info["defaultTorrentCategories"]) && $TSUE["action"] != "details" ) 
    {
        $categories = array(  );
        foreach( $TSUE["TSUE_Member"]->info["defaultTorrentCategories"] as $categoryID ) 
        {
            $categories[] = intval($categoryID);
        }
        if( !empty($categories) ) 
        {
            $TSUE["action"] == "search";
            $implodeCats = implode(",", $categories);
            $whereConditions[] = "(c.cid IN (" . $implodeCats . ") OR c.pid IN (" . $implodeCats . "))";
            $pagerConditions[] = "cid=" . $implodeCats;
        }

    }

}

if( $selectedAlfabe ) 
{
    $pagerConditions[] = "a=" . html_clean($selectedAlfabe);
    if( $selectedAlfabe == "0-9" ) 
    {
        $whereConditions[] = "t.name REGEXP '^[0-9]'";
    }
    else
    {
        $whereConditions[] = "t.name LIKE '" . $TSUE["TSUE_Database"]->escape_no_quotes($selectedAlfabe) . "%'";
    }

    AddBreadcrumb(array( get_phrase("button_search") . ": " . $selectedAlfabe => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;a=" . $selectedAlfabe ));
}

if( $tag ) 
{
    $tag = trim(strip_tags($tag));
    $whereConditions[] = explodeSearchKeywords("t.tags", $tag);
    $pagerConditions[] = "tag=" . urlencode($tag);
}

if( $genre ) 
{
    $genre = trim(strip_tags($genre));
    $whereConditions[] = explodeSearchKeywords("t.gids", $genre);
    $pagerConditions[] = "genre=" . urlencode($genre);
}

if( isset($_POST["rss"]) && isset($categories) && !empty($categories) ) 
{
    if( is_member_of("unregistered") ) 
    {
        show_error(get_phrase("login_required"));
    }

    $RSSLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=rss&amp;categories=" . $implodeCats . "&amp;pk=" . $TSUE["TSUE_Member"]->info["passkey"];
    AddBreadcrumb(array( get_phrase("rss_for_selected_categories") => $RSSLink ));
    eval("\$rss_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("rss_link") . "\";");
    show_information(get_phrase("rss_link_for_selected_categories", $rss_link), get_phrase("rss_for_selected_categories"));
}

if( !empty($sortOptions) && is_array($sortOptions) && count($sortOptions) == 2 ) 
{
    $availableSortBY = array( "added", "seeders", "leechers", "size", "times_completed" );
    $availableOrderBy = array( "asc", "desc" );
    if( !empty($sortOptions["sortBy"]) && !empty($sortOptions["sortOrder"]) && in_array($sortOptions["sortOrder"], $availableOrderBy) && in_array($sortOptions["sortBy"], $availableSortBY) ) 
    {
        $SelectedSortBy[$sortOptions["sortBy"]] = " selected=\"selected\"";
        $SelectedOrderBy[$sortOptions["sortOrder"]] = " selected=\"selected\"";
        $orderBY = " ORDER BY t." . $sortOptions["sortBy"] . " " . strtoupper($sortOptions["sortOrder"]);
        $pagerConditions[] = "sortOptions[sortBy]=" . $sortOptions["sortBy"];
        $pagerConditions[] = "sortOptions[sortOrder]=" . $sortOptions["sortOrder"];
    }

}

if( $TSUE["action"] == "details" ) 
{
    if( !has_permission("canview_torrent_details") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    if( !$tid ) 
    {
        show_error(get_phrase("torrents_not_found"));
    }

    $whereConditions[] = "t.tid = " . $TSUE["TSUE_Database"]->escape($tid);
    $UseFunction = "getTorrent";
    $queryFunction = "query_result";
}

if( $TSUE["action"] == "new_torrents" ) 
{
    $whereConditions[] = "t.added >= " . getRecentTorrentsTimeout();
    $pagerConditions[] = "action=new_torrents";
    $Page_Title = get_phrase("navigation_torrents_whats_new");
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;action=new_torrents" ));
}

if( in_array($TSUE["action"], array( "todays_torrents", "torrents_of_this_week", "torrents_of_this_month" )) ) 
{
    switch( $TSUE["action"] ) 
    {
        case "todays_torrents":
            $timeOut = TIMENOW - 86400;
            break;
        case "torrents_of_this_week":
            $timeOut = TIMENOW - 604800;
            break;
        case "torrents_of_this_month":
            $timeOut = TIMENOW - 2629800;
    }
    $whereConditions[] = "t.added >= " . $timeOut;
    $pagerConditions[] = "action=" . $TSUE["action"];
    $Page_Title = get_phrase("find_torrents") . ": " . get_phrase($TSUE["action"]);
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;action=" . $TSUE["action"] . "&amp;pid=" . PAGEID ));
}

if( isset($_GET["type"]) && $_GET["type"] == "bookmarks" ) 
{
    $pagerConditions[] = "type=bookmarks";
    $whereConditions[] = "b.memberid = " . $TSUE["TSUE_Member"]->info["memberid"];
    AddBreadcrumb(array( get_phrase("bookmarked_torrents") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;type=bookmarks" ));
}

if( !empty($whereConditions) ) 
{
    $whereConditions = "WHERE " . ((!has_permission("can_moderate_torrents") ? "awaitingModeration = 0 AND " : "")) . implode(" AND ", $whereConditions);
}
else
{
    $whereConditions = (!has_permission("can_moderate_torrents") ? "WHERE awaitingModeration = 0" : "");
}

$TorrentsCountQuery = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE COUNT(t.tid) as totalCounts\r\nFROM tsue_torrents t" . ((!$whereConditions ? " USE INDEX(PRIMARY) " : "")) . "\r\nLEFT JOIN tsue_members m on (t.owner=m.memberid) \r\nLEFT JOIN tsue_torrents_categories c USING(cid) \r\nLEFT JOIN tsue_bookmarks b ON (t.tid=b.tid&&b.memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ") \r\n" . $whereConditions, true);
$totalCounts = $TorrentsCountQuery["totalCounts"];
if( $totalCounts <= 0 ) 
{
    show_error(get_phrase("no_results_found"));
}

if( getSetting("happy_hours", "active") ) 
{
    $happy_hours_start_date = getSetting("happy_hours", "start_date");
    $happy_hours_end_date = getSetting("happy_hours", "end_date");
    if( $happy_hours_start_date <= TIMENOW && TIMENOW <= $happy_hours_end_date ) 
    {
        $happy_hours_freeleech = getSetting("happy_hours", "freeleech");
        $happy_hours_double_upload = getSetting("happy_hours", "doubleupload");
        $start_date = convert_time($happy_hours_start_date, "d-m-Y");
        $end_date = convert_time($happy_hours_end_date, "d-m-Y");
        if( $happy_hours_freeleech && $happy_hours_double_upload ) 
        {
            define("HAPPY_HOURS_FREELEECH", true);
            define("HAPPY_HOURS_DOUBLEUPLOAD", true);
            $happyHoursPhrase = "happy_hours_info_3";
        }
        else
        {
            if( $happy_hours_double_upload ) 
            {
                define("HAPPY_HOURS_DOUBLEUPLOAD", true);
                $happyHoursPhrase = "happy_hours_info_2";
            }
            else
            {
                if( $happy_hours_freeleech ) 
                {
                    define("HAPPY_HOURS_FREELEECH", true);
                    $happyHoursPhrase = "happy_hours_info_1";
                }

            }

        }

    }

}

$Pagination = Pagination($totalCounts, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_torrents_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=" . PAGEID . "&" . ((!empty($pagerConditions) ? implode("&", $pagerConditions) . "&" : "")));
$TorrentsQuery = $TSUE["TSUE_Database"]->$queryFunction("SELECT SQL_NO_CACHE " . $scoreSQL . "t.*, m.membername, g.groupstyle, c.cname, c.cviewpermissions, c.cdownloadpermissions, cc.cid as parentCategoryID, cc.cname as parentCategoryName, a.filename, aa.filename as torrentFilename, i.content as IMDBContent, b.tid as bookmark \r\nFROM tsue_torrents t \r\nLEFT JOIN tsue_members m on (t.owner=m.memberid) \r\nLEFT JOIN tsue_membergroups g USING(membergroupid) \r\nLEFT JOIN tsue_torrents_categories c ON(t.cid=c.cid) \r\nLEFT JOIN tsue_torrents_categories cc ON(c.pid=cc.cid) \r\nLEFT JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) \r\nLEFT JOIN tsue_attachments aa ON (aa.content_type='torrent_files' AND aa.content_id=t.tid) \r\nLEFT JOIN tsue_imdb i USING(tid) \r\nLEFT JOIN tsue_bookmarks b ON (t.tid=b.tid&&b.memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ") \r\n" . $whereConditions . " \r\nGROUP BY t.tid " . (($orderSQL ? $orderSQL . str_replace(" ORDER BY ", ",", $orderBY) : $orderBY)) . " " . $Pagination["0"]);
$Torrents = $UseFunction($TorrentsQuery);
if( $UseFunction == "getTorrents" ) 
{
function Alfabe($selectedAlfabe, $Alfabe)
{
    global $TSUE;
    $Pagination = "";
    foreach( $Alfabe as $a ) 
    {
        $extraClass = ($selectedAlfabe == $a ? " selectedAlfabe" : "");
        $Pagination .= "\r\n\t\t\t<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;a=" . $a . "\"><div class=\"alfabe" . $extraClass . "\">" . $a . "</div></a>";
    }
    return $Pagination;
}

    $Alfabe = Alfabe($selectedAlfabe, $Alfabe);
    if( getSetting("global_settings", "torrent_catz_always_visible") ) 
    {
        $show_categories_ajax_button = "";
        $class = "";
        $torrentCategories = prepareTorrentCategoriesCheckbox($TSUE["TSUE_Member"]->info["defaultTorrentCategories"], true);
    }
    else
    {
        eval("\$show_categories_ajax_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("show_categories_ajax_button") . "\";");
        $class = "hidden";
        $torrentCategories = "";
    }

    eval("\$torrentSearchBox = \"" . $TSUE["TSUE_Template"]->LoadTemplate("torrent_search") . "\";");
}
else
{
    $show_categories_ajax_button = "";
    $torrentSearchBox = "";
    $Page_Title = strip_tags($TorrentsQuery["name"]);
    if( $TorrentsQuery["parentCategoryName"] ) 
    {
        AddBreadcrumb(array( $TorrentsQuery["parentCategoryName"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;cid=" . $TorrentsQuery["parentCategoryID"] ));
    }

    AddBreadcrumb(array( $TorrentsQuery["cname"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;cid=" . $TorrentsQuery["cid"], $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=" . PAGEID . "&amp;action=details&amp;tid=" . $tid ));
}

eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate(($TSUE["TSUE_Member"]->info["torrentStyle"] == 1 || $UseFunction == "getTorrent" ? "torrents" : "torrents_table_classic")) . "\";");
if( isset($happyHoursPhrase) ) 
{
    $Output = show_information(get_phrase($happyHoursPhrase, $start_date, $end_date), get_phrase("happy_hours"), false) . $Output;
}

PrintOutput($Output, $Page_Title);


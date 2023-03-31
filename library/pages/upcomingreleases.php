<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "upcomingreleases.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "upcomingreleases" ));
$Page_Title = get_phrase("upcoming_releases");
$Output = "";
$WHERE = "";
$pageID = PAGEID;
globalize(array( "post", "get" ), array( "keywords" => "TRIM" ));
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&amp;pid=" . PAGEID ));
require(REALPATH . "library/functions/functions_getupComingReleases.php");
$add_a_release = "";
if( !is_member_of("unregistered") && has_permission("canupload_torrents") ) 
{
    eval("\$add_a_release = \"" . $TSUE["TSUE_Template"]->LoadTemplate("add_a_release") . "\";");
}

eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("upcoming_releases_search_form") . "\";");
if( $keywords ) 
{
    $WHERE = " WHERE " . explodeSearchKeywords("r.title", $keywords);
    AddBreadcrumb(array( get_phrase("search_upcoming_releases") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&amp;pid=" . PAGEID . "&amp;keywords=" . urlencode($keywords) ));
}

$upcomingReleasesCount = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE r.rid FROM tsue_upcoming_releases r" . $WHERE);
if( $upcomingReleasesCount ) 
{
    $Pagination = Pagination($upcomingReleasesCount, getSetting("global_settings", "upcoming_releases_perpage", 10), $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&pid=" . PAGEID . (($keywords ? "&keywords=" . urlencode($keywords) : "")) . "&");
    $upcomingReleases = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE r.*, m.membername, m.gender, g.groupstyle FROM tsue_upcoming_releases r LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid)" . $WHERE . " ORDER BY r.added DESC " . $Pagination["0"]);
    if( $TSUE["TSUE_Database"]->num_rows($upcomingReleases) ) 
    {
        while( $upcomingRelease = $TSUE["TSUE_Database"]->fetch_assoc($upcomingReleases) ) 
        {
            $Output .= prepareUpComingRelease($upcomingRelease, $pageID);
        }
        $Output .= $Pagination["1"];
    }

}
else
{
    $Output .= show_error(get_phrase("message_nothing_found"), "", false);
}

PrintOutput($Output, $Page_Title);


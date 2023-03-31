<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "request.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "request" ));
$Page_Title = get_phrase("request_a_torrent");
$Output = "";
$WHERE = "";
globalize(array( "post", "get" ), array( "keywords" => "TRIM" ));
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=" . PAGEID ));
require(REALPATH . "library/functions/functions_getRequests.php");
$request_torrent_button = "";
if( !is_member_of("unregistered") && has_permission("canrequest_torrent") ) 
{
    eval("\$request_torrent_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("request_torrent_button") . "\";");
}

eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("search_request_form") . "\";");
if( $keywords ) 
{
    $WHERE = " WHERE " . explodeSearchKeywords("r.title", $keywords);
    AddBreadcrumb(array( get_phrase("search_request") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=" . PAGEID . "&amp;keywords=" . urlencode($keywords) ));
}

$requestCount = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE r.rid FROM tsue_requests r" . $WHERE);
if( $requestCount ) 
{
    if( 0 < getSetting("global_settings", "days_retain_posted_request") ) 
    {
        $Output = show_information(get_phrase("unfilled_requests_deletion_notice_x", getSetting("global_settings", "days_retain_posted_request")), "", false) . $Output;
    }

    $Pagination = Pagination($requestCount, $TSUE["TSUE_Settings"]->settings["global_settings"]["requests_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&pid=" . PAGEID . (($keywords ? "&keywords=" . urlencode($keywords) : "")) . "&");
    $Requests = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE r.*, c.cname, m.membername, m.gender, g.groupstyle, mm.membername AS fmembername, mm.gender AS fgender, gg.groupstyle AS fgroupstyle \r\n\tFROM tsue_requests r \r\n\tLEFT JOIN tsue_torrents_categories c USING(cid)\r\n\tLEFT JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid)\r\n\tLEFT JOIN tsue_members mm ON(mm.memberid=r.filled_by) \r\n\tLEFT JOIN tsue_membergroups gg ON(gg.membergroupid=mm.membergroupid)\r\n\t" . $WHERE . " ORDER BY r.added DESC " . $Pagination["0"]);
    if( $TSUE["TSUE_Database"]->num_rows($Requests) ) 
    {
        while( $Request = $TSUE["TSUE_Database"]->fetch_assoc($Requests) ) 
        {
            $Output .= prepareRequest($Request);
        }
        $Output .= $Pagination["1"];
    }

}
else
{
    $Output .= show_error(get_phrase("message_nothing_found"), "", false);
}

PrintOutput($Output, $Page_Title);


<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "online.php");
require("./library/init/init.php");
globalize("get", array( "last24" => "INT" ));
$Page_Title = get_phrase(($last24 ? "last24_members" : "online_title"));
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=online&amp;pid=" . PAGEID . (($last24 ? "&amp;last24=1" : "")) ));
$DateCut = TIMENOW - (($last24 ? "86400" : $TSUE["TSUE_Settings"]->settings["global_settings"]["website_timeout"] * 60));
$OnlineMembersCountQuery = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_session WHERE date > " . $DateCut);
$Pagination = Pagination($TSUE["TSUE_Database"]->num_rows($OnlineMembersCountQuery), $TSUE["TSUE_Settings"]->settings["global_settings"]["website_members_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=online&amp;pid=17&amp;" . (($last24 ? "last24=1&amp;" : "")));
$OnlineMembersQuery = $TSUE["TSUE_Database"]->query("SELECT s.*, m.membername, m.gender, m.visible, g.groupstyle FROM tsue_session s LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE s.date > " . $DateCut . " ORDER BY s.date DESC, m.membername ASC " . $Pagination["0"]);
$onlineMembers = "";
while( $OM = $TSUE["TSUE_Database"]->fetch_assoc($OnlineMembersQuery) ) 
{
    if( !$OM["visible"] && !has_permission("canview_invisible_members") && $OM["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] || !$OM["memberid"] || !$OM["membername"] ) 
    {
        $_membername = get_phrase("guest");
        $_memberid = 0;
    }
    else
    {
        $_membername = getMembername($OM["membername"], $OM["groupstyle"]);
        $_memberid = $OM["memberid"];
    }

    $_date = convert_relative_time($OM["date"]);
    $_avatar = get_member_avatar($_memberid, $OM["gender"], "s");
    if( $_memberid ) 
    {
        eval("\$membername = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    }
    else
    {
        $membername = $_membername;
    }

    $_ip = $_browser = "";
    if( has_permission("canview_special_details") ) 
    {
        $_ip = $OM["ipaddress"];
        $_browser = $OM["browser"];
    }

    $_viewing = get_phrase("online_viewing", translate_location($OM["location"], $OM["http_referer"], $OM["query_string"]));
    $_alt = "";
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    eval("\$onlineMembers .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("online_members") . "\";");
}
eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("online") . "\";");
PrintOutput($Output, $Page_Title);


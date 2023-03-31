<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "staffmessages.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "comments", "staffmessages" ));
$TSUE["TSUE_Template"]->loadJSPhrase(array( "button_delete", "confirm_mass_delete_staff_messages" ));
$Page_Title = get_phrase("navigation_staff_messages");
$Output = $Comments = "";
if( !has_permission("canreply_staff_messages") ) 
{
    show_error(get_phrase("permission_denied"));
}

AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=staffmessages&amp;pid=" . PAGEID ));
globalize("get", array( "mid" => "INT" ));
if( $mid ) 
{
    $CountQuery = true;
    $Pagination["1"] = "";
    $Pagination["0"] = $Pagination["1"];
    $Messages = $TSUE["TSUE_Database"]->query("SELECT sm.*, m.membername, g.groupstyle FROM tsue_staff_messages sm LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE sm.mid=" . $TSUE["TSUE_Database"]->escape($mid));
}
else
{
    $CountQuery = $TSUE["TSUE_Database"]->row_count("SELECT mid FROM tsue_staff_messages");
    $Pagination = Pagination($CountQuery, 10, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=staffmessages&amp;pid=" . PAGEID . "&amp;");
    $Messages = $TSUE["TSUE_Database"]->query("SELECT sm.*, m.membername, g.groupstyle FROM tsue_staff_messages sm LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) ORDER BY sm.added DESC " . $Pagination["0"]);
}

if( !$TSUE["TSUE_Database"]->num_rows($Messages) ) 
{
    show_error(get_phrase("message_nothing_found"));
}

$Count = 0;
$staff_messages_rows = "";
for( $candelete_staff_messages = has_permission("candelete_staff_messages"); $Message = $TSUE["TSUE_Database"]->fetch_assoc($Messages); $Count++ ) 
{
    $checkboxes = "";
    if( $candelete_staff_messages ) 
    {
        $checkboxes = "<input type=\"checkbox\" name=\"mid[]\" value=\"" . $Message["mid"] . "\" class=\"middle\" />";
    }

    $_memberid = $Message["memberid"];
    $_membername = getMembername($Message["membername"], $Message["groupstyle"]);
    eval("\$Message['membername'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    $orj_message = $Message["message"];
    $Message["added"] = convert_relative_time($Message["added"]);
    $Message["message"] = substr(strip_tags($Message["message"]), 0, 100) . "...";
    $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
    $status = get_phrase(($Message["rid"] ? "replied" : "awaiting_reply"));
    eval("\$staff_messages_rows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_messages_rows") . "\";");
}
if( $mid ) 
{
    $Message["safe_message"] = nl2br(strip_tags($orj_message));
    eval("\$Comments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_messages_full_message") . "\";");
    require_once(REALPATH . "/library/functions/functions_getComments.php");
    $Comments .= getComments("staff_messages_comments", $mid);
}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_messages_table") . "\";");
PrintOutput($Output, $Page_Title);


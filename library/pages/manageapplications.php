<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "manageapplications.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "comments", "manageapplications" ));
$TSUE["TSUE_Template"]->loadJSPhrase(array( "button_delete", "confirm_mass_delete_applications" ));
$Page_Title = get_phrase("manage_applications");
$Output = "";
$listApplications = "";
$applicationMessages = "";
$applicationComments = "";
$Pagination["1"] = "";
$Pagination["0"] = $Pagination["1"];
$applicationStateChecked = array( "pending" => "", "approved" => "", "rejected" => "" );
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=manageapplications&amp;pid=" . PAGEID ));
if( !has_permission("canmanage_applications") ) 
{
    show_error(get_phrase("permission_denied"));
}

globalize("get", array( "memberid" => "INT" ));
if( $memberid && strtolower($_SERVER["REQUEST_METHOD"]) == "post" && isset($_POST["application_state"]) && in_array($_POST["application_state"], array( "pending", "approved", "rejected" )) ) 
{
    $TSUE["TSUE_Database"]->update("tsue_uploader_applications", array( "application_state" => $_POST["application_state"], "last_modified_date" => TIMENOW, "last_modified_memberid" => $TSUE["TSUE_Member"]->info["memberid"] ), "memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
    if( $memberid != $TSUE["TSUE_Member"]->info["memberid"] ) 
    {
        switch( $_POST["application_state"] ) 
        {
            case "pending":
                $content_id = 1;
                break;
            case "approved":
                $content_id = 2;
                break;
            case "rejected":
                $content_id = 3;
        }
        alert_member($memberid, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "applications", $content_id, "state-updated");
    }

}

require_once(REALPATH . "/library/functions/functions_getApplications.php");
if( $memberid ) 
{
    $Application = $TSUE["TSUE_Database"]->query_result("SELECT a.*, m.membername as application_by_membername, g.groupstyle as application_by_groupstyle, mm.membername as last_modified_membername, gg.groupstyle as last_modified_groupstyle FROM tsue_uploader_applications a LEFT JOIN tsue_members m ON (a.memberid=m.memberid) LEFT JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) LEFT JOIN tsue_members mm ON (a.last_modified_memberid=mm.memberid) LEFT JOIN tsue_membergroups gg ON (mm.membergroupid=gg.membergroupid) WHERE a.memberid=" . $TSUE["TSUE_Database"]->escape($memberid));
    if( !$Application ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    $applicationStateChecked[$Application["application_state"]] = " checked=\"checked\"";
    $computer_running_all_the_time = get_phrase("uploader_application_q1a" . (($Application["computer_running_all_the_time"] ? $Application["computer_running_all_the_time"] : 2)));
    $seedbox = get_phrase("uploader_application_q2a" . (($Application["seedbox"] ? $Application["seedbox"] : 2)));
    $speedtest = strip_tags($Application["speedtest"]);
    $stuff = nl2br(strip_tags($Application["stuff"]));
    eval("\$listMessages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("applications_messages_rows") . "\";");
    eval("\$applicationMessages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("applications_messages_table") . "\";");
    require_once(REALPATH . "/library/functions/functions_getComments.php");
    $Comments = getComments("application_comments", $memberid);
    eval("\$applicationComments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("application_comments") . "\";");
    $listApplications = getApplications($Application, 0);
}
else
{
    $ApplicationsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT memberid FROM tsue_uploader_applications");
    $Pagination = Pagination($ApplicationsCountQuery, 10, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=manageapplications&amp;pid=" . PAGEID . "&amp;");
    if( $ApplicationsCountQuery ) 
    {
        $Applications = $TSUE["TSUE_Database"]->query("SELECT a.*, m.membername as application_by_membername, g.groupstyle as application_by_groupstyle, mm.membername as last_modified_membername, gg.groupstyle as last_modified_groupstyle FROM tsue_uploader_applications a LEFT JOIN tsue_members m ON (a.memberid=m.memberid) LEFT JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) LEFT JOIN tsue_members mm ON (a.last_modified_memberid=mm.memberid) LEFT JOIN tsue_membergroups gg ON (mm.membergroupid=gg.membergroupid) ORDER BY added DESC " . $Pagination["0"]);
        $Count = 0;
        for( $candelete_applications = has_permission("candelete_applications"); $Application = $TSUE["TSUE_Database"]->fetch_assoc($Applications); $Count++ ) 
        {
            $checkboxes = "";
            if( $candelete_applications ) 
            {
                $checkboxes = "<input type=\"checkbox\" name=\"mid[]\" value=\"" . $Application["memberid"] . "\" class=\"middle\" />";
            }

            $listApplications .= getApplications($Application, $Count, $checkboxes);
        }
    }

}

if( !isset($Pagination["0"]) ) 
{
    $Pagination["0"] = "";
}

if( !isset($Pagination["1"]) ) 
{
    $Pagination["1"] = "";
}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("applications_list_applications_table") . "\";");
PrintOutput($Output, $Page_Title);


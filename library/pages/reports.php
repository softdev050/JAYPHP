<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "reports.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("comments");
$Page_Title = get_phrase("navigation_reports");
$Output = "";
$listReports = "";
$reportMessages = "";
$reportComments = "";
$Pagination["1"] = "";
$Pagination["0"] = $Pagination["1"];
$reportStateChecked = array( "open" => "", "resolved" => "", "rejected" => "" );
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=reports&amp;pid=" . PAGEID ));
if( !has_permission("canmanage_reports") ) 
{
    show_error(get_phrase("permission_denied"));
}

globalize("get", array( "report_id" => "INT" ));
require_once(REALPATH . "/library/functions/functions_getReports.php");
if( $report_id ) 
{
    $Report = $TSUE["TSUE_Database"]->query_result("SELECT r.*, m.membername as reported_by_membername, g.groupstyle as reported_by_groupstyle, mm.membername as last_modified_membername, gg.groupstyle as last_modified_groupstyle FROM tsue_reports r LEFT JOIN tsue_members m ON (r.reported_by_memberid=m.memberid) LEFT JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) LEFT JOIN tsue_members mm ON (r.last_modified_memberid=mm.memberid) LEFT JOIN tsue_membergroups gg ON (mm.membergroupid=gg.membergroupid) WHERE report_id=" . $TSUE["TSUE_Database"]->escape($report_id));
    if( !$Report ) 
    {
        show_error(get_phrase("message_content_error"));
    }

    if( strtolower($_SERVER["REQUEST_METHOD"]) == "post" && isset($_POST["report_state"]) && in_array($_POST["report_state"], array( "open", "resolved", "rejected" )) ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_reports", array( "report_state" => $_POST["report_state"], "last_modified_date" => TIMENOW, "last_modified_memberid" => $TSUE["TSUE_Member"]->info["memberid"] ), "report_id=" . $TSUE["TSUE_Database"]->escape($report_id));
        $Report["report_state"] = $_POST["report_state"];
        if( isset($_POST["message"]) ) 
        {
            $message = trim($_POST["message"]);
            if( $message ) 
            {
                sendPM(get_phrase("your_report_status_has_been_updated"), $TSUE["TSUE_Member"]->info["memberid"], $Report["reported_by_memberid"], $message, false);
                $PMSent = true;
            }

        }

        if( !isset($PMSent) ) 
        {
            $gotoLink = buildReportContentURL($Report);
            $report_state = get_phrase("report_state_" . $Report["report_state"]);
            $content_type = buildReportName($Report);
            $Output .= show_information(get_phrase("message_saved"), false, false);
            $reportDetails1 = "<div><b>" . get_phrase("report_content_type") . ":</b><br /><a href=\"" . getSetting("global_settings", "website_url") . "/" . $gotoLink . "\" target=\"_blank\">" . $content_type . "</a></div>\r\n\t\t\t<div><b>" . get_phrase("report_state") . ":</b><br />" . $report_state . "</div>";
            $reportDetails2 = "<div><b>" . get_phrase("report_reported_by") . "/" . get_phrase("receiver") . ":</b><br />" . $Report["reported_by_membername"] . "</div>\r\n\t\t\t<div><b>" . get_phrase("messages_subject") . ":</b><br />" . get_phrase("your_report_status_has_been_updated") . "</div>\r\n\t\t\t<div><b>" . get_phrase("messages_message") . ":</b>";
            $Output .= "\r\n\t\t\t<div class=\"comment-box\">\r\n\t\t\t\t<form method=\"post\">\r\n\t\t\t\t\t<input type=\"hidden\" name=\"report_state\" value=\"" . $Report["report_state"] . "\" />\r\n\t\t\t\t\t" . $reportDetails1 . "\r\n\t\t\t\t\t" . $reportDetails2 . "\r\n\t\t\t\t\t<textarea name=\"message\" id=\"tinymce_autoload\" class=\"tinymce\"><br />----------------------------------------------------------------------------<br />" . $reportDetails1 . "----------------------------------------------------------------------------</textarea>\r\n\t\t\t\t</div>\r\n\t\t\t\t\t<div><input type=\"submit\" value=\"" . get_phrase("messages_send_message") . "\" class=\"submit\" /></div>\r\n\t\t\t\t</form>\r\n\t\t\t</div>";
            PrintOutput($Output, $Page_Title);
        }
        else
        {
            $Output .= show_information(get_phrase("message_posted"), false, false);
        }

    }

    $reportStateChecked[$Report["report_state"]] = " checked=\"checked\"";
    $listMessages = "";
    $Messages = $TSUE["TSUE_Database"]->query("SELECT c.*, m.membername, g.groupstyle FROM tsue_reports_comments c LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE c.report_id=" . $TSUE["TSUE_Database"]->escape($report_id) . " ORDER BY c.comment_date DESC");
    if( $TSUE["TSUE_Database"]->num_rows($Messages) ) 
    {
        while( $Message = $TSUE["TSUE_Database"]->fetch_assoc($Messages) ) 
        {
            $_memberid = $Message["memberid"];
            $_membername = getMembername($Message["membername"], $Message["groupstyle"]);
            eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            $messageDate = convert_relative_time($Message["comment_date"]);
            $reportMessage = strip_tags($Message["message"]);
            $gotoLink = buildReportContentURL($Report);
            $gotoContent = "<a href=\"" . getSetting("global_settings", "website_url") . "/" . $gotoLink . "\" target=\"_blank\" class=\"submit\">" . get_phrase("report_go_to_content") . "</a>";
            eval("\$listMessages .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("reports_messages_rows") . "\";");
        }
    }

    eval("\$reportMessages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("reports_messages_table") . "\";");
    require_once(REALPATH . "/library/functions/functions_getComments.php");
    $Comments = getComments("report_comments", $report_id);
    eval("\$reportComments = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_comments") . "\";");
    $listReports = getReports($Report, 0);
}
else
{
    $ReportsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT report_id FROM tsue_reports");
    $Pagination = Pagination($ReportsCountQuery, 10, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=reports&amp;pid=" . PAGEID . "&amp;");
    if( $ReportsCountQuery ) 
    {
        $Reports = $TSUE["TSUE_Database"]->query("SELECT r.*, m.membername as reported_by_membername, g.groupstyle as reported_by_groupstyle, mm.membername as last_modified_membername, gg.groupstyle as last_modified_groupstyle FROM tsue_reports r LEFT JOIN tsue_members m ON (r.reported_by_memberid=m.memberid) LEFT JOIN tsue_membergroups g ON (m.membergroupid=g.membergroupid) LEFT JOIN tsue_members mm ON (r.last_modified_memberid=mm.memberid) LEFT JOIN tsue_membergroups gg ON (mm.membergroupid=gg.membergroupid) ORDER BY first_report_date DESC " . $Pagination["0"]);
        for( $Count = 0; $Report = $TSUE["TSUE_Database"]->fetch_assoc($Reports); $Count++ ) 
        {
            $listReports .= getReports($Report, $Count);
        }
    }

}

eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("reports_list_reports_table") . "\";");
PrintOutput($Output, $Page_Title);


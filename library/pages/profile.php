<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "profile.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "comments", "profile" ));
globalize("get", array( "memberid" => "INT" ));
if( !$memberid && $TSUE["TSUE_Member"]->info["memberid"] ) 
{
    $memberid = $TSUE["TSUE_Member"]->info["memberid"];
}

if( !$memberid ) 
{
    show_error(get_phrase("member_not_found"));
}

if( !has_permission("canview_member_profiles") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
{
    show_error(get_phrase("permission_denied"));
}

require_once(REALPATH . "/library/classes/class_awards.php");
$TSUE_Awards = new TSUE_Awards($memberid);
$memberAwards = $TSUE_Awards->getMemberAwards($memberid);
require_once(REALPATH . "library/functions/functions_memberInfo.php");
$MemberInfo = memberInfo($memberid, true, "no", "m", $memberAwards);
if( !is_array($MemberInfo) ) 
{
    show_error($MemberInfo);
}

$Member = $MemberInfo["1"];
$Page_Title = get_phrase("memberinfo_title", $Member["membername"]);
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&amp;pid=" . PAGEID . "&amp;memberid=" . $Member["memberid"] ));
if( $MemberInfo["2"] ) 
{
    $Output = $MemberInfo["0"];
}
else
{
    require_once(REALPATH . "/library/functions/functions_getComments.php");
    $Comments = getComments("profile_comments", $Member["memberid"], (isset($_GET["comment_id"]) ? intval($_GET["comment_id"]) : 0));
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("profile") . "\";");
}

PrintOutput($Output, $Page_Title);


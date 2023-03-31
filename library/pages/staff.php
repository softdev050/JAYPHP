<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "staff.php");
require("./library/init/init.php");
$Page_Title = get_phrase("navigation_staff");
$staff_member_avatars = "";
$Output = "";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_staff") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=staff&amp;pid=" . PAGEID ));
$staffCache = array(  );
$staffQuery = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membername, m.email, m.joindate, m.lastactivity, m.gender, m.visible, profile.custom_title, profile.country, p.allow_view_profile, p.show_your_age, g.groupname, g.groupstyle FROM tsue_members m INNER JOIN tsue_member_profile profile USING(memberid) LEFT JOIN tsue_member_privacy p USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE g.showOnStaff = 1 ORDER BY g.sort ASC, m.lastactivity DESC");
while( $staffMembers = $TSUE["TSUE_Database"]->fetch_assoc($staffQuery) ) 
{
    $staffCache[$staffMembers["groupname"]][] = $staffMembers;
}
foreach( $staffCache as $groupname => $Staffs ) 
{
    $staff_member_avatars = "";
    foreach( $Staffs as $Staff ) 
    {
        $imageText = html_clean($Staff["membername"] . " - " . get_phrase("memberinfo_lastactivity", convert_relative_time($Staff["lastactivity"], false)));
        $staffName = getMembername($Staff["membername"], $Staff["groupstyle"]);
        if( !$Staff["visible"] && !has_permission("canview_invisible_members") && $Staff["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            $imageText = strip_tags($Staff["membername"]);
        }

        $_memberid = $Staff["memberid"];
        $_avatar = get_member_avatar($Staff["memberid"], $Staff["gender"], "m");
        eval("\$staff_member_avatars .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_member_avatars") . "\";");
    }
    eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_members") . "\";");
}
PrintOutput($Output, $Page_Title);


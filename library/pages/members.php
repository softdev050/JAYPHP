<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "members.php");
require("./library/init/init.php");
$Page_Title = get_phrase("members_title");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_members") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=members&amp;pid=" . PAGEID ));
require_once(REALPATH . "/library/functions/functions_memberInfo.php");
$WHERE = "";
$Members = "";
$Pagination["1"] = "";
$queryLink = "";
if( $TSUE["do"] == "search" ) 
{
    globalize(array( "post", "get" ), array( "membername" => "DECODE" ));
    if( $membername ) 
    {
        $queryLink = "&amp;do=search&amp;membername=" . html_clean($membername);
        AddBreadcrumb(array( get_phrase("forums_search_results") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=members&amp;pid=" . PAGEID . $queryLink ));
        $WHERE = " WHERE " . explodeSearchKeywords("m.membername", $membername, true);
    }

}

$MembersCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT m.memberid FROM tsue_members m" . $WHERE, true);
if( $MembersCountQuery ) 
{
    $Pagination = Pagination($MembersCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_members_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=members&amp;pid=" . PAGEID . "&amp;" . (($queryLink ? $queryLink . "&amp;" : "")));
    $MembersQuery = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membername, m.email, m.joindate, m.lastactivity, m.ipaddress, m.gender, m.visible, m.accountParked, profile.date_of_birth, profile.custom_title, profile.uploaded, profile.downloaded, profile.country, profile.signature, p.allow_view_profile, p.show_your_age, profile.points, profile.muted, g.groupname, g.groupstyle, s.location as lastViewedPage, s.http_referer, s.query_string, b.memberid as isBanned, w.memberid as isWarned, i.sender_memberid as invitedByID, mm.membername as invitedByMembername, gg.groupstyle as invitedByGroupstyle \r\n\tFROM tsue_members m \r\n\tINNER JOIN tsue_member_profile profile USING(memberid) \r\n\tINNER JOIN tsue_member_privacy p USING(memberid) \r\n\tINNER JOIN tsue_membergroups g USING(membergroupid) \r\n\tLEFT JOIN tsue_session s USING(memberid) \r\n\tLEFT JOIN tsue_member_bans b USING(memberid) \r\n\tLEFT JOIN tsue_member_warns w USING(memberid) \r\n\tLEFT JOIN tsue_invites i ON(m.memberid=i.receiver_memberid && i.status = 'completed') \r\n\tLEFT JOIN tsue_members mm ON(i.sender_memberid=mm.memberid) \r\n\tLEFT JOIN tsue_membergroups gg ON(mm.membergroupid=gg.membergroupid)\r\n\t" . $WHERE . " \r\n\tORDER BY m.membername ASC " . $Pagination["0"]);
    $queryCache = $memberids = array(  );
    while( $OM = $TSUE["TSUE_Database"]->fetch_assoc($MembersQuery) ) 
    {
        $queryCache[] = $OM;
        $memberids[] = $OM["memberid"];
    }
    require_once(REALPATH . "/library/classes/class_awards.php");
    $TSUE_Awards = new TSUE_Awards($memberids);
    unset($memberids);
    foreach( $queryCache as $OM ) 
    {
        $OM["lastViewedPage"] = "";
        $OM["http_referer"] = "";
        $OM["query_string"] = "";
        $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
        $PassiveUser = array( "memberid" => $OM["memberid"], "allow_view_profile" => $OM["allow_view_profile"] );
        $memberAwards = $TSUE_Awards->getMemberAwards($OM["memberid"]);
        $Info = prepareInfo($OM["memberid"], $OM, "m", false, "no", $memberAwards, !canViewProfile($ActiveUser, $PassiveUser));
        $Members .= $Info["0"];
    }
    unset($queryCache);
}
else
{
    $Members = show_error(get_phrase("message_nothing_found"), NULL, false);
}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("members") . "\";");
PrintOutput($Output, $Page_Title);


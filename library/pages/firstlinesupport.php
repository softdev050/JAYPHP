<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "firstlinesupport.php");
require("./library/init/init.php");
$Page_Title = get_phrase("first_line_support");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("first_line_support") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=firstlinesupport&amp;pid=" . PAGEID ));
$supportTeams = $TSUE["TSUE_Database"]->query("SELECT sid, title, description, members FROM tsue_first_line_support WHERE active = 1 ORDER BY title ASC");
if( !$TSUE["TSUE_Database"]->num_rows($supportTeams) ) 
{
    show_error(get_phrase("message_nothing_found"));
}

$FLSCache = array(  );
while( $FLS = $TSUE["TSUE_Database"]->fetch_assoc($supportTeams) ) 
{
    if( $FLS["members"] ) 
    {
        $WHERE = "membername IN (" . implode(",", array_map(array( $TSUE["TSUE_Database"], "escape" ), explode(",", trim(preg_replace("/\\s+/", "", $FLS["members"]))))) . ")";
        $Members = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membername, m.lastactivity, m.gender, m.visible, p.country, p.custom_title, g.groupname, g.groupstyle FROM tsue_members m INNER JOIN tsue_member_profile p USING(memberid) INNER JOIN tsue_membergroups g USING(membergroupid) WHERE " . $WHERE . " ORDER BY lastactivity DESC");
        if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
        {
            $memberCache = array(  );
            while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
            {
                $memberCache[] = $Member;
            }
            unset($FLS["members"]);
            $FLSCache[] = array( "Team" => $FLS, "Members" => $memberCache );
        }

    }

}
if( !$FLSCache ) 
{
    show_error(get_phrase("message_nothing_found"));
}

$Teams = "";
foreach( $FLSCache as $FLS ) 
{
    $Members = "";
    foreach( $FLS["Members"] as $Member ) 
    {
        $_memberid = $Member["memberid"];
        $_membername = getMembername($Member["membername"], $Member["groupstyle"]);
        $_avatar = get_member_avatar($_memberid, $Member["gender"], "s");
        $_isMemberOnline = isMemberOnline($Member, false, true, false);
        $_membergroup = $Member["groupname"];
        $countryFlag = countryFlag($Member["country"]);
        $Image = array( "src" => $countryFlag, "alt" => $Member["country"], "title" => $Member["country"], "class" => "", "id" => "", "rel" => "resized_by_tsue" );
        $_countryFlag = getImage($Image);
        eval("\$_membername = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $_SendMessageLink = "";
        if( !is_member_of("unregistered") && has_permission("canpost_a_new_message") && $TSUE["TSUE_Member"]->info["memberid"] != $_memberid ) 
        {
            $Image = array( "src" => getImagesFullURL() . "status/sendmessage.png", "alt" => get_phrase("messages_send_message"), "title" => get_phrase("messages_send_message"), "class" => "", "id" => "", "rel" => "resized_by_tsue" );
            $_SendMessageLink = getImage($Image);
        }

        eval("\$Members .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("fls_members") . "\";");
    }
    $FLS["Team"]["description"] = $TSUE["TSUE_Parser"]->clearTinymceP($FLS["Team"]["description"]);
    eval("\$Teams .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("fls_teams") . "\";");
}
PrintOutput($Teams, $Page_Title);


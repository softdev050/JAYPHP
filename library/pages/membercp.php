<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "membercp.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "passwordstrength", "membercp" ));
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_membercp") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&amp;pid=" . PAGEID ));
if( is_member_of("unregistered") ) 
{
    show_error(get_phrase("permission_denied"));
}

$ValidActionList = array( "personal_details", "contact_details", "preferences", "privacy", "password", "signature", "avatar", "invite", "following", "performance", "subscribed_threads", "open_port_check_tool" );
if( !in_array($TSUE["action"], $ValidActionList) ) 
{
    $TSUE["action"] = "personal_details";
}

if( $TSUE["action"] == "personal_details" ) 
{
    $Page_Title = get_phrase("navigation_membercp_personal_details");
    $gender_female_checked = ($TSUE["TSUE_Member"]->info["gender"] == "f" ? " checked=\"checked\"" : "");
    $gender_male_checked = ($TSUE["TSUE_Member"]->info["gender"] == "m" ? " checked=\"checked\"" : "");
    $gender_unspecified_checked = (!$TSUE["TSUE_Member"]->info["gender"] ? " checked=\"checked\"" : "");
    if( has_permission("canchange_birthday") ) 
    {
        $TSUE["TSUE_Language"]->phrase["membercp_date_of_birth_info"] = "<input type=\"date\" name=\"date_of_birth\" id=\"date_of_birth\" class=\"s\" accesskey=\"d\" value=\"" . $TSUE["TSUE_Member"]->info["date_of_birth"] . "\" title=\"" . $TSUE["TSUE_Language"]->phrase["memberbday_tip"] . "\" />";
    }

    $countrySelect = countryList();
    $countryMember = countryFlag($TSUE["TSUE_Member"]->info["country"]);
    eval("\$countryMember = \"" . $TSUE["TSUE_Template"]->LoadTemplate("countryMember") . "\";");
}

if( $TSUE["action"] == "contact_details" ) 
{
    $Page_Title = get_phrase("navigation_membercp_contact_details");
    if( !has_permission("canchange_own_email") ) 
    {
        show_error(get_phrase("you_cant_change_your_email"), $Page_Title);
    }

}

if( $TSUE["action"] == "preferences" ) 
{
    $Page_Title = get_phrase("navigation_membercp_preferences");
    $get_themes = get_themes();
    $get_languages = get_languages();
    $get_timezones = "\r\n\t<select name=\"timezone\" id=\"cat_content\">";
    foreach( fetch_timezones() as $optionvalue => $timezonephrase ) 
    {
        $optionselected = ($optionvalue == $TSUE["TSUE_Member"]->info["timezone"] ? " selected=\"selected\"" : "");
        $get_timezones .= "\r\n\t\t<option value=\"" . $optionvalue . "\"" . $optionselected . ">" . $timezonephrase . "</option>";
    }
    $get_timezones .= "\r\n\t</select>";
    $torrentStyle = "\r\n\t<select name=\"torrentStyle\" id=\"cat_content\">\r\n\t\t<option value=\"1\"" . (($TSUE["TSUE_Member"]->info["torrentStyle"] == 1 ? " selected=\"selected\"" : "")) . ">" . get_phrase("modern") . "</option>\r\n\t\t<option value=\"2\"" . (($TSUE["TSUE_Member"]->info["torrentStyle"] == 2 ? " selected=\"selected\"" : "")) . ">" . get_phrase("classic") . "</option>\r\n\t</select>";
    require_once(REALPATH . "/library/functions/functions_getTorrents.php");
    $defaultCategories = prepareTorrentCategoriesCheckbox($TSUE["TSUE_Member"]->info["defaultTorrentCategories"], true, false, 2);
    $accountParked = ($TSUE["TSUE_Member"]->info["accountParked"] ? " checked=\"checked\"" : "");
}

if( $TSUE["action"] == "privacy" ) 
{
    $Page_Title = get_phrase("navigation_membercp_privacy");
    $visible_checked = ($TSUE["TSUE_Member"]->info["visible"] == "1" ? " checked=\"checked\"" : "");
    $Privacy = $TSUE["TSUE_Database"]->query_result("SELECT allow_view_profile, receive_admin_email, receive_pm_email, show_your_age FROM tsue_member_privacy WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    $everyone_selected = ($Privacy["allow_view_profile"] == "everyone" ? " selected=\"selected\"" : "");
    $members_selected = ($Privacy["allow_view_profile"] == "members" ? " selected=\"selected\"" : "");
    $followed_selected = ($Privacy["allow_view_profile"] == "followed" ? " selected=\"selected\"" : "");
    $none_selected = ($Privacy["allow_view_profile"] == "none" ? " selected=\"selected\"" : "");
    $receive_admin_email_checked = ($Privacy["receive_admin_email"] == "1" ? " checked=\"checked\"" : "");
    $receive_pm_email_checked = ($Privacy["receive_pm_email"] == "1" ? " checked=\"checked\"" : "");
    $receive_pm_email_disabled = (!has_permission("canreceive_pm_email") ? " disabled=\"disabled\" data=\"force-disabled\"" : "");
    $show_your_age_checked = ($Privacy["show_your_age"] == "1" ? " checked=\"checked\"" : "");
}

if( $TSUE["action"] == "password" ) 
{
    $Page_Title = get_phrase("navigation_membercp_password");
    $TSUE["TSUE_Language"]->phrase["memberpassword_tip"] = get_phrase("memberpassword_tip", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"]);
}

if( $TSUE["action"] == "signature" ) 
{
    $Page_Title = get_phrase("navigation_membercp_signature");
    if( !has_permission("canpost_signature") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $TSUE["TSUE_Member"]->info["signature"] = html_clean($TSUE["TSUE_Member"]->info["signature"]);
}

if( $TSUE["action"] == "avatar" ) 
{
    $Page_Title = get_phrase("navigation_membercp_avatar");
    if( !has_permission("canupload_avatar") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $Error = "";
    if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" ) 
    {
        globalize("post", array( "securitytoken" => "TRIM" ));
        if( !isValidToken($securitytoken) ) 
        {
            show_error(get_phrase("invalid_security_token"));
        }

        $NewAvatar = (isset($_FILES["avatar"]) ? $_FILES["avatar"] : false);
        if( !$NewAvatar ) 
        {
            $Error[] = get_phrase("avatar_invalid_image");
        }
        else
        {
            if( !is_uploaded_file($NewAvatar["tmp_name"]) ) 
            {
                $Error[] = get_phrase("unable_upload");
            }
            else
            {
                $_AllowedImages = array( "jpg", "jpeg", "gif", "png" );
                $_OrjName = (isset($NewAvatar["name"]) ? $NewAvatar["name"] : "");
                $_Type = (isset($NewAvatar["type"]) ? $NewAvatar["type"] : "");
                $_TempName = (isset($NewAvatar["tmp_name"]) ? $NewAvatar["tmp_name"] : "");
                $_Error = (isset($NewAvatar["error"]) ? $NewAvatar["error"] : "");
                $_Size = (isset($NewAvatar["size"]) && 0 < intval($NewAvatar["size"]) ? $NewAvatar["size"] : "");
                if( !$_OrjName || !$_Type || !$_TempName || $_Error || $_Size == 0 ) 
                {
                    $Error[] = get_phrase("avatar_invalid_image");
                }
                else
                {
                    if( !in_array(file_extension($_OrjName), $_AllowedImages) ) 
                    {
                        $Error[] = get_phrase("avatar_invalid_image");
                    }
                    else
                    {
                        $imageInfo = getimagesize($_TempName);
                        list($width, $height) = $imageInfo;
                        if( !$imageInfo || !$width || !$height || 2 * $height < $width || 2 * $width < $height ) 
                        {
                            $Error[] = get_phrase("avatar_please_provide_an_image_whose_longer_side_is_no_more_than_twice_length");
                        }
                        else
                        {
                            $AvatarPath = REALPATH . "data/avatars/";
                            require_once(REALPATH . "/library/functions/functions_memberAvatar.php");
                            prepareAvatar($NewAvatar, $AvatarPath, $TSUE["TSUE_Member"]->info["memberid"], $width, $height);
                        }

                    }

                }

            }

        }

        if( is_array($Error) ) 
        {
            show_error($Error);
        }
        else
        {
            show_information(get_phrase("avatar_uploaded"));
        }

    }

}

if( $TSUE["action"] == "invite" ) 
{
    if( !has_permission("cansend_invite") ) 
    {
        show_error(get_phrase("invite_no_permission"));
    }

    $Page_Title = get_phrase("navigation_membercp_invite");
    require_once(REALPATH . "/library/functions/functions_getInvites.php");
    $membercp_invited_friends_table = prepareInviteList($TSUE["TSUE_Member"]->info["memberid"]);
}

if( $TSUE["action"] == "following" ) 
{
    $Page_Title = get_phrase("navigation_people_you_follow");
    if( is_member_of("unregistered") ) 
    {
        show_error(get_phrase("login_required"));
    }

    if( !has_permission("canfollow") ) 
    {
        show_error(get_phrase("permission_denied"));
    }

    $followingMembersCount = $TSUE["TSUE_Database"]->query_result("SELECT COUNT(memberid) as totalFollowing FROM tsue_member_follow WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    if( !$followingMembersCount || !$followingMembersCount["totalFollowing"] ) 
    {
        show_error(get_phrase("message_nothing_found"));
    }
    else
    {
        require_once(REALPATH . "/library/functions/functions_memberInfo.php");
        $Pagination = Pagination($followingMembersCount["totalFollowing"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_members_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&amp;action=following&amp;pid=28&amp;");
        $MembersQuery = $TSUE["TSUE_Database"]->query("SELECT f.follow_memberid as memberid, m.membername, m.email, m.joindate, m.lastactivity, m.gender, m.ipaddress, m.visible, m.accountParked, profile.date_of_birth, profile.custom_title, profile.uploaded, profile.downloaded, profile.country, p.allow_view_profile, p.show_your_age, profile.points, profile.muted, g.groupname, g.groupstyle, s.location as lastViewedPage, s.http_referer, s.query_string, b.memberid as isBanned, w.memberid as isWarned, i.sender_memberid as invitedByID, mm.membername as invitedByMembername, gg.groupstyle as invitedByGroupstyle \r\n\t\tFROM tsue_member_follow f \r\n\t\tLEFT JOIN tsue_members m ON (f.follow_memberid=m.memberid) \r\n\t\tINNER JOIN tsue_member_profile profile ON(profile.memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_member_privacy p ON(p.memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_session s ON(s.memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_member_bans b ON(m.memberid=b.memberid) \r\n\t\tLEFT JOIN tsue_member_warns w ON(m.memberid=w.memberid) \r\n\t\tLEFT JOIN tsue_invites i ON(m.memberid=i.receiver_memberid && i.status = 'completed') \r\n\t\tLEFT JOIN tsue_members mm ON(i.sender_memberid=mm.memberid) \r\n\t\tLEFT JOIN tsue_membergroups gg ON(mm.membergroupid=gg.membergroupid) \r\n\t\tWHERE f.memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " \r\n\t\tORDER BY f.follow_date DESC " . $Pagination["0"]);
        $queryCache = $memberids = array(  );
        while( $followingMember = $TSUE["TSUE_Database"]->fetch_assoc($MembersQuery) ) 
        {
            $queryCache[] = $followingMember;
            $memberids[] = $followingMember["memberid"];
        }
        require_once(REALPATH . "/library/classes/class_awards.php");
        $TSUE_Awards = new TSUE_Awards($memberids);
        unset($memberids);
        $followingList = "";
        foreach( $queryCache as $followingMember ) 
        {
            $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
            $PassiveUser = array( "memberid" => $followingMember["memberid"], "allow_view_profile" => $followingMember["allow_view_profile"] );
            $memberAwards = $TSUE_Awards->getMemberAwards($followingMember["memberid"]);
            $Info = prepareInfo($followingMember["memberid"], $followingMember, "m", true, "no", $memberAwards, !canViewProfile($ActiveUser, $PassiveUser));
            $followingList .= $Info["0"];
        }
        unset($queryCache);
    }

}

if( $TSUE["action"] == "performance" ) 
{
    $Page_Title = get_phrase("navigation_membercp_performance");
    if( is_member_of("unregistered") ) 
    {
        show_error(get_phrase("login_required"));
    }

    $shoutbox_enabled_checked = (fetchCPOption("shoutbox_enabled") == "1" ? " checked=\"checked\"" : "");
    $irtm_enabled_checked = (fetchCPOption("irtm_enabled") == "1" ? " checked=\"checked\"" : "");
    $alerts_enabled_checked = (fetchCPOption("alerts_enabled") == "1" ? " checked=\"checked\"" : "");
}

if( $TSUE["action"] == "subscribed_threads" ) 
{
    $Page_Title = get_phrase("subscribed_threads");
    if( is_member_of("unregistered") ) 
    {
        show_error(get_phrase("login_required"));
    }

    require_once(REALPATH . "/library/classes/class_forums.php");
    $TSUE_Forums = new forums();
    AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&amp;pid=" . PAGEID . "&amp;action=" . $TSUE["action"] ));
    $Page_Title = get_phrase("navigation_membercp") . " - " . $Page_Title;
    PrintOutput($TSUE_Forums->prepareSubscribedThreads(), $Page_Title);
}

if( $TSUE["action"] == "open_port_check_tool" ) 
{
    $Page_Title = get_phrase("open_port_check_tool");
    $open_port_check_tool_alt = show_information(get_phrase("open_port_check_tool_alt"), "", false);
    $your_ip = MEMBER_IP;
}

AddBreadcrumb(array( $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&amp;pid=" . PAGEID . "&amp;action=" . $TSUE["action"] ));
$Page_Title = get_phrase("navigation_membercp") . " - " . $Page_Title;
eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("membercp_" . $TSUE["action"]) . "\";");
PrintOutput($Output, $Page_Title);


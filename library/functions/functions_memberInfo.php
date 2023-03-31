<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function memberInfo($memberid = 0, $useFollowLink = false, $_inOverlay = "no", $avatar = "m", $memberAwards = "")
{
    global $TSUE;
    $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membername, m.email, m.joindate, m.lastactivity, m.ipaddress, m.gender, m.visible, m.accountParked, profile.date_of_birth, profile.custom_title, profile.uploaded, profile.downloaded, profile.country, profile.signature, p.allow_view_profile, p.show_your_age, profile.points, profile.muted, g.groupname, g.groupstyle, s.location as lastViewedPage, s.http_referer, s.query_string, b.memberid as isBanned, w.memberid as isWarned, i.sender_memberid as invitedByID, mm.membername as invitedByMembername, gg.groupstyle as invitedByGroupstyle FROM tsue_members m INNER JOIN tsue_member_profile profile USING(memberid) INNER JOIN tsue_member_privacy p USING(memberid) INNER JOIN tsue_membergroups g USING(membergroupid) LEFT JOIN tsue_session s USING(memberid) LEFT JOIN tsue_member_bans b USING(memberid) LEFT JOIN tsue_member_warns w USING(memberid) LEFT JOIN tsue_invites i ON(m.memberid=i.receiver_memberid && i.status = 'completed') LEFT JOIN tsue_members mm ON(i.sender_memberid=mm.memberid) LEFT JOIN tsue_membergroups gg ON(mm.membergroupid=gg.membergroupid) WHERE m.memberid = " . $TSUE["TSUE_Database"]->escape($memberid));
    if( !$Member ) 
    {
        $Error[] = get_phrase("member_not_found");
        return implode("<br />", $Error);
    }

    $ActiveUser = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"] );
    $PassiveUser = array( "memberid" => $Member["memberid"], "allow_view_profile" => $Member["allow_view_profile"] );
    return prepareInfo($memberid, $Member, $avatar, $useFollowLink, $_inOverlay, $memberAwards, !canViewProfile($ActiveUser, $PassiveUser));
}

function prepareInfo($memberid, $Member, $avatar = "m", $useFollowLink = false, $_inOverlay = "no", $memberAwards = "", $limitedView = false)
{
    global $TSUE;
    $Page_Title = get_phrase("memberinfo_title", $Member["membername"]);
    if( $Member["accountParked"] ) 
    {
        $Page_Title .= " " . get_phrase("this_account_has_been_parked");
    }

    $_memberid = $memberid;
    $_membername = $Member["membername"];
    $_membername = getMembername($Member["membername"], $Member["groupstyle"]);
    $countryFlag = countryFlag($Member["country"]);
    $_avatar = get_member_avatar($memberid, $Member["gender"], $avatar);
    if( defined("IS_AJAX") ) 
    {
        $Image = array( "src" => $countryFlag, "alt" => $Member["country"], "title" => $Member["country"], "class" => "", "id" => "", "rel" => "resized_by_tsue" );
        $countryFlag = getImage($Image);
    }
    else
    {
        $_alt = "";
        eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
        eval("\$countryFlag = \"" . $TSUE["TSUE_Template"]->LoadTemplate("countryFlag") . "\";");
    }

    $FollowLink = "";
    if( $useFollowLink && has_permission("canfollow") && $memberid != $TSUE["TSUE_Member"]->info["memberid"] ) 
    {
        $followText = get_phrase((is_following($TSUE["TSUE_Member"]->info["memberid"], $memberid) ? "button_unfollow" : "button_follow"));
        eval("\$FollowLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_follow_link") . "\";");
    }

    $SendMessageLink = "";
    if( !is_member_of("unregistered") && has_permission("canpost_a_new_message") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        $SendMessageLink = get_phrase("messages_send_message");
    }

    eval("\$member_info_direct_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_direct_link") . "\";");
    $Member["groupname"] = getGroupname($Member);
    $staffLinks = "";
    if( $limitedView ) 
    {
        if( defined("IS_AJAX") ) 
        {
            eval("\$MemberInfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_card_limited_view") . "\";");
        }
        else
        {
            eval("\$MemberInfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_limited_view") . "\";");
        }

        return array( $MemberInfo, $Member, $limitedView );
    }

    $Member["ratio"] = member_ratio($Member["uploaded"], $Member["downloaded"]);
    $Member["buffer"] = ($Member["downloaded"] < $Member["uploaded"] ? friendly_size($Member["uploaded"] - $Member["downloaded"]) : 0);
    $Member["uploaded"] = friendly_size($Member["uploaded"]);
    $Member["downloaded"] = friendly_size($Member["downloaded"]);
    $Member["points"] = friendly_number_format($Member["points"], 0, ".", ".");
    $MoreMemberInfo[] = $member_info_direct_link;
    if( has_permission("canview_special_details") ) 
    {
        $ipaddress = $Member["ipaddress"];
        eval("\$MoreMemberInfo[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ipdetails") . "\";");
        $MoreMemberInfo[] = $Member["email"];
    }

    $MemberSince = get_phrase("memberinfo_membersince", convert_relative_time($Member["joindate"]));
    $LastActivity = get_phrase("memberinfo_lastactivity", convert_relative_time($Member["lastactivity"]));
    if( $Member["lastViewedPage"] ) 
    {
        $LastActivity .= " (" . get_phrase("online_viewing", translate_location($Member["lastViewedPage"], $Member["http_referer"], $Member["query_string"])) . ")";
    }

    if( !$Member["visible"] && !has_permission("canview_invisible_members") && $memberid != $TSUE["TSUE_Member"]->info["memberid"] ) 
    {
        $LastActivity = "";
    }

    $banned = "";
    if( $Member["isBanned"] ) 
    {
        eval("\$banned = \"" . $TSUE["TSUE_Template"]->LoadTemplate("banned_flag") . "\";");
    }

    $muted = "";
    if( $Member["muted"] ) 
    {
        $TSUE["TSUE_Language"]->phrase["muted"] = listMutes($Member["muted"], false);
        eval("\$muted = \"" . $TSUE["TSUE_Template"]->LoadTemplate("muted_flag") . "\";");
    }

    $warned = "";
    if( $Member["isWarned"] ) 
    {
        eval("\$warned = \"" . $TSUE["TSUE_Template"]->LoadTemplate("warned_flag") . "\";");
    }

    if( trim($Member["date_of_birth"]) != "" && ($Member["show_your_age"] || $memberid == $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canview_special_details")) ) 
    {
        $memberAge = calculateAge($Member["date_of_birth"]);
        if( $memberAge && 5 < $memberAge ) 
        {
            $MoreMemberInfo[] = get_phrase("memberinfo_age", $memberAge);
        }

    }

    if( $Member["gender"] ) 
    {
        $MoreMemberInfo[] = get_phrase("memberinfo_gender_" . (($Member["gender"] == "m" ? "male" : "female")));
    }

    $invitedBy = "";
    if( isset($Member["invitedByID"]) && $Member["invitedByID"] && (has_permission("canview_special_details") || $Member["invitedByID"] == $TSUE["TSUE_Member"]->info["memberid"] || $Member["memberid"] == $TSUE["TSUE_Member"]->info["memberid"]) ) 
    {
        $invitedBy = getInviter($Member);
    }

    if( isset($MoreMemberInfo) && is_array($MoreMemberInfo) ) 
    {
        $MoreMemberInfo = implode(", ", $MoreMemberInfo);
    }

    $dropDownMenuLinks = "";
    if( !is_member_of("unregistered") && has_permission("canview_member_history") ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_member_history_link") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canaward_members") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_award_member_link") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canwarn_member") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        if( $Member["isWarned"] ) 
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_lift_warn_link") . "\";");
        }
        else
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_warn_member_link") . "\";");
        }

    }

    if( !is_member_of("unregistered") && has_permission("canban_member") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        if( $Member["isBanned"] ) 
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_lift_ban_link") . "\";");
        }
        else
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_ban_member_link") . "\";");
        }

    }

    if( !is_member_of("unregistered") && has_permission("canmute_member") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        if( $Member["muted"] ) 
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_lift_mute_link") . "\";");
        }
        else
        {
            eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_mute_member_link") . "\";");
        }

    }

    if( !is_member_of("unregistered") && has_permission("canremove_avatar") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_remove_member_avatar") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canreset_passkey") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_reset_member_passkey") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canlogin_admincp") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_manage_member_account") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canview_all_content") ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_find_all_content_link") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canadd_note") ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_add_a_note_link") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canview_special_details") && has_permission("canview_member_profiles") ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_member_ip_to_country") . "\";");
    }

    if( !is_member_of("unregistered") && has_permission("canuse_spam_cleaner") ) 
    {
        eval("\$dropDownMenuLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_spam_cleaner") . "\";");
    }

    if( $dropDownMenuLinks ) 
    {
        eval("\$staffLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dropDownMenu") . "\";");
    }

    if( defined("IS_AJAX") ) 
    {
        eval("\$MemberInfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_card") . "\";");
    }
    else
    {
        eval("\$MemberInfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info") . "\";");
    }

    return array( $MemberInfo, $Member, $limitedView );
}

function getInviter($Member)
{
    global $TSUE;
    $_memberid = $Member["invitedByID"];
    $_membername = getMembername($Member["invitedByMembername"], $Member["invitedByGroupstyle"]);
    eval("\$invitedBy = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    return get_phrase("memberinfo_invited_by", $invitedBy);
}



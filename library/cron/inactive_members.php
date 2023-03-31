<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function inactiveMembers()
{
    global $TSUE;
    $TSUE["TSUE_Settings"]->loadSettings("inactive_members");
    $Settings = array( "active" => intval(getSetting("inactive_members", "active")), "grace_period" => intval(getSetting("inactive_members", "grace_period")), "max_email" => intval(getSetting("inactive_members", "max_email")), "prune_members" => intval(getSetting("inactive_members", "prune_members")), "membergroups" => trim(getSetting("inactive_members", "membergroups")) );
    if( $Settings["active"] && $Settings["grace_period"] && $Settings["membergroups"] ) 
    {
        sendMailToInactiveMembers($Settings);
        if( $Settings["prune_members"] ) 
        {
            deleteInactiveMembers($Settings);
        }

    }

}

function sendMailToInactiveMembers($Settings)
{
    global $TSUE;
    $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid, m.membername, m.email, m.lastactivity, m.accountParked, b.memberid AS isBanned \r\n\tFROM tsue_members m \r\n\tLEFT JOIN tsue_member_privacy p USING(memberid) \r\n\tLEFT JOIN tsue_member_bans b USING(memberid) \r\n\tWHERE p.receive_admin_email = 1 AND m.lastactivity < UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL - " . $Settings["grace_period"] . " DAY)) AND m.inactivitytag = 0 AND m.membergroupid IN (" . $Settings["membergroups"] . ")");
    if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
    {
        $forgotPasswordLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&pid=1&dialog=forgot-password";
        $warnedMembers = "";
        $Count = 0;
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            if( $Settings["max_email"] && $Settings["max_email"] < $Count ) 
            {
                break;
            }

            if( !$Member["accountParked"] && !$Member["isBanned"] ) 
            {
                $Subject = get_phrase("inactivity_reminder_subject", $Member["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                $Body = get_phrase("inactivity_reminder_body", $Member["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $forgotPasswordLink, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
                sent_mail($Member["email"], $Member["membername"], $Subject, $Body, "", "", true, false);
                $warnedMembers .= ", " . $Member["memberid"];
                $Count++;
            }

        }
        if( $warnedMembers ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_members", array( "inactivitytag" => TIMENOW ), "memberid IN (0" . $warnedMembers . ")");
        }

    }

}

function deleteInactiveMembers($Settings)
{
    global $TSUE;
    $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid, membername, accountParked FROM tsue_members WHERE inactivitytag > 0 AND inactivitytag < UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL - " . $Settings["prune_members"] . " DAY))");
    if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
    {
        $warnedMembers = "";
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            if( !$Member["accountParked"] ) 
            {
                $warnedMembers .= ", " . $Member["memberid"];
                logAction(get_phrase("member_x_has_been_deleted_due_inactivity", $Member["membername"]));
            }

        }
        if( $warnedMembers ) 
        {
            deleteMember($warnedMembers);
        }

    }

}

function deleteMember($memberids)
{
    global $TSUE;
    if( !$memberids ) 
    {
        return NULL;
    }

    $TSUE["TSUE_Database"]->delete("tsue_comments", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_invites", "sender_memberid IN (0" . $memberids . ") OR receiver_memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_liked_content", "like_memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_members", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_alerts", "alerted_memberid IN (0" . $memberids . ") OR memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_bans", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_confirmation", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_follow", "memberid IN (0" . $memberids . ") OR follow_memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_mutes", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_privacy", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_profile", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_profile_visitors", "memberid IN (0" . $memberids . ") OR visitorid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_promotions", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_upgrades_promotions", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_upgrades_purchases", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_member_warns", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_messages_master", "owner_memberid IN (0" . $memberids . ") OR receiver_memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_messages_replies", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_requests", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_torrents_peers", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("tsue_uploader_applications", "memberid IN (0" . $memberids . ")");
    $TSUE["TSUE_Database"]->delete("xbt_files_users", "uid IN (0" . $memberids . ")");
}



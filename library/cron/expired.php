<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function resetMemberMultipliers()
{
    global $TSUE;
    if( getSetting("xbt_happy_hours", "used") ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "1", "upload_multiplier" => "1" ));
        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "download_multiplier" => "1", "upload_multiplier" => "1", "flags" => "2" ));
        $TSUE["TSUE_Database"]->delete("tsue_settings", "settingname = 'xbt_happy_hours'");
    }

}

function deleteExpiredData()
{
    global $TSUE;
    if( getSetting("xbt", "active") && ($points_seed = getSetting("global_settings", "points_seed")) && 0 < $points_seed ) 
    {
        $points_x2_for_big_torrents = (0 + getSetting("global_settings", "points_x2_for_big_torrents", 0)) * 1073741824;
        $minGB = (0 + getSetting("global_settings", "points_seed_min_gb", 0)) * 1073741824;
        $points_seed_al = intval(getSetting("global_settings", "points_seed_al", 0));
        $TSUE["TSUE_Database"]->query("UPDATE tsue_member_profile p JOIN (SELECT COUNT(x.uid) as totalSeeds, x.uid, t.size FROM xbt_files_users x INNER JOIN tsue_torrents t ON(x.fid=t.tid) WHERE x.active=1 AND x.left=0" . ((0 < $minGB ? " AND t.size > " . $minGB : "")) . ((0 < $points_seed_al ? " AND x.announced >= " . $points_seed_al : "")) . " GROUP BY x.uid) AS data ON (p.memberid=data.uid) SET points = IF(" . $points_x2_for_big_torrents . " > 0 && data.size >= " . $points_x2_for_big_torrents . ",points+(" . $points_seed . "*data.totalSeeds*2),points+(" . $points_seed . "*data.totalSeeds))");
    }

    if( getSetting("xbt", "active") && getSetting("happy_hours", "active") ) 
    {
        $happy_hours_start_date = getSetting("happy_hours", "start_date");
        $happy_hours_end_date = getSetting("happy_hours", "end_date");
        $happy_hours_freeleech = getSetting("happy_hours", "freeleech");
        $happy_hours_double_upload = getSetting("happy_hours", "doubleupload");
        if( $happy_hours_start_date <= TIMENOW && TIMENOW <= $happy_hours_end_date ) 
        {
            $xbtHappyHoursUsed = false;
            if( $happy_hours_freeleech && $happy_hours_double_upload ) 
            {
                $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "0", "upload_multiplier" => "2" ));
                $TSUE["TSUE_Database"]->update("tsue_torrents", array( "download_multiplier" => "0", "upload_multiplier" => "2", "flags" => "2" ));
                $xbtHappyHoursUsed = true;
            }
            else
            {
                if( $happy_hours_double_upload ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "1", "upload_multiplier" => "2" ));
                    $TSUE["TSUE_Database"]->update("tsue_torrents", array( "download_multiplier" => "1", "upload_multiplier" => "2", "flags" => "2" ));
                    $xbtHappyHoursUsed = true;
                }
                else
                {
                    if( $happy_hours_freeleech ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "download_multiplier" => "0", "upload_multiplier" => "1" ));
                        $TSUE["TSUE_Database"]->update("tsue_torrents", array( "download_multiplier" => "0", "upload_multiplier" => "1", "flags" => "2" ));
                        $xbtHappyHoursUsed = true;
                    }
                    else
                    {
                        resetmembermultipliers();
                    }

                }

            }

            if( $xbtHappyHoursUsed && !getSetting("xbt_happy_hours", "used") ) 
            {
                updateSettings("xbt_happy_hours", array( "used" => 1 ));
            }

        }
        else
        {
            resetmembermultipliers();
        }

    }
    else
    {
        resetmembermultipliers();
    }

    $timeOut = TIMENOW - 3600;
    $Attachments = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE attachment_id, content_type, filename FROM tsue_attachments WHERE upload_date < " . $timeOut . " AND associated = 0");
    if( $TSUE["TSUE_Database"]->num_rows($Attachments) ) 
    {
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $deleteAttachments = array(  );
        $attachmentPath = REALPATH . "/data/";
        while( $Attachment = $TSUE["TSUE_Database"]->fetch_assoc($Attachments) ) 
        {
            $deleteAttachments[] = $Attachment["attachment_id"];
            if( strstr($Attachment["content_type"], "torrent") !== false ) 
            {
                if( $Attachment["content_type"] == "torrent_images" ) 
                {
                    deleteImages($Attachment["filename"]);
                    continue;
                }

                $filePath = $attachmentPath . "torrents/" . $Attachment["content_type"] . "/" . $Attachment["filename"];
            }
            else
            {
                $filePath = $attachmentPath . $Attachment["content_type"] . "/" . $Attachment["filename"];
            }

            if( is_file($filePath) ) 
            {
                @unlink($filePath);
            }

        }
        if( !empty($deleteAttachments) ) 
        {
            $TSUE["TSUE_Database"]->delete("tsue_attachments", "attachment_id IN (" . implode(",", $deleteAttachments) . ")");
        }

    }

    $timeOut = TIMENOW - 86400;
    $TSUE["TSUE_Database"]->delete("tsue_downloads_session", "`started` < " . $timeOut);
    $timeOut = TIMENOW - $TSUE["TSUE_Settings"]->settings["global_settings"]["reseed_request_flood_limit"] * 86400;
    $TSUE["TSUE_Database"]->delete("tsue_flood_check", "`flood_time` < " . $timeOut);
    $days_retain_unused_invites = intval(getSetting("global_settings", "days_retain_unused_invites"));
    if( $days_retain_unused_invites ) 
    {
        $timeOut = TIMENOW - $days_retain_unused_invites * 86400;
        $TSUE["TSUE_Database"]->delete("tsue_invites", "`send_date` < " . $timeOut . " AND `status` = 'pending'");
    }

    $timeOut = TIMENOW - $TSUE["TSUE_Settings"]->settings["global_settings"]["alerts_popup_expiry_days"] * 86400;
    $timeOut2 = TIMENOW - 30 * 86400;
    $TSUE["TSUE_Database"]->delete("tsue_member_alerts", "(`event_date` < " . $timeOut . " AND `read_date` > 0) OR (`event_date` < " . $timeOut2 . " AND `read_date` = 0)");
    $TSUE["TSUE_Database"]->delete("tsue_member_bans", "end_date > 0 AND end_date <= " . TIMENOW);
    $timeOut = TIMENOW - 3 * 86400;
    $TSUE["TSUE_Database"]->delete("tsue_member_confirmation", "`date` < " . $timeOut);
    $expiredMutes = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE DISTINCT memberid FROM tsue_member_mutes WHERE end_date > 0 AND end_date <= " . TIMENOW);
    if( $expiredMutes && $TSUE["TSUE_Database"]->num_rows($expiredMutes) ) 
    {
        $expiredMutesMembers = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($expiredMutes) ) 
        {
            $expiredMutesMembers[] = $Member["memberid"];
        }
        if( !empty($expiredMutesMembers) ) 
        {
            $implodeMembers = implode(",", $expiredMutesMembers);
            $TSUE["TSUE_Database"]->delete("tsue_member_mutes", "memberid IN (" . $implodeMembers . ")");
            $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "muted" => "" ), "memberid IN (" . $implodeMembers . ")");
        }

    }

    $findMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.promotion_id, p.memberid, p.old_membergroupid, u.upgrade_demote_to FROM tsue_member_upgrades_promotions p LEFT JOIN tsue_member_upgrades u USING(upgrade_id) WHERE p.expiry_date > 0 AND p.expiry_date <= " . TIMENOW . " AND p.active = 1");
    if( $findMembers && $TSUE["TSUE_Database"]->num_rows($findMembers) ) 
    {
        $deletePromotions = $expiredMembers = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($findMembers) ) 
        {
            $deletePromotions[] = $Member["promotion_id"];
            $expiredMembers[] = $Member["memberid"];
            $doNotDemote = false;
            if( $Member["old_membergroupid"] || $Member["upgrade_demote_to"] ) 
            {
                $previousUpgrades = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.old_membergroupid, u.upgrade_demote_to FROM tsue_member_upgrades_promotions p LEFT JOIN tsue_member_upgrades u USING(upgrade_id) WHERE p.promotion_id != " . $TSUE["TSUE_Database"]->escape($Member["promotion_id"]) . " AND (p.expiry_date = 0 OR p.expiry_date > " . TIMENOW . ") AND p.active = 1 AND p.memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]));
                if( $TSUE["TSUE_Database"]->num_rows($previousUpgrades) ) 
                {
                    while( $previousUpgrade = $TSUE["TSUE_Database"]->fetch_assoc($previousUpgrades) ) 
                    {
                        if( $previousUpgrade["old_membergroupid"] || $previousUpgrade["upgrade_demote_to"] ) 
                        {
                            $doNotDemote = true;
                            break;
                        }

                    }
                }

                if( !$doNotDemote ) 
                {
                    if( $Member["upgrade_demote_to"] ) 
                    {
                        $Member["old_membergroupid"] = $Member["upgrade_demote_to"];
                    }

                    $TSUE["TSUE_Database"]->update("tsue_members", array( "membergroupid" => $Member["old_membergroupid"] ), "memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]));
                }

            }

        }
        if( $expiredMembers && count($expiredMembers) ) 
        {
            foreach( $expiredMembers as $Memberid ) 
            {
                alert_member($Memberid, 0, "", "paid_subscriptions", 0, "expired");
                $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 0, "total_uploaded" => array( "escape" => 0, "value" => "IF(total_uploaded<total_downloaded,total_downloaded,total_uploaded)" ) ), "memberid=" . $TSUE["TSUE_Database"]->escape($Memberid));
                $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 0, "uploaded" => array( "escape" => 0, "value" => "IF(uploaded<downloaded,downloaded,uploaded)" ) ), "uid=" . $TSUE["TSUE_Database"]->escape($Memberid));
                $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "uploaded" => array( "escape" => 0, "value" => "IF(uploaded<downloaded,downloaded,uploaded)" ) ), "memberid=" . $TSUE["TSUE_Database"]->escape($Memberid));
            }
        }

        if( $deletePromotions && count($deletePromotions) ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_member_upgrades_promotions", array( "active" => 0 ), "promotion_id IN (" . implode(",", $deletePromotions) . ")");
        }

    }

    $timeOut = TIMENOW - 86400;
    $TSUE["TSUE_Database"]->delete("tsue_member_upgrades_purchases", "`completed` = 0 AND `created` < " . $timeOut);
    $TSUE["TSUE_Database"]->delete("tsue_member_warns", "end_date > 0 AND end_date <= " . TIMENOW);
    $ExpiredMessageReplies = array(  );
    $FindMessages = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE message_id FROM tsue_messages_master WHERE owner_deleted = 1 AND receiver_deleted = 1");
    if( $FindMessages && $TSUE["TSUE_Database"]->num_rows($FindMessages) ) 
    {
        while( $Message = $TSUE["TSUE_Database"]->fetch_assoc($FindMessages) ) 
        {
            $ExpiredMessageReplies[] = $Message["message_id"];
        }
        if( $ExpiredMessageReplies && count($ExpiredMessageReplies) ) 
        {
            $TSUE["TSUE_Database"]->delete("tsue_messages_replies", "message_id IN (" . implode(",", $ExpiredMessageReplies) . ")");
        }

        $TSUE["TSUE_Database"]->delete("tsue_messages_master", "owner_deleted=1 AND receiver_deleted=1");
    }

    if( 0 < getSetting("global_settings", "days_retain_posted_request") ) 
    {
        $timeOut = TIMENOW - 86400 * getSetting("global_settings", "days_retain_posted_request");
        $TSUE["TSUE_Database"]->delete("tsue_requests", "`added` < " . $timeOut);
    }

    $days_retain_posted_upcoming_releases = intval(getSetting("global_settings", "days_retain_posted_upcoming_releases", 5));
    if( $days_retain_posted_upcoming_releases ) 
    {
        $timeOut = TIMENOW - 86400 * $days_retain_posted_upcoming_releases;
        $TSUE["TSUE_Database"]->delete("tsue_upcoming_releases", "`added` < " . $timeOut);
    }

    $timeOut = TIMENOW - 7200;
    $TSUE["TSUE_Database"]->delete("tsue_search", "`search_date` < " . $timeOut);
    $timeOut = TIMENOW - 86400;
    $TSUE["TSUE_Database"]->delete("tsue_session", "`date` < " . $timeOut);
    if( 0 < $TSUE["TSUE_Settings"]->settings["global_settings"]["shouts_prune_in_day"] ) 
    {
        $timeOut = TIMENOW - 86400 * $TSUE["TSUE_Settings"]->settings["global_settings"]["shouts_prune_in_day"];
        $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "`sdate` < " . $timeOut);
    }

    if( 0 < $TSUE["TSUE_Settings"]->settings["global_settings"]["peers_prune_in_day"] ) 
    {
        $timeOut = TIMENOW - 86400 * $TSUE["TSUE_Settings"]->settings["global_settings"]["peers_prune_in_day"];
        if( getSetting("xbt", "active") ) 
        {
            $TSUE["TSUE_Database"]->delete("xbt_files_users", "`mtime` < " . $timeOut);
            return NULL;
        }

        $TSUE["TSUE_Database"]->delete("tsue_torrents_peers", "`last_updated` < " . $timeOut);
    }

}



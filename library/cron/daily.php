<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function runDaily()
{
    global $TSUE;
    checkPromotions();
    deleteDeadTorrents();
}

function checkPromotions()
{
    global $TSUE;
    $Promotions = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_auto_promotions WHERE promotionSystemActive = 1");
    if( $TSUE["TSUE_Database"]->num_rows($Promotions) ) 
    {
        while( $Promotion = $TSUE["TSUE_Database"]->fetch_assoc($Promotions) ) 
        {
            promoteDemoteMembers($Promotion);
        }
    }

}

function promoteDemoteMembers($Promotion)
{
    global $TSUE;
    $Promotion["minDaysRegistered"] = $Promotion["minDaysRegistered"] * 86400;
    $Promotion["minUpload"] = $Promotion["minUpload"] * 1024 * 1024 * 1024;
    $findMembers = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membergroupid, m.joindate, p.uploaded, p.downloaded, p.total_posts FROM tsue_members m INNER JOIN tsue_member_profile p USING(memberid) WHERE m.membergroupid = " . $TSUE["TSUE_Database"]->escape($Promotion["checkForMembergroupid"]));
    if( $TSUE["TSUE_Database"]->num_rows($findMembers) ) 
    {
        $promoteCache = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($findMembers) ) 
        {
            if( ($Member["joindate"] <= TIMENOW - $Promotion["minDaysRegistered"] || !$Promotion["minDaysRegistered"]) && ($Promotion["minUpload"] <= $Member["uploaded"] || !$Promotion["minUpload"]) && ($Promotion["minRatio"] <= member_ratio($Member["uploaded"], $Member["downloaded"], true) || !$Promotion["minRatio"]) && ($Promotion["minPosts"] <= $Member["total_posts"] || !$Promotion["minPosts"]) ) 
            {
                $promoteCache[$Member["memberid"]] = array( "memberid" => $Member["memberid"], "beforeMembergroupid" => $Member["membergroupid"], "date" => TIMENOW );
            }

        }
        if( $promoteCache ) 
        {
            foreach( $promoteCache as $memberID => $buildQuery ) 
            {
                $checkPreviousPromotion = $TSUE["TSUE_Database"]->query_result("SELECT beforeMembergroupid FROM tsue_member_promotions WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberID));
                if( $checkPreviousPromotion ) 
                {
                    $buildQuery["beforeMembergroupid"] = $checkPreviousPromotion["beforeMembergroupid"];
                }

                $TSUE["TSUE_Database"]->replace("tsue_member_promotions", $buildQuery);
                if( $TSUE["TSUE_Database"]->affected_rows() ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_members", array( "membergroupid" => $Promotion["promoteMembergroupid"] ), "memberid=" . $TSUE["TSUE_Database"]->escape($memberID));
                    if( $TSUE["TSUE_Database"]->affected_rows() ) 
                    {
                        alert_member($memberID, 0, "", "promotions", 0, "promoted", $Promotion["promoteMembergroupid"]);
                    }

                }

            }
        }

    }

    if( $Promotion["minRatio"] ) 
    {
        $findMembers = $TSUE["TSUE_Database"]->query("SELECT pr.memberid, pr.beforeMembergroupid, m.membername, p.uploaded, p.downloaded FROM tsue_member_promotions pr INNER JOIN tsue_members m USING(memberid) INNER JOIN tsue_member_profile p USING(memberid) WHERE m.membergroupid = " . $TSUE["TSUE_Database"]->escape($Promotion["promoteMembergroupid"]));
        if( $TSUE["TSUE_Database"]->num_rows($findMembers) ) 
        {
            $demoteCache = array(  );
            while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($findMembers) ) 
            {
                if( member_ratio($Member["uploaded"], $Member["downloaded"], true) < $Promotion["minRatio"] ) 
                {
                    $demoteCache[$Member["memberid"]] = array( "membergroupid" => $Member["beforeMembergroupid"] );
                }

            }
            if( $demoteCache ) 
            {
                foreach( $demoteCache as $memberID => $buildQuery ) 
                {
                    $TSUE["TSUE_Database"]->delete("tsue_member_promotions", "memberid=" . $TSUE["TSUE_Database"]->escape($memberID));
                    if( $TSUE["TSUE_Database"]->affected_rows() ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_members", $buildQuery, "memberid=" . $TSUE["TSUE_Database"]->escape($memberID));
                        if( $TSUE["TSUE_Database"]->affected_rows() ) 
                        {
                            alert_member($memberID, 0, "", "promotions", 0, "demoted", $buildQuery["membergroupid"]);
                        }

                    }

                }
            }

        }

    }

}

function deleteDeadTorrents()
{
    global $TSUE;
    $days_retain_dead_torrents = intval(getSetting("global_settings", "days_retain_dead_torrents"));
    if( !$days_retain_dead_torrents ) 
    {
        return NULL;
    }

    $timeOut = TIMENOW - $days_retain_dead_torrents * 86400;
    $Torrents = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE tid, name, owner, awaitingModeration FROM tsue_torrents WHERE `mtime` != 0 AND `mtime` < " . $TSUE["TSUE_Database"]->escape($timeOut) . " AND seeders = 0");
    if( $TSUE["TSUE_Database"]->num_rows($Torrents) ) 
    {
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($Torrents) ) 
        {
            $deletePhrase = get_phrase("torrent_has_been_deleted", strip_tags($Torrent["name"]), $Torrent["tid"], get_phrase("dashboard_cron_entries_alt"), get_phrase("days_retain_dead_torrents"));
            deleteTorrent($Torrent["tid"], $deletePhrase, $Torrent["owner"], $Torrent["awaitingModeration"]);
        }
    }

}



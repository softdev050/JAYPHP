<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function checkWarnHitRun()
{
    global $TSUE;
    $TSUE["TSUE_Settings"]->loadSettings("hitrun_settings");
    if( !$TSUE["TSUE_Settings"]->settings["hitrun_settings"]["active"] ) 
    {
        return NULL;
    }

    if( $TSUE["TSUE_Settings"]->settings["xbt"]["active"] ) 
    {
        return checkWarnXBTHitRun();
    }

    $Rules = array(  );
    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["skipMembergroups"] ) 
    {
        $Rules[] = "m.membergroupid NOT IN (" . $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["skipMembergroups"] . ")";
    }

    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["skipDownloadedTorrentsBefore"] ) 
    {
    }

    $WHERE = "";
    if( $Rules ) 
    {
        $WHERE = " WHERE " . implode(" AND ", $Rules);
    }

    $Peers = $TSUE["TSUE_Database"]->query("\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSELECT SQL_NO_CACHE \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tp.pid, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSUM(p.total_uploaded) AS total_uploaded, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tMAX(p.last_updated) AS last_updated, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSUM(p.announced) AS announced, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tMAX(p.active) AS active, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tMAX(p.left) AS `left`, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tMAX(p.isWarned) AS isWarned, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tSUM(p.total_downloaded) AS total_downloaded, \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tt.size, t.options, m.memberid \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tFROM tsue_torrents_peers p \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tINNER JOIN tsue_torrents t USING(tid) \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tINNER JOIN tsue_members m USING(memberid)\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t" . $WHERE . " \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tGROUP BY p.tid, p.memberid \r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tORDER BY p.last_updated DESC\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t");
    if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
    {
        $increaseMemberHitRuns = array(  );
        $updatePeersWarned = array(  );
        while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
        {
            $torrentOptions = unserialize($Peer["options"]);
            if( isset($torrentOptions["hitRunRatio"]) && $torrentOptions["hitRunRatio"] ) 
            {
                $passed = false;
                if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["dayTolerance"] && TIMENOW - $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["dayTolerance"] * 86400 < $Peer["last_updated"] ) 
                {
                    $passed = true;
                }
                else
                {
                    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["announceLimit"] && $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["announceLimit"] <= $Peer["announced"] ) 
                    {
                        $passed = true;
                    }
                    else
                    {
                        if( $Peer["active"] ) 
                        {
                            $passed = true;
                        }
                        else
                        {
                            if( $Peer["left"] ) 
                            {
                                $passed = true;
                            }
                            else
                            {
                                if( $Peer["isWarned"] ) 
                                {
                                    $passed = true;
                                }
                                else
                                {
                                    if( !$Peer["total_downloaded"] ) 
                                    {
                                        $passed = true;
                                    }

                                }

                            }

                        }

                    }

                }

                if( !$passed ) 
                {
                    $torrentOptions["hitRunRatio"] = 0 + $torrentOptions["hitRunRatio"];
                    if( member_ratio($Peer["total_uploaded"], $Peer["size"], true) < $torrentOptions["hitRunRatio"] ) 
                    {
                        $increaseMemberHitRuns[] = $Peer["memberid"];
                        $updatePeersWarned[] = $Peer["pid"];
                    }

                }

            }

        }
        if( $increaseMemberHitRuns ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "hitRuns" => array( "escape" => 0, "value" => "hitRuns+1" ) ), "memberid IN (" . implode(",", $increaseMemberHitRuns) . ")");
        }

        if( $updatePeersWarned ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 1 ), "pid IN (" . implode(",", $updatePeersWarned) . ")");
        }

    }

    checkDeWarnHitRun();
    checkBanHitRun();
}

function checkDeWarnHitRun()
{
    global $TSUE;
    $Rules = array(  );
    $Rules[] = "p.isWarned =1";
    $WHERE = implode(" AND ", $Rules);
    $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.pid, SUM(p.total_uploaded) AS total_uploaded, t.size, t.options, m.memberid FROM tsue_torrents_peers p INNER JOIN tsue_torrents t USING(tid) INNER JOIN tsue_members m USING(memberid) WHERE " . $WHERE . " GROUP BY p.tid, p.memberid ORDER BY p.last_updated DESC");
    if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
    {
        $decreaseMemberHitRuns = array(  );
        $updatePeersWarned = array(  );
        while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
        {
            $torrentOptions = unserialize($Peer["options"]);
            if( isset($torrentOptions["hitRunRatio"]) && $torrentOptions["hitRunRatio"] ) 
            {
                $torrentOptions["hitRunRatio"] = 0 + $torrentOptions["hitRunRatio"];
                if( $torrentOptions["hitRunRatio"] <= member_ratio($Peer["total_uploaded"], $Peer["size"], true) ) 
                {
                    $decreaseMemberHitRuns[] = $Peer["memberid"];
                    $updatePeersWarned[] = $Peer["pid"];
                }

            }

        }
        if( $decreaseMemberHitRuns ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "hitRuns" => array( "escape" => 0, "value" => "IF(hitRuns>1,hitRuns-1,0)" ) ), "memberid IN (" . implode(",", $decreaseMemberHitRuns) . ")");
        }

        if( $updatePeersWarned ) 
        {
            $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 0 ), "pid IN (" . implode(",", $updatePeersWarned) . ")");
        }

    }

}

function checkWarnXBTHitRun()
{
    global $TSUE;
    $Rules = array(  );
    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["dayTolerance"] ) 
    {
        $Rules[] = "p.mtime < " . (TIMENOW - $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["dayTolerance"] * 24 * 60 * 60);
    }

    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["announceLimit"] ) 
    {
        $Rules[] = "p.announced < " . $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["announceLimit"];
    }

    if( $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["skipMembergroups"] ) 
    {
        $Rules[] = "m.membergroupid NOT IN (" . $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["skipMembergroups"] . ")";
    }

    $Rules[] = "p.active = 0";
    $Rules[] = "p.left = 0";
    $Rules[] = "p.isWarned =0";
    $Rules[] = "p.downloaded > 0";
    $WHERE = implode(" AND ", $Rules);
    $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.fid, p.uploaded, t.size, t.options, m.memberid FROM xbt_files_users p INNER JOIN tsue_torrents t ON (p.fid=t.tid) INNER JOIN tsue_members m ON (p.uid=m.memberid) WHERE " . $WHERE);
    if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
    {
        while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
        {
            $torrentOptions = unserialize($Peer["options"]);
            if( isset($torrentOptions["hitRunRatio"]) && $torrentOptions["hitRunRatio"] ) 
            {
                $torrentOptions["hitRunRatio"] = 0 + $torrentOptions["hitRunRatio"];
                if( member_ratio($Peer["uploaded"], $Peer["size"], true) < $torrentOptions["hitRunRatio"] ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "hitRuns" => array( "escape" => 0, "value" => "hitRuns+1" ) ), "memberid =" . $TSUE["TSUE_Database"]->escape($Peer["memberid"]));
                    $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 1 ), "fid=" . $TSUE["TSUE_Database"]->escape($Peer["fid"]) . " AND uid=" . $TSUE["TSUE_Database"]->escape($Peer["memberid"]));
                }

            }

        }
    }

    checkDeWarnXBTHitRun();
    checkBanHitRun();
}

function checkDeWarnXBTHitRun()
{
    global $TSUE;
    $Rules = array(  );
    $Rules[] = "p.isWarned =1";
    $WHERE = implode(" AND ", $Rules);
    $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.fid, p.uploaded, t.size, t.options, m.memberid FROM xbt_files_users p INNER JOIN tsue_torrents t ON (p.fid=t.tid) INNER JOIN tsue_members m ON (p.uid=m.memberid) WHERE " . $WHERE);
    if( $TSUE["TSUE_Database"]->num_rows($Peers) ) 
    {
        while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
        {
            $torrentOptions = unserialize($Peer["options"]);
            if( isset($torrentOptions["hitRunRatio"]) && $torrentOptions["hitRunRatio"] ) 
            {
                $torrentOptions["hitRunRatio"] = 0 + $torrentOptions["hitRunRatio"];
                if( $torrentOptions["hitRunRatio"] <= member_ratio($Peer["uploaded"], $Peer["size"], true) ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "hitRuns" => array( "escape" => 0, "value" => "IF(hitRuns>1,hitRuns-1,0)" ) ), "memberid =" . $TSUE["TSUE_Database"]->escape($Peer["memberid"]));
                    $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 0 ), "fid=" . $TSUE["TSUE_Database"]->escape($Peer["fid"]) . " AND uid=" . $TSUE["TSUE_Database"]->escape($Peer["memberid"]));
                }

            }

        }
    }

}

function checkBanHitRun()
{
    global $TSUE;
    $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["maxWarns"] = intval($TSUE["TSUE_Settings"]->settings["hitrun_settings"]["maxWarns"]);
    if( !$TSUE["TSUE_Settings"]->settings["hitrun_settings"]["maxWarns"] ) 
    {
        return NULL;
    }

    $fetchMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.memberid, m.membername, b.memberid as isBanned FROM tsue_member_profile p INNER JOIN tsue_members m USING(memberid) LEFT JOIN tsue_member_bans b ON(m.memberid=b.memberid) WHERE p.hitRuns >= " . $TSUE["TSUE_Settings"]->settings["hitrun_settings"]["maxWarns"] . " AND b.memberid IS NULL");
    if( $TSUE["TSUE_Database"]->num_rows($fetchMembers) ) 
    {
        $banMembers = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($fetchMembers) ) 
        {
            if( !$Member["isBanned"] ) 
            {
                $Output = get_phrase("banned_member_has_been_banned", $Member["membername"], $TSUE["TSUE_Language"]->phrase["dashboard_options_hitrun"]);
                $banMember = array( "memberid" => $Member["memberid"], "banned_by" => 0, "ban_date" => TIMENOW, "end_date" => 0, "reason" => $Output );
                $TSUE["TSUE_Database"]->replace("tsue_member_bans", $banMember);
                logAction($Output);
            }

        }
    }

}



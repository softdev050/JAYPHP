<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function checkWarn()
{
    global $TSUE;
    if( !$TSUE["TSUE_Settings"]->settings["auto_warning"]["active"] || !$TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"] ) 
    {
        return NULL;
    }

    $alreadyWarned = array(  );
    $warnCache = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid FROM tsue_auto_warning");
    if( $warnCache ) 
    {
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($warnCache) ) 
        {
            $alreadyWarned[] = $Member["memberid"];
        }
    }

    $Rules = array(  );
    if( $TSUE["TSUE_Settings"]->settings["auto_warning"]["skipMembergroups"] ) 
    {
        $Rules[] = "m.membergroupid NOT IN (" . $TSUE["TSUE_Settings"]->settings["auto_warning"]["skipMembergroups"] . ")";
    }

    if( $alreadyWarned ) 
    {
        removeExpiredWarns();
        banExpiredWarns();
        $Rules[] = "m.memberid NOT IN (" . implode(",", $alreadyWarned) . ")";
        unset($alreadyWarned);
    }

    $Rules[] = "(p.downloaded > 0 AND (p.uploaded/p.downloaded) < " . $TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"] . ")";
    $WHERE = implode(" AND ", $Rules);
    $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid FROM tsue_members m INNER JOIN tsue_member_profile p USING(memberid) WHERE " . $WHERE);
    if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
    {
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            $TSUE["TSUE_Database"]->replace("tsue_auto_warning", array( "memberid" => $Member["memberid"], "warned" => TIMENOW ));
        }
    }

}

function removeExpiredWarns()
{
    global $TSUE;
    $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE a.memberid FROM tsue_auto_warning a INNER JOIN tsue_member_profile p USING(memberid) WHERE (p.uploaded/p.downloaded) >= " . $TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"]);
    if( $Members ) 
    {
        $removeWarn = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            $removeWarn[] = $Member["memberid"];
        }
        if( $removeWarn ) 
        {
            $TSUE["TSUE_Database"]->delete("tsue_auto_warning", "memberid IN (" . implode(",", $removeWarn) . ")");
        }

    }

}

function banExpiredWarns()
{
    global $TSUE;
    $timeOut = TIMENOW - $TSUE["TSUE_Settings"]->settings["auto_warning"]["warn_length"] * 24 * 60 * 60;
    $Members = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE a.memberid, m.membername FROM tsue_auto_warning a INNER JOIN tsue_members m USING(memberid) INNER JOIN tsue_member_profile p USING(memberid) WHERE a.warned < " . $timeOut . " AND (p.uploaded/p.downloaded) < " . $TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"]);
    if( $Members ) 
    {
        $bannedMembers = array(  );
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            $bannedMembers[] = $Member["memberid"];
            $Output = get_phrase("member_x_has_been_banned_due_bad_ratio", $Member["membername"]);
            $banMember = array( "memberid" => $Member["memberid"], "banned_by" => 0, "ban_date" => TIMENOW, "end_date" => 0, "reason" => $Output );
            $TSUE["TSUE_Database"]->replace("tsue_member_bans", $banMember);
            logAction($Output);
        }
        if( $bannedMembers ) 
        {
            $TSUE["TSUE_Database"]->delete("tsue_auto_warning", "memberid IN (" . implode(",", $bannedMembers) . ")");
        }

    }

}



<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function checkForInactiveUploaders()
{
    global $TSUE;
    $TSUE["TSUE_Settings"]->loadSettings("uploader_inactivity");
    $Settings = array( "membergroups" => trim(getSetting("uploader_inactivity", "membergroups")), "demote_to" => intval(getSetting("uploader_inactivity", "demote_to")), "criterias_torrents" => intval(getSetting("uploader_inactivity", "criterias_torrents")), "criterias_days" => intval(getSetting("uploader_inactivity", "criterias_days")) );
    if( !$Settings["membergroups"] || !$Settings["demote_to"] || !$Settings["criterias_torrents"] || !$Settings["criterias_days"] ) 
    {
        return NULL;
    }

    $demoteMembers = array(  );
    $minUploadDate = TIMENOW - $Settings["criterias_days"] * 86400;
    $Uploaders = $TSUE["TSUE_Database"]->query("SELECT m.memberid, MAX(t.added) AS lastUploadDate FROM tsue_members m INNER JOIN tsue_torrents t ON(m.memberid=t.owner) WHERE m.membergroupid IN (" . $Settings["membergroups"] . ") GROUP BY m.memberid");
    if( $TSUE["TSUE_Database"]->num_rows($Uploaders) ) 
    {
        while( $Uploader = $TSUE["TSUE_Database"]->fetch_assoc($Uploaders) ) 
        {
            if( $Uploader["lastUploadDate"] < $minUploadDate ) 
            {
                $demoteMembers[] = $Uploader["memberid"];
            }

        }
    }

    $Uploaders = $TSUE["TSUE_Database"]->query("SELECT m.memberid, COUNT(t.tid) as totalUploads FROM tsue_members m INNER JOIN tsue_torrents t ON(m.memberid=t.owner) WHERE m.membergroupid IN (" . $Settings["membergroups"] . ") AND " . (($demoteMembers ? "m.memberid NOT IN(" . implode(",", $demoteMembers) . ") AND " : "")) . "t.added >= " . $minUploadDate . " GROUP BY m.memberid");
    if( $TSUE["TSUE_Database"]->num_rows($Uploaders) ) 
    {
        while( $Uploader = $TSUE["TSUE_Database"]->fetch_assoc($Uploaders) ) 
        {
            if( $Uploader["totalUploads"] < $Settings["criterias_torrents"] ) 
            {
                $demoteMembers[] = $Uploader["memberid"];
            }

        }
    }

    if( $demoteMembers ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_members", array( "membergroupid" => $Settings["demote_to"] ), "memberid IN (" . implode(",", $demoteMembers) . ")");
        foreach( $demoteMembers as $memberID ) 
        {
            alert_member($memberID, 0, "", "promotions", 0, "uploader_demoted", $Settings["demote_to"]);
        }
    }

}



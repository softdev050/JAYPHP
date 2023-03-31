<?php 
if( !defined("SCRIPTNAME") || defined("SCRIPTNAME") && SCRIPTNAME != "cron.php" ) 
{
    exit();
}

function giveAutomaticInvites()
{
    global $TSUE;
    $TSUE["TSUE_Settings"]->loadSettings("automatic_invites");
    $Settings = array( "membergroups" => trim(getSetting("automatic_invites", "membergroups")), "amount" => intval(getSetting("automatic_invites", "amount")) );
    if( !$Settings["membergroups"] || !$Settings["amount"] ) 
    {
        return NULL;
    }

    $updateMembers = array(  );
    $Members = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . $Settings["membergroups"] . ")");
    if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
    {
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
        {
            $updateMembers[] = $Member["memberid"];
        }
    }

    if( $updateMembers ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "invites_left" => array( "escape" => 0, "value" => "invites_left+" . $Settings["amount"] ) ), "memberid IN (" . implode(",", $updateMembers) . ")");
        foreach( $updateMembers as $memberID ) 
        {
            alert_member($memberID, 0, "", "promotions", 0, "received_automatic_invite", $Settings["amount"]);
        }
        logAction(get_phrase("automatic_invites_cron_log", $Settings["membergroups"], $Settings["amount"]), 2);
    }

}



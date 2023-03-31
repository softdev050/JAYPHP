<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "confirmaccount.php");
require("./library/init/init.php");
globalize("get", array( "hash" => "TRIM", "memberid" => "INT" ));
$Page_Title = get_phrase("confirm_account_title");
if( strlen($hash) != 16 || !$memberid ) 
{
    $Error[] = get_phrase("confirm_account_could_not_confirm");
}
else
{
    $membersAwaitingEmailConfirmationGroup = is_member_of("awaitingemailconfirmation", true);
    $Confirmation = $TSUE["TSUE_Database"]->query_result("SELECT c.memberid, c.membergroupid, c.hash, m.membername, m.email FROM tsue_member_confirmation c INNER JOIN tsue_members m USING(memberid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.type = 'confirm_account' AND m.membergroupid = " . $membersAwaitingEmailConfirmationGroup);
    if( !$Confirmation ) 
    {
        $Error[] = get_phrase("confirm_account_could_not_confirm");
    }
    else
    {
        if( $Confirmation["memberid"] != $memberid || $Confirmation["hash"] != $hash ) 
        {
            $Error[] = get_phrase("confirm_account_could_not_confirm");
        }
        else
        {
            $TSUE["TSUE_Database"]->query("DELETE FROM tsue_member_confirmation WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND type = 'confirm_account'");
            $BuildQuery = array( "membergroupid" => ($Confirmation["membergroupid"] && $Confirmation["membergroupid"] != $membersAwaitingEmailConfirmationGroup ? $Confirmation["membergroupid"] : is_member_of("registeredusers", true)) );
            if( $TSUE["TSUE_Database"]->update("tsue_members", $BuildQuery, "memberid = " . $TSUE["TSUE_Database"]->escape($memberid)) ) 
            {
                if( !$Confirmation["membergroupid"] ) 
                {
                    shoutboxAnnouncement(array( "new_member", $memberid, $Confirmation["membername"] ));
                    ircAnnouncement("new_member", $memberid, $Confirmation["membername"]);
                }

                show_done(get_phrase("confirm_account_confirmed"), $Page_Title);
            }
            else
            {
                $Error[] = get_phrase("database_error");
            }

        }

    }

}

show_error($Error, $Page_Title);


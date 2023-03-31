<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function prepareInviteList($memberid)
{
    global $TSUE;
    $invitedFriends = "";
    $count = 0;
    $searchInvitedFriends = $TSUE["TSUE_Database"]->query("SELECT i.*, m.membername, g.groupstyle FROM tsue_invites i LEFT JOIN tsue_members m ON (i.receiver_memberid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE i.sender_memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY i.status ASC, i.send_date DESC");
    if( $TSUE["TSUE_Database"]->num_rows($searchInvitedFriends) ) 
    {
        while( $invite = $TSUE["TSUE_Database"]->fetch_assoc($searchInvitedFriends) ) 
        {
            $inviteDeleteLink = "";
            if( $invite["status"] == "pending" && $memberid == $TSUE["TSUE_Member"]->info["memberid"] && SCRIPTNAME == "membercp.php" ) 
            {
                eval("\$inviteDeleteLink = \"" . $TSUE["TSUE_Template"]->LoadTemplate("membercp_invite_deletelink") . "\";");
            }

            if( $invite["status"] == "completed" && $invite["receiver_memberid"] ) 
            {
                $_memberid = $invite["receiver_memberid"];
                $_membername = getMembername($invite["membername"], $invite["groupstyle"]);
                eval("\$invite['name'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            }
            else
            {
                $invite["name"] = strip_tags($invite["name"]);
            }

            $invite["email"] = strip_tags($invite["email"]);
            $invite["send_date"] = convert_relative_time($invite["send_date"]);
            $invite["status"] = @get_phrase("invite_status_" . $invite["status"]);
            $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
            eval("\$invitedFriends .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("membercp_invited_friends_rows") . "\";");
            $count++;
        }
    }

    eval("\$membercp_invited_friends_table = \"" . $TSUE["TSUE_Template"]->LoadTemplate("membercp_invited_friends_table") . "\";");
    return $membercp_invited_friends_table;
}



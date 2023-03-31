<?php 
define("SCRIPTNAME", "profile.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

switch( $TSUE["action"] ) 
{
    case "recent_activity":
        globalize("post", array( "memberid" => "INT" ));
        require_once(REALPATH . "library/functions/functions_memberHistory.php");
        prepareRecentActivity($memberid);
        break;
    case "following":
    case "followers":
        globalize("post", array( "memberid" => "INT" ));
        if( $TSUE["action"] == "following" ) 
        {
            $followQuery = $TSUE["TSUE_Database"]->query("SELECT f.follow_memberid AS memberid, m.membername, m.gender, g.groupstyle FROM tsue_member_follow f INNER JOIN tsue_members m ON (f.follow_memberid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE f.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY f.follow_date DESC");
        }
        else
        {
            $followQuery = $TSUE["TSUE_Database"]->query("SELECT f.memberid AS memberid, m.membername, m.gender, g.groupstyle FROM tsue_member_follow f INNER JOIN tsue_members m ON (f.memberid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE f.follow_memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY f.follow_date DESC");
        }

        if( $TSUE["TSUE_Database"]->num_rows($followQuery) ) 
        {
            $followList = "";
            while( $Follow = $TSUE["TSUE_Database"]->fetch_assoc($followQuery) ) 
            {
                $_memberid = $Follow["memberid"];
                $_alt = $Follow["membername"];
                $_avatar = get_member_avatar($Follow["memberid"], $Follow["gender"], "s");
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                eval("\$followList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("following") . "\";");
            }
            ajax_message($followList);
        }
        else
        {
            ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        }

}


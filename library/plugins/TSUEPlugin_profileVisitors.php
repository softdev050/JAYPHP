<?php 
function TSUEPlugin_profileVisitors($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $memberid = (isset($_GET["memberid"]) ? intval($_GET["memberid"]) : $TSUE["TSUE_Member"]->info["memberid"]);
    $isToggled = isToggled("profileVisitors");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !is_member_of("unregistered") && $TSUE["TSUE_Member"]->info["memberid"] != $memberid ) 
    {
        $buildQuery = array( "memberid" => $memberid, "visitorid" => $TSUE["TSUE_Member"]->info["memberid"], "dateline" => TIMENOW );
        $TSUE["TSUE_Database"]->replace("tsue_member_profile_visitors", $buildQuery, true);
    }

    $maxProfileVisitors = getPluginOption($pluginOptions, "max_profile_visitors", 6);
    $query = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE memberid FROM tsue_member_profile_visitors WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " GROUP BY memberid HAVING COUNT(*) > " . $maxProfileVisitors);
    if( $TSUE["TSUE_Database"]->num_rows($query) ) 
    {
        while( $member = $TSUE["TSUE_Database"]->fetch_assoc($query) ) 
        {
            $QQuery = $TSUE["TSUE_Database"]->query("SELECT memberid, visitorid, dateline FROM tsue_member_profile_visitors WHERE memberid = " . $TSUE["TSUE_Database"]->escape($member["memberid"]) . " ORDER BY dateline DESC LIMIT " . $maxProfileVisitors . ", 1");
            if( $TSUE["TSUE_Database"]->num_rows($QQuery) ) 
            {
                while( $delete = $TSUE["TSUE_Database"]->fetch_assoc($QQuery) ) 
                {
                    $TSUE["TSUE_Database"]->delete("tsue_member_profile_visitors", "dateline = " . $TSUE["TSUE_Database"]->escape($delete["dateline"]) . " AND memberid = " . $TSUE["TSUE_Database"]->escape($delete["memberid"]) . " AND visitorid = " . $TSUE["TSUE_Database"]->escape($delete["visitorid"]), true);
                }
            }

        }
    }

    $profileVisitors = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE v.visitorid, v.dateline, m.membername, m.gender, g.groupstyle FROM tsue_member_profile_visitors v INNER JOIN tsue_members m ON (v.visitorid=m.memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) WHERE v.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " ORDER BY v.dateline DESC LIMIT " . $maxProfileVisitors);
    if( $TSUE["TSUE_Database"]->num_rows($profileVisitors) ) 
    {
        $TSUEPlugin_profileVisitors_avatars = "";
        while( $Visitor = $TSUE["TSUE_Database"]->fetch_assoc($profileVisitors) ) 
        {
            $dateline = get_phrase("visit_date", convert_relative_time($Visitor["dateline"], false));
            $imageText = html_clean($Visitor["membername"] . " - " . $dateline);
            $_memberid = $Visitor["visitorid"];
            $_avatar = get_member_avatar($Visitor["visitorid"], $Visitor["gender"], "m");
            $memberName = getMembername($Visitor["membername"], $Visitor["groupstyle"]);
            eval("\$TSUEPlugin_profileVisitors_avatars .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_profileVisitors_avatars") . "\";");
        }
        eval("\$TSUEPlugin_profileVisitors = \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_profileVisitors") . "\";");
        return $TSUEPlugin_profileVisitors;
    }

}



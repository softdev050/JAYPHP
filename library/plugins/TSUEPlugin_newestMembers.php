<?php 
function TSUEPlugin_newestMembers($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_newestMembers_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("newestMembers_avatars");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($TSUEPlugin_newestMembers_avatars = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $TSUEPlugin_newestMembers_avatars = "";
        $newestMembers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE m.memberid, m.membername, m.joindate, m.gender, g.groupstyle FROM tsue_members m LEFT JOIN tsue_membergroups g USING(membergroupid) ORDER BY m.joindate DESC LIMIT " . getPluginOption($pluginOptions, "max_new_members", 6));
        while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($newestMembers) ) 
        {
            $MemberSince = get_phrase("memberinfo_membersince", convert_time($Member["joindate"]));
            $imageText = html_clean($Member["membername"] . " - " . $MemberSince);
            $_memberid = $Member["memberid"];
            $_avatar = get_member_avatar($Member["memberid"], $Member["gender"], "m");
            $memberName = getMembername($Member["membername"], $Member["groupstyle"]);
            eval("\$TSUEPlugin_newestMembers_avatars .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_newestMembers_avatars") . "\";");
        }
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $TSUEPlugin_newestMembers_avatars);
        if( defined("IS_AJAX") ) 
        {
            return $TSUEPlugin_newestMembers_avatars;
        }

    }

    eval("\$TSUEPlugin_newestMembers = \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_newestMembers") . "\";");
    return $TSUEPlugin_newestMembers;
}



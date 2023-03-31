<?php 
function TSUEPlugin_topUploaders($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_topUploaders_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("top_uploaders_list");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($top_uploaders_list = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $topUploaders = $TSUE["TSUE_Database"]->query("SELECT COUNT(t.tid) AS totalUploads, m.memberid, m.membername, m.gender, g.groupname, g.groupstyle, profile.custom_title, profile.country \r\n\t\tFROM tsue_torrents t \r\n\t\tINNER JOIN tsue_members m ON (t.owner=m.memberid)\r\n\t\tINNER JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tINNER JOIN tsue_member_profile profile USING(memberid) \r\n\t\tGROUP BY m.memberid ORDER BY totalUploads DESC LIMIT " . getPluginOption($pluginOptions, "max_uploaders", 10));
        if( $TSUE["TSUE_Database"]->num_rows($topUploaders) ) 
        {
            $top_uploaders_list = "";
            while( $Uploader = $TSUE["TSUE_Database"]->fetch_assoc($topUploaders) ) 
            {
                $_memberid = $Uploader["memberid"];
                $_avatar = get_member_avatar($Uploader["memberid"], $Uploader["gender"], "s");
                $memberName = getMembername($Uploader["membername"], $Uploader["groupstyle"]) . " (" . number_format($Uploader["totalUploads"]) . ")";
                $groupname = getGroupname($Uploader);
                $countryName = $Uploader["country"];
                $countryFlag = countryFlag($Uploader["country"]);
                eval("\$top_uploaders_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top_uploaders_list") . "\";");
            }
            $TSUE["TSUE_Cache"]->saveCache($cacheName, $top_uploaders_list);
            if( defined("IS_AJAX") ) 
            {
                return $top_uploaders_list;
            }

        }
        else
        {
            return NULL;
        }

    }

    eval("\$TSUEPlugin_topUploaders = \"" . $TSUE["TSUE_Template"]->LoadTemplate("top_uploaders") . "\";");
    return $TSUEPlugin_topUploaders;
}



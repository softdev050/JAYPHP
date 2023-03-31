<?php 
function TSUEPlugin_recentRequests($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_recentRequests_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("recentRequests");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($recentRequestList = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        $Requests = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE r.tid, r.memberid, r.added, r.title, m.membername, m.gender, g.groupstyle FROM tsue_requests r LEFT JOIN tsue_members m USING(memberid) LEFT JOIN tsue_membergroups g USING(membergroupid) ORDER BY r.added DESC LIMIT " . getPluginOption($pluginOptions, "max_recent_requests", 5));
        if( $TSUE["TSUE_Database"]->num_rows($Requests) ) 
        {
            $recentRequestList = "";
            while( $Request = $TSUE["TSUE_Database"]->fetch_assoc($Requests) ) 
            {
                $statusPhrase = get_phrase("request_" . (($Request["tid"] ? "filled" : "pending")));
                $statusClass = ($Request["tid"] ? "requestFilledBox" : "requestPendingBox");
                eval("\$requestStatus = \"" . $TSUE["TSUE_Template"]->LoadTemplate("request_status") . "\";");
                $_memberid = $Request["memberid"];
                $_membername = getMembername($Request["membername"], $Request["groupstyle"]);
                eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $request_by_x = get_phrase("request_by_x", $member_info_link, convert_relative_time($Request["added"]));
                $requestTitle = strip_tags($Request["title"]);
                eval("\$recentRequestList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_request_list") . "\";");
            }
            $TSUE["TSUE_Cache"]->saveCache($cacheName, $recentRequestList);
        }

    }

    eval("\$TSUEPlugin_recentRequests = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_recent_requests") . "\";");
    return $TSUEPlugin_recentRequests;
}



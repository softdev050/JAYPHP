<?php 
function TSUEPlugin_onlineMembers($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $DateCut = TIMENOW - $TSUE["TSUE_Settings"]->settings["global_settings"]["website_timeout"] * 60;
    $OnlineMembersQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE s.memberid, m.membername, m.visible, g.groupstyle \r\n\tFROM tsue_session s \r\n\tLEFT JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE s.date > " . $DateCut . " \r\n\tORDER BY s.date DESC");
    $OnlineNow = $TSUE["TSUE_Database"]->num_rows($OnlineMembersQuery);
    $OnlineMembers = $OnlineGuests = $totalMemberPassed = 0;
    $ShowMemberNames = "";
    $max_online_limit = getPluginOption($pluginOptions, "max_online_limit", 25);
    if( $OnlineNow ) 
    {
        while( $OM = $TSUE["TSUE_Database"]->fetch_assoc($OnlineMembersQuery) ) 
        {
            if( $OM["membername"] ) 
            {
                if( !$OM["visible"] && !has_permission("canview_invisible_members") && $OM["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
                {
                    $OnlineGuests++;
                }
                else
                {
                    $totalMemberPassed++;
                    if( $totalMemberPassed <= $max_online_limit ) 
                    {
                        $_memberid = $OM["memberid"];
                        $_membername = getMembername(strip_tags($OM["membername"]), $OM["groupstyle"]);
                        eval("\$ShowMemberNames[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    }

                    $OnlineMembers++;
                }

            }
            else
            {
                $OnlineGuests++;
            }

        }
    }

    if( $ShowMemberNames && is_array($ShowMemberNames) ) 
    {
        $ShowMemberNames = implode(", ", $ShowMemberNames);
        if( $max_online_limit < $OnlineMembers ) 
        {
            $ShowMemberNames .= "<br />" . get_phrase("online_members_and_x_more", number_format($OnlineMembers - $max_online_limit));
        }

    }

    $OnlineMembersCount = get_phrase("online_members_text", friendly_number_format($OnlineNow), friendly_number_format($OnlineMembers), friendly_number_format($OnlineGuests));
    $isToggled = isToggled("membersOnlineNow");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( defined("IS_AJAX") ) 
    {
        return $ShowMemberNames . "<br />" . $OnlineMembersCount;
    }

    $showBoxes = getPluginOption($pluginOptions, "show_boxes") == 1;
    $buildMembergroupsStyles = buildMembergroupsStyles($showBoxes, ($showBoxes ? " " : ($pluginPosition == "right" ? "<br />" : " | ")));
    eval("\$TSUEPlugin_onlineMembers = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_online_members") . "\";");
    return $TSUEPlugin_onlineMembers;
}



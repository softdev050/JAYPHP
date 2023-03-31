<?php 
function TSUEPlugin_staffOnlineNow($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $showOnStaff = $TSUE["TSUE_Database"]->query("SELECT membergroupid FROM tsue_membergroups WHERE showOnStaff = 1");
    if( !$TSUE["TSUE_Database"]->num_rows($showOnStaff) ) 
    {
        return NULL;
    }

    $mids = array(  );
    while( $M = $TSUE["TSUE_Database"]->fetch_assoc($showOnStaff) ) 
    {
        $mids[] = $M["membergroupid"];
    }
    $mids = "m.membergroupid IN (" . implode(",", $mids) . ")";
    $staffQuery = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membername, m.gender, m.visible, profile.custom_title, profile.country, g.groupname, g.groupstyle, s.date \r\n\tFROM tsue_members m \r\n\tINNER JOIN tsue_member_profile profile USING(memberid) \r\n\tINNER JOIN tsue_membergroups g USING(membergroupid) \r\n\tINNER JOIN tsue_session s ON (m.memberid=s.memberid) \r\n\tWHERE " . $mids . " GROUP BY m.memberid ORDER BY g.sort ASC, m.lastactivity DESC");
    if( $TSUE["TSUE_Database"]->num_rows($staffQuery) ) 
    {
        $DateCut = TIMENOW - 300;
        $staff_online_now_list = "";
        while( $Staff = $TSUE["TSUE_Database"]->fetch_assoc($staffQuery) ) 
        {
            if( $DateCut < $Staff["date"] ) 
            {
                $_memberid = $Staff["memberid"];
                $_avatar = get_member_avatar($Staff["memberid"], $Staff["gender"], "s");
                $memberName = getMembername($Staff["membername"], $Staff["groupstyle"]);
                $groupname = getGroupname($Staff);
                $countryName = $Staff["country"];
                $countryFlag = countryFlag($Staff["country"]);
                eval("\$staff_online_now_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_online_now_list") . "\";");
            }

        }
        $isToggled = isToggled("staffOnlineNowList");
        $class = (!$isToggled ? "" : "hidden");
        $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
        if( defined("IS_AJAX") ) 
        {
            return $staff_online_now_list;
        }

        if( $staff_online_now_list ) 
        {
            eval("\$TSUEPlugin_staffOnlineNow = \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_online_now") . "\";");
            return $TSUEPlugin_staffOnlineNow;
        }

    }

}



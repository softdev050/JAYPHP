<?php 
function TSUEPlugin_topDonors($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_topDonors_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("top_donors_list");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($top_donors_list = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $minStartDate = strtotime(date("01-m-Y"));
        $topDonors = $TSUE["TSUE_Database"]->query("SELECT COUNT(p.promotion_id) AS totalDonations, p.memberid, m.membername, m.gender, g.groupname, g.groupstyle, profile.custom_title, profile.country \r\n\t\tFROM tsue_member_upgrades_promotions p \r\n\t\tINNER JOIN tsue_members m USING(memberid) \r\n\t\tINNER JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tINNER JOIN tsue_member_profile profile USING(memberid) \r\n\t\tWHERE p.active = 1 AND p.start_date >= " . $minStartDate . " GROUP BY p.memberid ORDER BY totalDonations DESC LIMIT " . getPluginOption($pluginOptions, "max_donors", 5));
        if( $TSUE["TSUE_Database"]->num_rows($topDonors) ) 
        {
            $top_donors_list = "";
            while( $Donor = $TSUE["TSUE_Database"]->fetch_assoc($topDonors) ) 
            {
                $_memberid = $Donor["memberid"];
                $_avatar = get_member_avatar($Donor["memberid"], $Donor["gender"], "s");
                $memberName = getMembername($Donor["membername"], $Donor["groupstyle"]);
                $groupname = getGroupname($Donor);
                $countryName = $Donor["country"];
                $countryFlag = countryFlag($Donor["country"]);
                eval("\$top_donors_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("top_donors_list") . "\";");
            }
            $TSUE["TSUE_Cache"]->saveCache($cacheName, $top_donors_list);
            if( defined("IS_AJAX") ) 
            {
                return $top_donors_list;
            }

        }
        else
        {
            return NULL;
        }

    }

    eval("\$TSUEPlugin_topDonors = \"" . $TSUE["TSUE_Template"]->LoadTemplate("top_donors") . "\";");
    return $TSUEPlugin_topDonors;
}



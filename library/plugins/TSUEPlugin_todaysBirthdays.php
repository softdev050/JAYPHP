<?php 
function TSUEPlugin_todaysBirthdays($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_todaysBirthdays_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("todaysBirthdaysList");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($todaysBirthdays = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $todaysBirthdays = "";
        $Members = $TSUE["TSUE_Database"]->query("SELECT p.memberid, p.date_of_birth, m.membername, g.groupstyle FROM tsue_member_profile p INNER JOIN tsue_members m USING(memberid) INNER JOIN tsue_membergroups g USING(membergroupid) WHERE date_of_birth LIKE \"" . date("d/m") . "%\"");
        if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
        {
            $todaysBirthdays = array(  );
            while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
            {
                $_memberAge = calculateAge($Member["date_of_birth"]);
                if( $_memberAge ) 
                {
                    $_memberid = $Member["memberid"];
                    $Member["membername"] .= " (" . $_memberAge . ")";
                    $_membername = getMembername(strip_tags($Member["membername"]), $Member["groupstyle"]);
                    eval("\$todaysBirthdays[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                }

            }
            if( $todaysBirthdays ) 
            {
                $todaysBirthdays = implode(", ", $todaysBirthdays);
            }

            $TSUE["TSUE_Cache"]->saveCache($cacheName, $todaysBirthdays);
            if( defined("IS_AJAX") ) 
            {
                return $todaysBirthdays;
            }

        }
        else
        {
            return NULL;
        }

    }

    eval("\$TSUEPlugin_todaysBirthdays = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_todays_birthdays") . "\";");
    return $TSUEPlugin_todaysBirthdays;
}



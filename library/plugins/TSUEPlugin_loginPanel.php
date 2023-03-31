<?php 
function TSUEPlugin_loginPanel($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("logged");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !is_member_of("unregistered") ) 
    {
        $ul_dl_stats = ul_dl_stats($TSUE["TSUE_Member"]->info["uploaded"], $TSUE["TSUE_Member"]->info["downloaded"]);
        $_avatar = get_member_avatar($TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["gender"], "m");
        $_memberid = $TSUE["TSUE_Member"]->info["memberid"];
        $_membername = $TSUE["TSUE_Member"]->info["membername"];
        $_alt = "";
        eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
        eval("\$ShowMemberName = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_direct_link") . "\";");
        $TSUE["TSUE_Language"]->phrase["logged_welcome_back"] = get_phrase("logged_welcome_back", $ShowMemberName);
		if(null !==( $TSUE["TSUE_Template"]->LoadTemplate("logged"))){
			eval("\$TSUEPlugin_loginPanel = \"" . $TSUE["TSUE_Template"]->LoadTemplate("logged") . "\";");
		}
		else{
			eval("\$TSUEPlugin_loginPanel = \"" ."". "\";");
		}
        eval("\$TSUEPlugin_loginPanel = \"" . $TSUE["TSUE_Template"]->LoadTemplate("logged") . "\";");
    }
    else
    {
		if(isset($TSUE["TSUE_Template"]->LoadTemplate("login"))){
			eval("\$TSUEPlugin_loginPanel = \"" . $TSUE["TSUE_Template"]->LoadTemplate("login") . "\";");
		}
		else{
			eval("\$TSUEPlugin_loginPanel = \"" ."". "\";");
		}
        //eval("\$TSUEPlugin_loginPanel = \"" . $TSUE["TSUE_Template"]->LoadTemplate("login") . "\";");
    }

    return $TSUEPlugin_loginPanel;
}



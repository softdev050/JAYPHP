<?php 
function TSUEPlugin_shoutBOX($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    if( has_permission("canview_shout") && fetchCPOption("shoutbox_enabled") ) 
    {
        $TSUE["TSUE_Template"]->loadJavascripts("shoutbox");
        require_once(REALPATH . "/library/functions/functions_getShouts.php");
        $prepareShouts = prepareShouts();
        $ShoutBOXRows = $prepareShouts["ShoutBOXRows"];
        $lastSID = $prepareShouts["lastSID"];
        unset($prepareShouts);
        $shoutbox_post_shout = "";
        if( has_permission("canpost_shout") ) 
        {
            eval("\$shoutbox_post_shout = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_post_shout") . "\";");
        }

        $shoutboxNotifications = getSetting("shoutbox", "notifications");
        $shoutboxNotifications = $TSUE["TSUE_Parser"]->clearTinymceP($shoutboxNotifications);
        if( $shoutboxNotifications ) 
        {
            eval("\$shoutboxNotifications = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_notifications") . "\";");
        }

        $refresh_in_seconds = getPluginOption($pluginOptions, "refresh_in_seconds", 20);
        $max_load_limit = getPluginOption($pluginOptions, "max_load_limit", 300);
        $isToggled = isToggled("recentShoutbox");
        $class = (!$isToggled ? "" : "hidden");
        $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
        $shoutboxChannels = buildShoutboxChannels();
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_table") . "\";");
        return $Output;
    }

}



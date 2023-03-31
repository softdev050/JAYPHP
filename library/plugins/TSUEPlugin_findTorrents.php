<?php 
function TSUEPlugin_findTorrents($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("findTorrents");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    eval("\$TSUEPlugin_findTorrents = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_find_torrents") . "\";");
    return $TSUEPlugin_findTorrents;
}



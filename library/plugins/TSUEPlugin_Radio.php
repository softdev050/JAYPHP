<?php 
function TSUEPlugin_Radio($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $radioOptions = array( "radio_width" => getPluginOption($pluginOptions, "radio_width", "100%"), "radio_height" => getPluginOption($pluginOptions, "radio_height", "115"), "radio_version" => getPluginOption($pluginOptions, "radio_version", "11"), "radio_bg" => getPluginOption($pluginOptions, "radio_bg", "#F7F7F7"), "radio_swfcolor" => getPluginOption($pluginOptions, "radio_swfcolor", "999999"), "radio_swfradiochannel" => getPluginOption($pluginOptions, "radio_swfradiochannel", "TSUE Radio"), "radio_swfinformations" => getPluginOption($pluginOptions, "radio_swfinformations", "Enjoy your stay here!"), "radio_swfstreamurl" => getPluginOption($pluginOptions, "radio_swfstreamurl", "http://stream.blackbeatslive.de") );
    $isToggled = isToggled("TSUERadio");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    eval("\$TSUEPlugin_Radio = \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_Radio") . "\";");
    return $TSUEPlugin_Radio;
}



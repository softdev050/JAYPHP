<?php 
function TSUEPlugin_findMember($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("findMember");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    eval("\$TSUEPlugin_findMember = \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_findMember") . "\";");
    return $TSUEPlugin_findMember;
}



<?php 
function TSUEPlugin_recentThreads($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    global $TSUE_Forums;
    $recentThreadList = "";
    $isToggled = isToggled("recentThreads");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !isset($TSUE_Forums->availableForums) || !$TSUE_Forums->availableForums ) 
    {
        require_once(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums(true);
    }

    if( isset($TSUE_Forums->availableForums) && $TSUE_Forums->availableForums && count($TSUE_Forums->availableForums) ) 
    {
        $recentThreadList = $TSUE_Forums->prepareRecentThreads(getPluginOption($pluginOptions, "max_recent_threads", 5));
    }

    if( !$recentThreadList ) 
    {
        return NULL;
    }

    eval("\$TSUEPlugin_recentThreads = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recentThreads_table") . "\";");
    return $TSUEPlugin_recentThreads;
}



<?php 
function TSUEPlugin_sortOptions($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    global $SelectedSortBy;
    global $SelectedOrderBy;
    $isToggled = isToggled("displayOptions");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( $pluginPosition == "right" ) 
    {
        eval("\$TSUEPlugin_sortOptions = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_sort_options") . "\";");
    }
    else
    {
        eval("\$TSUEPlugin_sortOptions = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_sort_options_full") . "\";");
    }

    return $TSUEPlugin_sortOptions;
}



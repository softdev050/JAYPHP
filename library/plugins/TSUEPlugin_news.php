<?php 
function TSUEPlugin_news($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    if( $TSUE["TSUE_Settings"]->settings["active_news_cache"] ) 
    {
        $TSUE["TSUE_Template"]->loadJavascripts("news");
        $activeNews = "";
        foreach( $TSUE["TSUE_Settings"]->settings["active_news_cache"] as $nItem ) 
        {
            $nDate = convert_relative_time($nItem["date"]);
            $sNews = substr(strip_tags($nItem["content"]), 0, getPluginOption($pluginOptions, "max_chars_limit", 50));
            eval("\$activeNews .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("news_item") . "\";");
        }
        $isToggled = isToggled("recentNews");
        $class = (!$isToggled ? "" : "hidden");
        $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
        eval("\$activeNews = \"" . $TSUE["TSUE_Template"]->LoadTemplate("news_main") . "\";");
        return $activeNews;
    }

}



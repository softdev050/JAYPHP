<?php 
function TSUEPlugin_downloadMostPopularDownloads($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("mostPopularDownloads");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    $Downloads = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE d.did, d.title, d.downloads, c.cname FROM tsue_downloads d LEFT JOIN tsue_downloads_categories c USING(cid) WHERE d.downloads > 0 AND INSTR(CONCAT(',', c.cviewpermissions ,','),'," . $TSUE["TSUE_Member"]->info["membergroupid"] . ",') > 0 ORDER BY downloads DESC LIMIT " . getPluginOption($pluginOptions, "max_most_popular_downloads", 10));
    if( $TSUE["TSUE_Database"]->num_rows($Downloads) ) 
    {
        $downloadList = "";
        $PAGEID = 300;
        $index = 0;
        while( $Download = $TSUE["TSUE_Database"]->fetch_assoc($Downloads) ) 
        {
            $count = friendly_number_format($Download["downloads"]);
            $index++;
            $Download["title"] = strip_tags(substr($Download["title"], 0, ($pluginPosition == "right" ? 25 : 94)));
            eval("\$downloadList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_download_most_popular_downloads_li") . "\";");
        }
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_download_most_popular_downloads") . "\";");
        return $Output;
    }

}



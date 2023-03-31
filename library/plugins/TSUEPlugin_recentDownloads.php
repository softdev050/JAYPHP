<?php 
function TSUEPlugin_recentDownloads($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("recentDownloads");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    $downloadsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE d.did, d.title, d.preview,  c.cviewpermissions FROM tsue_downloads d LEFT JOIN tsue_downloads_categories c USING(cid) WHERE INSTR(CONCAT(',', c.cviewpermissions ,','),'," . $TSUE["TSUE_Member"]->info["membergroupid"] . ",') > 0 AND d.preview != '' ORDER BY d.added DESC LIMIT " . getPluginOption($pluginOptions, "max_recent_downloads", 20));
    if( $TSUE["TSUE_Database"]->num_rows($downloadsQuery) ) 
    {
        $addVerticalClass = ($pluginPosition == "right" ? " vertical" : "");
        $divWidthClass = ($TSUE["TSUE_Plugin"]->hasSideBarPlugins ? "widthSidebar" : "widthoutSidebar");
        $TSUE["TSUE_Template"]->loadJavascripts("scrollable");
        $Images = "\r\n\t\t<div class=\"items\">\r\n\t\t\t<div class=\"" . $divWidthClass . "\">";
        for( $count = 0; $Download = $TSUE["TSUE_Database"]->fetch_assoc($downloadsQuery); $count++ ) 
        {
            if( $count && $count % 7 == 0 ) 
            {
                $Images .= "\r\n\t\t\t\t</div>\r\n\t\t\t\t<div class=\"" . $divWidthClass . "\">";
            }

            $title = addslashes(strip_tags($Download["title"]));
            eval("\$Images .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_downloads_image") . "\";");
        }
        $Images .= "\r\n\t\t\t</div>\r\n\t\t</div>";
        eval("\$TSUEPlugin_recentDownloads = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recent_downloads") . "\";");
        return $TSUEPlugin_recentDownloads;
    }

}



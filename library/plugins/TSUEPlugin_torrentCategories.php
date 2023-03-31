<?php 
function TSUEPlugin_torrentCategories($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_torrentCategories_" . $TSUE["TSUE_Member"]->info["membergroupid"];
    $isToggled = isToggled("recentTorrentCategories");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($torrentCategories = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        $torrentCategories = "";
        $MainCategories = $SubCategories = array(  );
        $Categories = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE cid, pid, cname, cviewpermissions FROM tsue_torrents_categories ORDER by `sort` ASC");
        while( $C = $TSUE["TSUE_Database"]->fetch_assoc($Categories) ) 
        {
            if( 0 < $C["pid"] ) 
            {
                if( hasViewPermission($C["cviewpermissions"]) ) 
                {
                    $SubCategories[$C["pid"]][] = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;cid=" . $C["cid"] . "\">" . $C["cname"] . "</a>";
                }

            }
            else
            {
                $MainCategories[] = $C;
            }

        }
        foreach( $MainCategories as $M ) 
        {
            if( hasViewPermission($M["cviewpermissions"]) ) 
            {
                $subCategories = (isset($SubCategories[$M["cid"]]) ? implode(", ", $SubCategories[$M["cid"]]) : "");
                $categoryImage = get_torrent_category_image($M["cid"]);
                eval("\$torrentCategories .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_torrent_categories_list") . "\";");
            }

        }
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $torrentCategories);
    }

    eval("\$TSUEPlugin_torrentCategories = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_torrent_categories") . "\";");
    return $TSUEPlugin_torrentCategories;
}



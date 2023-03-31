<?php 
function TSUEPlugin_downloadCategories($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $cacheName = "TSUEPlugin_downloadCategories_" . $TSUE["TSUE_Member"]->info["membergroupid"] . "_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("downloadCategories");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($categoryList = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        $Categories = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE cid, cname, cdescription FROM tsue_downloads_categories WHERE INSTR(CONCAT(',', cviewpermissions ,','),'," . $TSUE["TSUE_Member"]->info["membergroupid"] . ",') > 0 ORDER BY `sort` ASC");
        if( $TSUE["TSUE_Database"]->num_rows($Categories) ) 
        {
            $categoryList = "";
            $PAGEID = 300;
            while( $Category = $TSUE["TSUE_Database"]->fetch_assoc($Categories) ) 
            {
                eval("\$categoryList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_download_categories_li") . "\";");
            }
            $TSUE["TSUE_Cache"]->saveCache($cacheName, $categoryList);
        }

    }

    eval("\$TSUEPlugin_downloadCategories = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_download_categories") . "\";");
    return $TSUEPlugin_downloadCategories;
}



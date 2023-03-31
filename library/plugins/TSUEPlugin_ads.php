<?php 
function TSUEPlugin_ads($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $Ads = $TSUE["TSUE_Database"]->query("SELECT title, content, permissions FROM tsue_ads WHERE active = 1 ORDER BY position ASC");
    $totalAds = $TSUE["TSUE_Database"]->num_rows($Ads);
    if( !$totalAds ) 
    {
        return "";
    }

    $TSUEPlugin_ads_rows = "";
    $adNumber = 1;
    while( $Row = $TSUE["TSUE_Database"]->fetch_assoc($Ads) ) 
    {
        if( hasViewPermission($Row["permissions"]) ) 
        {
            if( !isset($adsTitle) ) 
            {
                $adsTitle = $Row["title"];
            }

            $display = (1 < $adNumber ? "display: none;" : "");
            $Row["content"] = str_replace(array( "<p>", "</p>" ), array( "", "<br />" ), $Row["content"]);
            eval("\$TSUEPlugin_ads_rows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_ads_rows") . "\";");
            $adNumber++;
        }
        else
        {
            $totalAds--;
        }

    }
    if( !$totalAds ) 
    {
        return "";
    }

    $isToggled = isToggled("showAds");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    eval("\$TSUEPlugin_ads = \"" . $TSUE["TSUE_Template"]->LoadTemplate("TSUEPlugin_ads") . "\";");
    return $TSUEPlugin_ads;
}



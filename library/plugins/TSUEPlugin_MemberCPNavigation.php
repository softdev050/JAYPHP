<?php 
function TSUEPlugin_MemberCPNavigation($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $Navigation = "";
    $isToggled = isToggled("memberCPNavi");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    $Pages = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_pages WHERE parentid = 2 AND active = 1 ORDER BY `sort` ASC");
    if( $TSUE["TSUE_Database"]->num_rows($Pages) ) 
    {
        while( $Page = $TSUE["TSUE_Database"]->fetch_assoc($Pages) ) 
        {
            if( hasViewPermission($Page["viewpermissions"]) ) 
            {
                if( $Page["internal_link"] == "#" ) 
                {
                    $Link = "javascript:void(0)";
                }
                else
                {
                    $Link = ($Page["internal_link"] ? $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . fixInternalLink($Page["internal_link"]) . "&amp;pid=" . $Page["pageid"] : fixInternalLink($Page["external_link"]));
                }

                $Phrase = get_phrase($Page["phrase"]);
                $Navigation .= "<li><a href=\"" . $Link . "\"" . ((defined("PAGEID") && PAGEID == $Page["pageid"] ? " class=\"active\"" : "")) . ">" . $Phrase . "</a></li>";
            }

        }
        eval("\$Navigation = \"" . $TSUE["TSUE_Template"]->LoadTemplate("membercp_navigation") . "\";");
    }

    return $Navigation;
}



<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "market.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("market");
$Page_Title = get_phrase("navigation_market");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_market") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=market&amp;pid=" . PAGEID ));
if( !has_permission("canview_market") ) 
{
    show_error(get_phrase("permission_denied"));
}

$marketItems = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_market WHERE active = 1 ORDER BY required_points ASC");
if( !$TSUE["TSUE_Database"]->num_rows($marketItems) ) 
{
    show_error(get_phrase("market_no_items"));
}

$market_you_have_x_points = get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"]));
eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_table") . "\";");
while( $item = $TSUE["TSUE_Database"]->fetch_assoc($marketItems) ) 
{
    if( !$item["permissions"] || hasViewPermission($item["permissions"]) ) 
    {
        $item["required_points"] = friendly_number_format($item["required_points"]);
        eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_rows") . "\";");
    }

}
PrintOutput($Output, $Page_Title);


<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "alerts.php");
require("./library/init/init.php");
$Page_Title = get_phrase("recent_alerts");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=alerts&amp;pid=" . PAGEID ));
require_once(REALPATH . "/library/functions/functions_getAlerts.php");
printOutput(showMemberAlerts(false), $Page_Title);


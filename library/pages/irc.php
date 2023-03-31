<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "irc.php");
require("./library/init/init.php");
$Page_Title = get_phrase("navigation_irc");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_irc") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=irc&amp;pid=" . PAGEID ));
$TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"] = str_replace("#", "", $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"]);
eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("irc") . "\";");
PrintOutput($Output, $Page_Title);


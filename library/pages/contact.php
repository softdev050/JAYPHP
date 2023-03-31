<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "contact.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("contact");
$Page_Title = get_phrase("contact_title");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_contact") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=contact&amp;pid=" . PAGEID ));
eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("contact_form") . "\";");
PrintOutput($Output, $Page_Title);


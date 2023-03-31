<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "contactstaff.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("contactstaff");
$Page_Title = get_phrase("navigation_contact_staff");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_contact_staff") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=contactstaff&amp;pid=" . PAGEID ));
if( is_member_of("unregistered") ) 
{
    show_error(get_phrase("permission_denied"));
}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("contact_staff_form") . "\";");
PrintOutput($Output, $Page_Title);


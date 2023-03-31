<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "uploaderapplication.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "uploaderapplication", "wizard" ));
$Page_Title = get_phrase("join_our_uploaders_team");
$Output = "";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=uploaderapplication&amp;pid=" . PAGEID ));
if( has_permission("canupload_torrents") && !has_permission("canlogin_admincp") ) 
{
    show_error(get_phrase("permission_denied"));
}

eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("uploader_application") . "\";");
PrintOutput($Output, $Page_Title);


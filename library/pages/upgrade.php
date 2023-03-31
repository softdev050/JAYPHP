<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "upgrade.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts("upgrade");
$Page_Title = get_phrase("navigation_upgrade_account");
$Output = "";
if( !is_member_of("unregistered") ) 
{
    AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_membercp") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&amp;pid=2", get_phrase("navigation_upgrade_account") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=" . PAGEID ));
}
else
{
    AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_signup") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=signup&amp;pid=16", get_phrase("donate_to_signup") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=" . PAGEID ));
}

if( isset($_GET["register"]) && is_member_of("unregistered") ) 
{
    $Output .= show_information(get_phrase("donate_to_signup_info"), "", false);
}

$membergroupCache = array(  );
$membergroups = $TSUE["TSUE_Database"]->query("SELECT membergroupid, groupname, groupstyle FROM tsue_membergroups");
while( $membergroup = $TSUE["TSUE_Database"]->fetch_assoc($membergroups) ) 
{
    $membergroupCache[$membergroup["membergroupid"]] = getMembername($membergroup["groupname"], $membergroup["groupstyle"]);
}
if( !is_member_of("unregistered") ) 
{
    require_once(REALPATH . "library/functions/functions_memberHistory.php");
    $Output .= prepareSubscriptions($TSUE["TSUE_Member"]->info["memberid"]);
}

$Upgrades = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_member_upgrades WHERE active = 1 ORDER BY upgrade_price ASC");
if( !$TSUE["TSUE_Database"]->num_rows($Upgrades) ) 
{
    $Output .= show_error(get_phrase("upgrade_account_no_items"), "", false);
}
else
{
    while( $Upgrade = $TSUE["TSUE_Database"]->fetch_assoc($Upgrades) ) 
    {
        if( $Upgrade["viewpermissions"] ) 
        {
            $Upgrade["viewpermissions"] = unserialize($Upgrade["viewpermissions"]);
            if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Upgrade["viewpermissions"]) ) 
            {
                continue;
            }

        }

        $newIndicatorText = $Upgrade["upgrade_title"];
        eval("\$newIndicator = \"" . $TSUE["TSUE_Template"]->LoadTemplate("newIndicator") . "\";");
        if( $Upgrade["upgrade_length_type"] == "lifetime" ) 
        {
            $Upgrade["upgrade_length"] = "";
            $Upgrade["upgrade_length_type"] = get_phrase("upgrade_lifetime");
        }
        else
        {
            $Upgrade["upgrade_length_type"] = get_phrase("upgrade_" . $Upgrade["upgrade_length_type"] . ((1 < $Upgrade["upgrade_length"] ? "s" : "")));
        }

        $upradePrice = get_phrase("upgrade_price", $Upgrade["upgrade_price"], strtoupper($Upgrade["upgrade_currency"]), $Upgrade["upgrade_length"], $Upgrade["upgrade_length_type"]);
        $priceImage = "";
        $priceImageFile = "styles/" . $TSUE["TSUE_Template"]->ThemeName . "/market/" . strtolower($Upgrade["upgrade_currency"]) . ".png";
        if( is_file("./" . $priceImageFile) ) 
        {
            $currencyCode = strtolower($Upgrade["upgrade_currency"]);
            eval("\$priceImage = \"" . $TSUE["TSUE_Template"]->LoadTemplate("price_image") . "\";");
        }

        $Promotions = "";
        if( $Upgrade["upgrade_membergroupid"] ) 
        {
            $Promotions[] = get_phrase("upgrade_membergroup_x", $membergroupCache[$Upgrade["upgrade_membergroupid"]]);
        }

        if( $Upgrade["upgrade_points"] ) 
        {
            $Promotions[] = get_phrase("upgrade_points_x", friendly_number_format($Upgrade["upgrade_points"]));
        }

        if( $Upgrade["upgrade_invites"] ) 
        {
            $Promotions[] = get_phrase("upgrade_invites_x", friendly_number_format($Upgrade["upgrade_invites"]));
        }

        if( $Upgrade["upgrade_upload"] ) 
        {
            $Promotions[] = get_phrase("upgrade_upload_x", friendly_size($Upgrade["upgrade_upload"] * 1073741824));
        }

        if( $Promotions ) 
        {
            $Promotions = implode("<br />", $Promotions);
        }

        eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("upgrade_account") . "\";");
    }
}

PrintOutput($Output, $Page_Title);


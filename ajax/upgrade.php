<?php 
define("SCRIPTNAME", "upgrade.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

if( $TSUE["action"] == "upgrade" ) 
{
    globalize("post", array( "upgrade_id" => "INT" ));
    if( !$upgrade_id ) 
    {
        ajax_message(get_phrase("upgrade_invalid_item"), "-ERROR-");
    }

    $Upgrade = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_member_upgrades WHERE upgrade_id = " . $TSUE["TSUE_Database"]->escape($upgrade_id) . " AND active = 1");
    if( !$Upgrade ) 
    {
        ajax_message(get_phrase("upgrade_invalid_item"), "-ERROR-");
    }

    if( $Upgrade["viewpermissions"] ) 
    {
        $Upgrade["viewpermissions"] = unserialize($Upgrade["viewpermissions"]);
        if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Upgrade["viewpermissions"]) ) 
        {
            ajax_message(get_phrase("upgrade_invalid_item"), "-ERROR-");
        }

    }

function generateHash()
{
    global $TSUE;
    $hash = substr(md5(time() . generatePasskey(10) . time()), 0, 30);
    while( $TSUE["TSUE_Database"]->query_result("SELECT history_id FROM tsue_member_upgrades_purchases WHERE hash = " . $TSUE["TSUE_Database"]->escape($hash)) ) 
    {
        $hash = generateHash();
    }
    return $hash;
}

    $apiCache = array(  );
    $apis = $TSUE["TSUE_Database"]->query("SELECT * FROM tsue_member_upgrades_api WHERE active = 1");
    if( $TSUE["TSUE_Database"]->num_rows($apis) ) 
    {
        $hash = generateHash();
        while( $api = $TSUE["TSUE_Database"]->fetch_assoc($apis) ) 
        {
            $apiSettings = ($api["settings"] ? unserialize($api["settings"]) : array(  ));
            require_once(REALPATH . "library/classes/class_" . $api["classname"] . ".php");
            $apiClass = "TSUE_" . $api["classname"];
            $apiObj = new $apiClass($apiSettings);
            $apiCache[] = $apiObj->genareteForm($Upgrade, $hash, $TSUE["TSUE_Member"]->info["membername"]);
        }
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
        $title = $Upgrade["upgrade_title"] . " (" . $upradePrice . ")";
        $BuildQuery = array( "hash" => $hash, "upgrade_id" => $Upgrade["upgrade_id"], "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "completed" => 0, "created" => TIMENOW );
        if( $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_purchases", $BuildQuery) ) 
        {
            $apiCache = implode(" &nbsp; ", $apiCache);
            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upgrade_select_payment_method") . "\";");
            ajax_message($Output, "", false, $title);
            return 1;
        }

        ajax_message(get_phrase("database_error"), "-ERROR-");
        return 1;
    }

    ajax_message(get_phrase("upgrade_no_payment_api_found"), "-ERROR-");
}



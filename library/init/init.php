<?php 
date_default_timezone_set("GMT");
define("TIMESTART", microtime(true));
define("TIMENOW", time());
define("REALPATH", str_replace(array( "\\", "library/init" ), array( "/", "" ), dirname(__FILE__)));
define("V", "2.2");
if( !defined("SCRIPTNAME") ) 
{
    define("SCRIPTNAME", $_SERVER["SCRIPT_NAME"]);
}

error_reporting(30719);
ini_set("display_errors", "Off");
set_error_handler("GlobalErrorHandler");
if( function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() ) 
{
    require(REALPATH . "library/functions/functions_undoMagicQuotes.php");
}

if( function_exists("get_magic_quotes_runtime") && get_magic_quotes_runtime() ) 
{
    set_magic_quotes_runtime(false);
}

ini_set("memory_limit", "256M");
set_time_limit(0);
ini_set("output_buffering", false);
while( ob_get_level() ) 
{
    ob_end_clean();
}
$TSUE = array(  );
$TSUE["crumbsLinks"] = array(  );
$TSUE["breadcrumb"] = $TSUE["crumbsLinks"];
$TSUE["action"] = (isset($_GET["action"]) ? trim($_GET["action"]) : (isset($_POST["action"]) ? trim($_POST["action"]) : ""));
$TSUE["do"] = (isset($_GET["do"]) ? trim($_GET["do"]) : (isset($_POST["do"]) ? trim($_POST["do"]) : ""));
$TSUE["extraMenuItems"] = "";
$TSUE["V"] = V;
require(REALPATH . "library/functions/functions_global.php");
define("MEMBER_IP", getClientIp());
require(REALPATH . "library/classes/class_database.php");
$TSUE["TSUE_Database"] = new TSUE_Database();
require(REALPATH . "library/classes/class_settings.php");
$TSUE["TSUE_Settings"] = new TSUE_Settings((isset($loadSettings) ? $loadSettings : NULL));
if( !defined("NO_MEMBER") ) 
{
    require(REALPATH . "library/classes/class_member.php");
    $TSUE["TSUE_Member"] = new TSUE_Member();
}

if( !defined("NO_CACHE") ) 
{
    require(REALPATH . "library/classes/class_cache.php");
    $TSUE["TSUE_Cache"] = new TSUE_Cache();
}

if( !defined("NO_LANGUAGE") ) 
{
    require(REALPATH . "library/classes/class_language.php");
    $TSUE["TSUE_Language"] = new TSUE_Language();
}

if( !defined("NO_SECURITY") ) 
{
    require(REALPATH . "library/classes/class_security.php");
    $TSUE["TSUE_Security"] = new TSUE_Security();
}

if( !defined("NO_PARSER") ) 
{
    require(REALPATH . "library/classes/class_parser.php");
    $TSUE["TSUE_Parser"] = new TSUE_Parser();
}

if( !defined("NO_TEMPLATE") ) 
{
    require(REALPATH . "library/classes/class_template.php");
    $TSUE["TSUE_Template"] = new TSUE_Template();
}

if( !defined("NO_PLUGIN") ) 
{
    require(REALPATH . "library/classes/class_plugin.php");
    $TSUE["TSUE_Plugin"] = new TSUE_Plugin();
}

if( isset($_GET["logout"]) && isset($_GET["hash"]) && !is_member_of("unregistered") && isValidToken($_GET["hash"]) ) 
{
    logOut();
}

register_shutdown_function("shutdown");
if( !in_array(SCRIPTNAME, array( "announce.php", "auto_uploader.php", "cron.php", "js.php", "payment_gateway.php", "scrape.php", "style.php" )) ) 
{
    if( !$TSUE["TSUE_Settings"]->settings["global_settings"]["website_active"] && !has_permission("canview_offline_website") ) 
    {
        if( !defined("NO_PLUGINS") ) 
        {
            define("NO_PLUGINS", true);
        }

        define("LOAD_PLUGIN_ID", "1");
        if( defined("IS_AJAX") ) 
        {
            if( $TSUE["action"] != "login" ) 
            {
                ajax_message($TSUE["TSUE_Settings"]->settings["global_settings"]["website_active_reason"], "-ERROR-");
            }

        }
        else
        {
            show_error($TSUE["TSUE_Settings"]->settings["global_settings"]["website_active_reason"]);
        }

    }

    if( is_member_of("banned") ) 
    {
        (defined("IS_AJAX") ? ajax_message(getBanReason(), "-ERROR-") : exit);
    }

    if( isIPBanned() ) 
    {
        (defined("IS_AJAX") ? ajax_message(get_phrase("permission_denied_banned_ip"), "-ERROR-") : exit);
    }

    if( isset($TSUE["TSUE_Settings"]->settings["banned_countries_cache"]) && !empty($TSUE["TSUE_Settings"]->settings["banned_countries_cache"]) ) 
    {
        include(REALPATH . "library/classes/class_geoip.php");
        $geoIP = geoip_open(REALPATH . "library/geoip/GeoIP.dat", GEOIP_STANDARD);
        if( array_search(geoip_country_code_by_addr($geoIP, MEMBER_IP), $TSUE["TSUE_Settings"]->settings["banned_countries_cache"]) !== false || array_search(geoip_country_name_by_addr($geoIP, MEMBER_IP), $TSUE["TSUE_Settings"]->settings["banned_countries_cache"]) !== false ) 
        {
            (defined("IS_AJAX") ? ajax_message(get_phrase("permission_denied_banned_country"), "-ERROR-") : exit);
        }

        unset($geoIP);
    }

}

if( isset($TSUE["TSUE_Member"]->info["memberid"]) && $TSUE["TSUE_Member"]->info["memberid"] ) 
{
    setLoginCookie($TSUE["TSUE_Member"]->remember_me, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["password"], false);
}

function GlobalErrorHandler($errno, $errstr, $errfile, $errline)
{
    $Message = "\r\n------------------------------------------\r\n" . date("d-m-Y H:i:s") . "\r\n[" . $errno . "] " . $errstr . "\r\nPHP Error on line " . number_format($errline) . " in file " . $errfile . "\r\n" . PHP_VERSION . " " . PHP_OS . "\r\n------------------------------------------";
    file_put_contents(REALPATH . "data/errors/" . SCRIPTNAME . ".log", $Message, FILE_APPEND);
}



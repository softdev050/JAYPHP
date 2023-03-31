<?php 
define("SCRIPTNAME", "style.php");
define("NO_LANGUAGE", 1);
define("NO_TEMPLATE", 1);
define("NO_LASTACTIVITY_UPDATE", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_SECURITY", 1);
define("NO_PARSER", 1);
define("NO_PLUGIN", 1);
require("./library/init/init.php");
globalize("get", array( "l" => "TRIM" ));
$Content = "/* ERROR: Style NOT Found! */";
if( ($l = tsue_explode(",", $l)) && 0 < count($l) ) 
{
    $l = array_map("cssExtension", $l);
}

if( $l && is_array($l) && count($l) ) 
{
    $styleNames = implode(",", $l);
    $cacheName = "style_" . md5($styleNames) . "_" . $TSUE["TSUE_Member"]->info["themeid"];
    if( !($Content = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        $Content = "";
        $StyleQuery = $TSUE["TSUE_Database"]->query("SELECT s.stylename, s.css, t.themename FROM tsue_styles s INNER JOIN tsue_themes t USING(themeid) WHERE s.themeid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["themeid"]) . " AND t.active = 1 AND s.stylename IN (" . $styleNames . ")");
        while( $Style = $TSUE["TSUE_Database"]->fetch_assoc($StyleQuery) ) 
        {
            $Content .= "/* STYLE: " . $Style["stylename"] . " */\n" . str_replace(array( "{themename}", "{website_url}" ), array( $Style["themename"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] ), $Style["css"]) . "\n/* STYLE: " . $Style["stylename"] . " */\n\n";
        }
        $Content = compress($Content);
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $Content);
    }

}

if( isset($_GET["style-debug"]) ) 
{
    require_once(REALPATH . "library/functions/functions_debug.php");
    exit( fullDebugOutput() );
}

_sendHeaders($Content, "text/css", false, true);
function cssExtension($stylename)
{
    global $TSUE;
    return $TSUE["TSUE_Database"]->escape(trim(strval($stylename)) . ".css");
}

function compress($buffer = "")
{
    $buffer = preg_replace("!/\\*[^*]*\\*+([^/][^*]*\\*+)*/!", "", $buffer);
    $buffer = str_replace(array( "\r\n", "\r", "\n", "\t", "  ", "    ", "    ", ": ", " :", " {", "{ ", " }", "} " ), array( "", "", "", "", "", "", "", ":", ":", "{", "{", "}", "}" ), $buffer);
    return $buffer;
}



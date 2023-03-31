<?php 
@ini_set("session.gc_maxlifetime", 900);
@session_name("tsueadmincp");
@session_cache_expire(900);
@session_start();
$filename = (isset($_GET["load"]) ? trim($_GET["load"]) : "");
if( !preg_match("#^[a-zA-Z0-9_\\-]+\$#", $filename) ) 
{
    exit( "<h1>The specific addon could not be load!</h1>" );
}

if( !isset($_SESSION["load-addon"]) || $_SESSION["load-addon"] != base64_encode(strrev(md5($filename))) ) 
{
    exit( "\r\n\t<div style=\"color: red; font-size: 20px; font-weight: bold;\">The add-on session has been expired!</div>\r\n\t<div style=\"color: #000; font-size: 16px; font-weight: bold;\">Please re-run this add-on in Admin CP or refresh this page!</div>\r\n\t<pre>S: " . $_SESSION["load-addon"] . "<br />F: " . base64_encode(strrev(md5($filename))) . "</pre>" );
}

require("./../../library/init/init.php");
$Addon = $TSUE["TSUE_Database"]->query_result("SELECT active, permissions FROM tsue_admincp_addons WHERE filename = " . $TSUE["TSUE_Database"]->escape($filename));
if( !$Addon ) 
{
    exit( "<h1>The specific addon could not be found in the database!</h1>" );
}

if( !$Addon["active"] ) 
{
    exit( "<h1>This addon is disabled!</h1>" );
}

$addonFile = "./files/" . $filename . ".php";
if( !is_file($addonFile) ) 
{
    exit( "<h1>The specific addon could not be loaded!</h1>" );
}

define("ADDON", true);
include($addonFile);
exit();


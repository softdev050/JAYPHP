<?php 
define("IN_INDEX", 1);
if( isset($_GET["noscript"]) ) 
{
    header("Location: noJavascript.html");
    exit();
}

$pagePath = "./library/pages/";
$pageID = (isset($_GET["pid"]) && !empty($_GET["pid"]) ? intval($_GET["pid"]) : 0);
$pageFile = (isset($_GET["p"]) && !empty($_GET["p"]) ? trim(strtolower($_GET["p"])) : "");
if( !ctype_alpha($pageFile) ) 
{
    $pageFile = "";
}
else
{
    if( !is_file($pagePath . $pageFile . ".php") ) 
    {
        $pageFile = "";
    }

}

$notRequiredPID = [ "dialog", "confirmaccount", "forgotpassword", "goto", "logout", "notfound", "rss" ];
if( !$pageID && !in_array($pageFile, $notRequiredPID) || !$pageFile ) 
{
    header("Location: ?p=home&pid=1");
    exit();
}

define("PAGEID", $pageID);
define("PAGEFILE", $pageFile);
unset($pageID);
unset($notRequiredPID);
require($pagePath . $pageFile . ".php");
exit();


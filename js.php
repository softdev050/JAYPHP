<?php 
define("SCRIPTNAME", "js.php");
define("NO_MEMBER", 1);
define("NO_LANGUAGE", 1);
define("NO_TEMPLATE", 1);
define("NO_LASTACTIVITY_UPDATE", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_SECURITY", 1);
define("NO_PARSER", 1);
define("NO_PLUGIN", 1);
require("./library/init/init.php");
globalize("get", array( "s" => "TRIM" ));
$Content = "";
if( $s && ($loadJavascripts = tsue_explode(",", $s)) && $loadJavascripts ) 
{
    $cacheName = "js_" . md5($s);
    if( !($Content = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
    {
        require_once(REALPATH . "library/classes/class_jsmin.php");
        $Content = "";
        foreach( $loadJavascripts as $JS ) 
        {
            if( !preg_match("#[^a-z_]#", $JS) ) 
            {
                $jsFile = REALPATH . "js/tsue/" . $JS . ".js";
                if( file_exists($jsFile) ) 
                {
                    $JSContent = file_get_contents($jsFile);
                    if( $JS != "jquery" ) 
                    {
                        $Content .= JSMin::minify($JSContent) . "\n";
                    }
                    else
                    {
                        $Content .= $JSContent . "\n";
                    }

                }

            }

        }
        $TSUE["TSUE_Cache"]->saveCache($cacheName, $Content);
    }

}

if( isset($_GET["js-debug"]) ) 
{
    require_once(REALPATH . "library/functions/functions_debug.php");
    exit( fullDebugOutput() );
}

_sendHeaders($Content, "text/javascript", false, true);


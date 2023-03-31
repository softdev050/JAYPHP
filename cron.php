<?php 
define("SCRIPTNAME", "cron.php");
define("NO_SECURITY", 1);
define("NO_PARSER", 1);
if( !isset($_GET["cron-debug"]) ) 
{
    define("NO_MEMBER", 1);
    define("NO_TEMPLATE", 1);
}

define("NO_PLUGIN", 1);
define("IN_INDEX", 1);
require_once("./library/init/init.php");
if( !isset($_GET["cron-debug"]) ) 
{
    header("Content-Type: image/gif");
    echo base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");
    flush();
}

$Crons = $TSUE["TSUE_Database"]->query("SELECT cid, minutes, filename, function FROM tsue_cron WHERE nextrun <= " . TIMENOW . " AND active = 1");
if( $TSUE["TSUE_Database"]->num_rows($Crons) ) 
{
    $cronIDS = $cronCache = array(  );
    while( $Cron = $TSUE["TSUE_Database"]->fetch_assoc($Crons) ) 
    {
        if( is_file($filename = REALPATH . "/library/cron/" . $Cron["filename"]) ) 
        {
            $cronIDS[] = $Cron["cid"];
            $cronCache[$filename] = $Cron;
        }

    }
    if( !empty($cronIDS) ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_cron", array( "nextrun" => array( "escape" => 0, "value" => TIMENOW . "+minutes" ) ), "cid IN (" . implode(",", $cronIDS) . ")");
        unset($cronIDS);
        foreach( $cronCache as $filename => $Cron ) 
        {
            include_once($filename);
            if( !isset($_GET["cron-debug"]) ) 
            {
                $TSUE["TSUE_Database"]->resetQueryCounts();
            }

            $timeStart = microtime(true);
            $Cron["function"]();
            $TSUE["TSUE_Database"]->update("tsue_cron", array( "queryCount" => $TSUE["TSUE_Database"]->querycount, "loadTime" => round(microtime(true) - $timeStart, 4) ), "cid=" . $TSUE["TSUE_Database"]->escape($Cron["cid"]));
        }
        unset($cronCache);
    }

}

if( isset($_GET["cron-debug"]) ) 
{
    require_once(REALPATH . "library/functions/functions_debug.php");
    exit( fullDebugOutput() );
}



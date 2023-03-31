<?php 
define("SCRIPTNAME", "backupDatabase.php");
if( !defined("STDIN") && !isset($_GET["skipCMDRun"]) ) 
{
    exit( "The maintenance scripts must then be run from the command line." );
}

$rPath = str_replace(array( "\\", "/cli" ), array( "/", "" ), dirname(__FILE__));
require($rPath . "/library/init/init.php");
backupdatabase();
function backupDatabase()
{
    global $TSUE;
    $time = convert_time(TIMENOW, "dS F Y \\a\\t H:i");
    $mysqlversion = $TSUE["TSUE_Database"]->query_result("SELECT VERSION() AS version");
    $backupSQL = "-- -------------------------------------------\n-- TSUE Database Backup\n-- Generated: " . $time . "\n-- PHP Version: " . phpversion() . "\n-- MySQL Version: " . $mysqlversion["version"] . "\n-- TSUE Version: " . V . "\n-- -------------------------------------------\n";
    $Tables = array(  );
    $Result = $TSUE["TSUE_Database"]->query("SHOW TABLES");
    while( $Row = $TSUE["TSUE_Database"]->fetch_row($Result) ) 
    {
        $Tables[] = $Row["0"];
    }
    foreach( $Tables as $Table ) 
    {
        if( preg_match("#^[a-zA-Z_]+\$#", $Table) ) 
        {
            $backupSQL .= "\n-- ----------------------------------------------------------------------\n";
            $backupSQL .= "-- Table structure for table `" . $Table . "`";
            $backupSQL .= "\n-- ----------------------------------------------------------------------\n";
            $Query = $TSUE["TSUE_Database"]->query_result("SHOW CREATE TABLE `" . $Table . "`");
            $backupSQL .= $Query["Create Table"] . ";\n\n";
            $backupSQL .= "\n-- ----------------------------------------------------------------------\n";
            $backupSQL .= "-- Dumping data for table `" . $Table . "`";
            $backupSQL .= "\n-- ----------------------------------------------------------------------\n";
            $Fields = array(  );
            $rowTypes = array(  );
            $Rows = $TSUE["TSUE_Database"]->query("SHOW COLUMNS FROM " . $Table);
            while( $Row = $TSUE["TSUE_Database"]->fetch_assoc($Rows) ) 
            {
                $Fields[] = "`" . $Row["Field"] . "`";
                $rowTypes[] = $Row["Type"];
            }
            $Datas = $TSUE["TSUE_Database"]->query("SELECT * FROM " . $Table);
            if( $TSUE["TSUE_Database"]->num_rows($Datas) ) 
            {
                while( $Data = $TSUE["TSUE_Database"]->fetch_row($Datas) ) 
                {
                    foreach( $Data as $j => $k ) 
                    {
                        if( preg_match("#binary|blob#isU", strtolower($rowTypes[$j])) ) 
                        {
                            $Data[$j] = (!$k ? "''" : "0x" . bin2hex($k));
                        }
                        else
                        {
                            $Data[$j] = $TSUE["TSUE_Database"]->escape($k);
                        }

                    }
                    $backupSQL .= "INSERT INTO " . $Table . " (" . implode(",", $Fields) . ") VALUES (" . implode(",", $Data) . ");\n";
                }
            }

        }

    }
    $fileName = "tsue_db_backup_" . TIMENOW . ".sql";
    $Path = REALPATH . "data/backups/";
    if( function_exists("gzopen") ) 
    {
        $fileName .= ".gz";
        $fp = gzopen($Path . $fileName, "w9");
        gzwrite($fp, $backupSQL);
    }
    else
    {
        $fp = fopen($Path . $fileName . ".sql", "w");
        fwrite($fp, $backupSQL);
    }

    echo $fileName . " (" . friendly_size(filesize($Path . $fileName)) . ") is ready.";
}



<?php 
define("SCRIPTNAME", "switch_recent_torrents.php");
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

globalize("post", array( "pn" => "INT" ));
switch( $TSUE["action"] ) 
{
    case "List":
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_recentTorrents.php'");
        if( $Plugin && hasViewPermission($Plugin["viewpermissions"]) ) 
        {
            require_once(REALPATH . "library/plugins/TSUEPlugin_recentTorrents.php");
            ajax_message(TSUEPlugin_recentTorrents_Switch_List(NULL, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  )), $pn));
        }

}


<?php 
define("SCRIPTNAME", "read_announcement.php");
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

switch( $TSUE["action"] ) 
{
    case "read_announcement":
        cookie_set("read_announcement", TIMENOW);
        if( $TSUE["TSUE_Settings"]->settings["active_announcements_cache"] ) 
        {
            ajax_message($TSUE["TSUE_Settings"]->settings["active_announcements_cache"]["content"], "", false, $TSUE["TSUE_Settings"]->settings["active_announcements_cache"]["title"]);
        }
        else
        {
            ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        }

}


<?php 
define("SCRIPTNAME", "contactstaff.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" || is_member_of("unregistered") ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

if( $TSUE["action"] == "contactstaff" ) 
{
    globalize("post", array( "message" => "TRIM" ));
    $strlenOriginalText = strlenOriginalText($message);
    if( $strlenOriginalText < 3 ) 
    {
        $Error[] = get_phrase("valid_message_error");
    }

    if( !isset($Error) ) 
    {
        check_flood("contactstaff");
        $buildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "message" => $message );
        $TSUE["TSUE_Database"]->insert("tsue_staff_messages", $buildQuery);
        ajax_message(get_phrase("contact_message_sent"), "-DONE-");
    }

    ajax_message(implode("<br />", $Error), "-ERROR-");
}



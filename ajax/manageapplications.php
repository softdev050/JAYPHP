<?php 
define("SCRIPTNAME", "manageapplications.php");
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
    case "delete_applications":
        globalize("post", array( "mid" => "TRIM" ));
        if( !has_permission("candelete_applications") || empty($mid) || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $mid = implode(",", array_map("intval", explode(",", $mid)));
        $TSUE["TSUE_Database"]->delete("tsue_uploader_applications", "memberid IN(" . $mid . ")");
        $Output = get_phrase("applications_has_been_deleted", $mid);
        logAction($Output);
        exit();
}


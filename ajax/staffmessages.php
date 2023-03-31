<?php 
define("SCRIPTNAME", "staffmessages.php");
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
    case "delete_staff_message":
        globalize("post", array( "mid" => "TRIM" ));
        if( !has_permission("candelete_staff_messages") || empty($mid) || is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $mid = implode(",", array_map("intval", explode(",", $mid)));
        $TSUE["TSUE_Database"]->delete("tsue_staff_messages", "mid IN(" . $mid . ")");
        $TSUE["TSUE_Database"]->delete("tsue_comments", "content_type = 'staff_messages_comments' AND content_id IN(" . $mid . ")");
        $TSUE["TSUE_Database"]->delete("tsue_comments_replies", "content_type = 'staff_messages_comments' AND content_id IN(" . $mid . ")");
        $Output = get_phrase("staff_messages_has_been_deleted", $mid);
        logAction($Output);
        exit();
}


<?php 
define("SCRIPTNAME", "shoutbox.php");
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

require_once(REALPATH . "/library/functions/functions_getShouts.php");
switch( $TSUE["action"] ) 
{
    case "getNewShouts":
        if( !canViewShoutbox() ) 
        {
            ajax_message(get_phrase("shoutbox_channel_view_error"), "-ERROR-");
        }

        globalize("post", array( "lastSID" => "INT" ));
        $Output = prepareNewShouts($lastSID);
        jsonHeaders($Output);
        break;
    case "postShout":
        if( !canPostShoutbox() ) 
        {
            ajax_message(get_phrase("shoutbox_channel_post_shout_error"), "-ERROR-");
        }

        globalize("post", array( "smessage" => "TRIM" ));
        $smessage = removeLastEmptyLines($smessage);
        if( !$smessage ) 
        {
            ajax_message(get_phrase("valid_message_error"), "-ERROR-");
        }

        handleShoutboxCommands($smessage);
        check_flood("shoutbox_post_shout");
        $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "sdate" => TIMENOW, "smessage" => $smessage, "cid" => getActiveShoutboxChannel() );
        if( !$TSUE["TSUE_Database"]->insert("tsue_shoutbox", $BuildQuery) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        break;
    case "getShout":
    case "saveShout":
        if( !canPostShoutbox() ) 
        {
            ajax_message(get_phrase("shoutbox_channel_post_shout_error"), "-ERROR-");
        }

        globalize("post", array( "sid" => "INT" ));
        $Shout = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE memberid,smessage FROM tsue_shoutbox WHERE sid = " . $TSUE["TSUE_Database"]->escape($sid) . " AND cid = " . getActiveShoutboxChannel());
        if( !$Shout ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( has_permission("canedit_own_shout") && $Shout["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_shout") ) 
        {
            if( $TSUE["action"] == "saveShout" ) 
            {
                globalize("post", array( "smessage" => "TRIM" ));
                $smessage = removeLastEmptyLines($smessage);
                if( !$smessage ) 
                {
                    ajax_message(get_phrase("valid_message_error"), "-ERROR-");
                }

                $BuildQuery = array( "smessage" => $smessage );
                if( $TSUE["TSUE_Database"]->update("tsue_shoutbox", $BuildQuery, "sid=" . $TSUE["TSUE_Database"]->escape($sid)) ) 
                {
                    $smessage = $TSUE["TSUE_Parser"]->parse($smessage);
                    ajax_message($smessage);
                }
                else
                {
                    ajax_message(get_phrase("database_error"), "-ERROR-");
                }

            }
            else
            {
                $message = html_clean($TSUE["TSUE_Parser"]->clearTinymceP($Shout["smessage"]));
                $post_id = $sid;
                $upload_button = "";
                eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_ajax_editor") . "\";");
                ajax_message($Output, false, "", get_phrase("message_edit"));
            }

        }
        else
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        break;
    case "deleteShout":
        if( !canViewShoutbox() ) 
        {
            ajax_message(get_phrase("shoutbox_channel_view_error"), "-ERROR-");
        }

        globalize("post", array( "sid" => "INT" ));
        $Shout = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE memberid FROM tsue_shoutbox WHERE sid = " . $TSUE["TSUE_Database"]->escape($sid) . " AND cid=" . getActiveShoutboxChannel());
        if( !$Shout ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( has_permission("candelete_own_shout") && $Shout["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_shout") ) 
        {
            check_flood("delete_shout");
            $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "sid = " . $TSUE["TSUE_Database"]->escape($sid));
        }
        else
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

}


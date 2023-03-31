<?php 
define("SCRIPTNAME", "messages.php");
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
    case "messages_get_reply":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        globalize("post", array( "message_id" => "INT", "reply_id" => "INT" ));
        if( !$message_id || !$reply_id ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }

        $Reply = $TSUE["TSUE_Database"]->query_result("SELECT r.reply, m.subject, members.membername\r\n\t\t\tFROM tsue_messages_replies r\r\n\t\t\tINNER JOIN tsue_messages_master m USING(message_id)\r\n\t\t\tINNER JOIN tsue_members members ON (r.memberid=members.memberid)\r\n\t\t\tWHERE m.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND r.reply_id = " . $TSUE["TSUE_Database"]->escape($reply_id) . " AND (m.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR m.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")");
        if( !$Reply ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
            break;
        }

        exit( "[quote=" . strip_tags($Reply["membername"]) . "]" . $TSUE["TSUE_Parser"]->parse($Reply["reply"]) . "[/quote]" );
    case "show_more_messages":
        globalize("post", array( "read_messages" => "TRIM" ));
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !$read_messages ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $read_messages = implode(",", array_map("intval", explode(",", $read_messages)));
        if( !$read_messages ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $MQuery = $TSUE["TSUE_Database"]->query("SELECT messages.*, max(reply.reply_date) as reply_date, members.memberid, members.membername, members.gender, g.groupstyle \r\n\t\tFROM tsue_messages_master messages \r\n\t\tINNER JOIN tsue_messages_replies reply USING(message_id) \r\n\t\tINNER JOIN tsue_members members ON (members.memberid=IF(messages.owner_memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ",messages.receiver_memberid,messages.owner_memberid)) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE messages.message_id NOT IN (" . $read_messages . ") AND (messages.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR messages.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ") \r\n\t\tGROUP BY messages.message_id ORDER BY reply_date");
        if( $TSUE["TSUE_Database"]->num_rows($MQuery) ) 
        {
            require_once(REALPATH . "library/functions/functions_getMessages.php");
            $ShowMemberMessages = "";
            while( $M = $TSUE["TSUE_Database"]->fetch_assoc($MQuery) ) 
            {
                if( !($M["viaAdminCP"] && $M["receiver_memberid"] != $TSUE["TSUE_Member"]->info["memberid"]) ) 
                {
                    $ShowMemberMessages .= prepareMessageList($M);
                }

            }
            ajax_message($ShowMemberMessages, "", false);
        }
        else
        {
            ajax_message(get_phrase("messages_no_message"), "-ERROR-");
        }

        break;
    case "messages_new_message":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !has_permission("canpost_a_new_message") || isMuted($TSUE["TSUE_Member"]->info["muted"], "pm") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            globalize("post", array( "receiver_membername" => "TRIM", "subject" => "TRIM", "message" => "TRIM" ));
            require_once(REALPATH . "library/functions/functions_getMessages.php");
            postMessage($receiver_membername, $subject, $message);
        }

        globalize("post", array( "receiver_membername" => "TRIM", "message_id" => "INT" ));
        if( !is_valid_string($receiver_membername) ) 
        {
            $receiver_membername = "";
        }

        $reply = "";
        if( $message_id ) 
        {
            $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT message_id FROM tsue_messages_master \r\n\t\t\tWHERE message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")");
            if( !$MasterMessage ) 
            {
                ajax_message(get_phrase("messages_not_found"), "-ERROR-");
            }

            $checkReply = $TSUE["TSUE_Database"]->query_result("SELECT reply \r\n\t\t\tFROM tsue_messages_replies\r\n\t\t\tWHERE message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " ORDER BY reply_date ASC LIMIT 1");
            if( !$checkReply ) 
            {
                ajax_message(get_phrase("messages_not_found"), "-ERROR-");
            }

            $reply = "[quote]" . html_clean($TSUE["TSUE_Parser"]->clearTinymceP($checkReply["reply"])) . "[/quote]";
        }

        $autoDescription = autoDescription(2, "ajaxInputText");
        eval("\$messages_post_message = \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_post_message") . "\";");
        ajax_message($messages_post_message, "", false, get_phrase("messages_new_message"));
        break;
    case "mark_as_unread":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        globalize("post", array( "message_id" => "INT" ));
        if( !$message_id ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }

        $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT message_id FROM tsue_messages_master \r\n\t\tWHERE message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
        if( !$MasterMessage ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "is_unread" => 1 ), "message_id=" . $TSUE["TSUE_Database"]->escape($message_id));
        exit();
    case "messages_post_reply":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        if( !has_permission("canpost_a_reply_to_message") || isMuted($TSUE["TSUE_Member"]->info["muted"], "pm") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "message_id" => "INT", "reply" => "TRIM" ));
        if( !$message_id ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }
        else
        {
            if( !$reply ) 
            {
                ajax_message(get_phrase("valid_message_error"), "-ERROR-");
            }

        }

        $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT messages.is_unread, members.memberid \r\n\t\tFROM tsue_messages_master messages \r\n\t\tLEFT JOIN tsue_members members ON (members.memberid=IF(messages.owner_memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ",messages.receiver_memberid,messages.owner_memberid)) \r\n\t\tWHERE messages.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (messages.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR messages.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")");
        if( !$MasterMessage ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }
        else
        {
            check_flood("messages_post_reply");
            $BuildQuery = array( "message_id" => $message_id, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "reply_date" => TIMENOW, "reply" => $reply );
            if( $TSUE["TSUE_Database"]->insert("tsue_messages_replies", $BuildQuery) ) 
            {
                $last_reply_id = $TSUE["TSUE_Database"]->insert_id();
                autoAlert($reply, $message_id);
                $TSUE["TSUE_Database"]->update("tsue_members", array( "unread_messages" => array( "escape" => 0, "value" => "unread_messages + 1" ) ), "memberid = " . $TSUE["TSUE_Database"]->escape($MasterMessage["memberid"]));
                $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "is_unread" => 1, "viaAdminCP" => 0 ), "message_id=" . $TSUE["TSUE_Database"]->escape($message_id));
                $messages_post_reply = "";
                $ModerationLinks = "";
                $Data = array( "groupstyle" => $TSUE["TSUE_Member"]->info["groupstyle"], "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "gender" => $TSUE["TSUE_Member"]->info["gender"], "membername" => $TSUE["TSUE_Member"]->info["membername"], "reply_date" => TIMENOW, "reply" => $reply, "reply_id" => $last_reply_id, "message_id" => $message_id );
                require_once(REALPATH . "library/functions/functions_getMessages.php");
                $Content = prepareReplyList($Data);
                ajax_message("~last_reply_id:" . $last_reply_id . "~" . $Content);
            }
            else
            {
                ajax_message(get_phrase("database_error"), "-ERROR-");
            }

        }

        break;
    case "fetch_new_replies":
        globalize("post", array( "message_id" => "INT", "last_reply_id" => "INT" ));
        if( $message_id && $last_reply_id && !is_member_of("unregistered") ) 
        {
            $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT messages.*, members.memberid, members.membername, members.gender, g.groupstyle \r\n\t\t\tFROM tsue_messages_master messages \r\n\t\t\tLEFT JOIN tsue_members members ON (members.memberid=IF(messages.owner_memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ",messages.receiver_memberid,messages.owner_memberid)) \r\n\t\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\t\tWHERE messages.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (messages.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR messages.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")");
            if( !$MasterMessage ) 
            {
                $noAccess = true;
            }
            else
            {
                if( $MasterMessage["owner_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $MasterMessage["owner_deleted"] || $MasterMessage["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $MasterMessage["receiver_deleted"] ) 
                {
                    $noAccess = true;
                }
                else
                {
                    if( $MasterMessage["viaAdminCP"] && $MasterMessage["receiver_memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
                    {
                        $noAccess = true;
                    }

                }

            }

            if( !isset($noAccess) ) 
            {
                $Replies = $TSUE["TSUE_Database"]->query("SELECT r.*, m.membername, m.gender, g.groupstyle \r\n\t\t\t\tFROM tsue_messages_replies r \r\n\t\t\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\t\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\t\t\tWHERE r.reply_id > " . $TSUE["TSUE_Database"]->escape($last_reply_id) . " AND r.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " \r\n\t\t\t\tORDER BY r.reply_date ASC");
                if( $TSUE["TSUE_Database"]->num_rows($Replies) ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "is_unread" => 0 ), "message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")", true);
                    require_once(REALPATH . "library/functions/functions_getMessages.php");
                    $messages_replies_list = "";
                    $content_type = "message";
                    while( $R = $TSUE["TSUE_Database"]->fetch_assoc($Replies) ) 
                    {
                        $ModerationLinks = "";
                        if( has_permission("canreport") && $R["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
                        {
                            $content_id = $R["reply_id"];
                            eval("\$ModerationLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post") . "\";");
                        }

                        $messages_replies_list .= prepareReplyList($R);
                        $last_reply_id = $R["reply_id"];
                    }
                    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("message_replies_fetch_ajax") . "\";");
                    ajax_message("~last_reply_id:" . $last_reply_id . "~" . $Output);
                }

            }

        }

        break;
    case "messages_delete_messages":
        if( is_member_of("unregistered") ) 
        {
            ajax_message(get_phrase("login_required"), "-ERROR-");
        }

        globalize("post", array( "message_ids" => "TRIM" ));
        if( !$message_ids ) 
        {
            ajax_message(get_phrase("messages_not_found"), "-ERROR-");
        }

        $message_ids = tsue_explode(",", $message_ids);
        foreach( $message_ids as $i => $message_id ) 
        {
            $message_id = intval($message_id);
            if( !$message_id ) 
            {
                unset($message_ids[$i]);
            }

        }
        check_flood("messages_delete_messages");
        foreach( $message_ids as $message_id ) 
        {
            $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT owner_memberid, receiver_memberid, owner_deleted, receiver_deleted \r\n\t\t\tFROM tsue_messages_master \r\n\t\t\tWHERE message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ") \r\n\t\t\tLIMIT 1");
            if( $MasterMessage ) 
            {
                if( $MasterMessage["owner_memberid"] == $MasterMessage["receiver_memberid"] ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "owner_deleted" => 1, "receiver_deleted" => 1 ), "message_id = " . $TSUE["TSUE_Database"]->escape($message_id));
                }
                else
                {
                    if( $MasterMessage["owner_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !$MasterMessage["owner_deleted"] ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "owner_deleted" => 1 ), "message_id = " . $TSUE["TSUE_Database"]->escape($message_id));
                    }
                    else
                    {
                        if( $MasterMessage["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !$MasterMessage["receiver_deleted"] ) 
                        {
                            $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "receiver_deleted" => 1 ), "message_id = " . $TSUE["TSUE_Database"]->escape($message_id));
                        }

                    }

                }

            }

        }
        ajax_message(get_phrase("messages_deleted"));
}


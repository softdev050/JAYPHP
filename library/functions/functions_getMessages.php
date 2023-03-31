<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function getMessages()
{
    global $TSUE;
    $TSUE["TSUE_Database"]->update("tsue_members", array( "unread_messages" => 0 ), "memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]), true);
    $totalMessages = $showCount = 0;
    $read_messages = "";
    if( !defined("IS_AJAX") ) 
    {
        $totalMessages = $TSUE["TSUE_Database"]->row_count("SELECT message_id \r\n\t\tFROM tsue_messages_master \r\n\t\tWHERE owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " \r\n\t\tGROUP BY message_id");
    }

    $MQuery = $TSUE["TSUE_Database"]->query("SELECT messages.*, max(reply.reply_date) as reply_date, members.memberid, members.membername, members.gender, g.groupstyle \r\n\tFROM tsue_messages_master messages \r\n\tINNER JOIN tsue_messages_replies reply USING(message_id) \r\n\tINNER JOIN tsue_members members ON (members.memberid=IF(messages.owner_memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ",messages.receiver_memberid,messages.owner_memberid)) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE messages.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR messages.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " \r\n\tGROUP BY messages.message_id ORDER BY reply_date DESC LIMIT " . getSetting("global_settings", "website_messages_perpage", 15));
    if( $TSUE["TSUE_Database"]->num_rows($MQuery) ) 
    {
        $read_messages = array(  );
        for( $ShowMemberMessages = ""; $M = $TSUE["TSUE_Database"]->fetch_assoc($MQuery); $showCount++ ) 
        {
            if( !($M["viaAdminCP"] && $M["receiver_memberid"] != $TSUE["TSUE_Member"]->info["memberid"]) ) 
            {
                $ShowMemberMessages .= prepareMessageList($M);
                $read_messages[] = $M["message_id"];
            }

        }
        $read_messages = implode(",", $read_messages);
    }
    else
    {
        $ShowMemberMessages = show_information(get_phrase("messages_no_message") . ((defined("IS_AJAX") ? get_phrase("click_here_to_create_a_new_message") : "")), "", 0, "messages_no_message");
    }

    if( $ShowMemberMessages == "" ) 
    {
        $ShowMemberMessages = show_information(get_phrase("messages_no_message") . ((defined("IS_AJAX") ? get_phrase("click_here_to_create_a_new_message") : "")), "", 0, "messages_no_message");
    }

    eval("\$Content = \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages") . "\";");
    if( !defined("IS_AJAX") && $showCount < $totalMessages ) 
    {
        eval("\$Content .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_show_more") . "\";");
    }

    return $Content;
}

function prepareMessageList($M)
{
    global $TSUE;
    if( $M["owner_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $M["owner_deleted"] || $M["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $M["receiver_deleted"] ) 
    {
        return "";
    }

    $Unread = "";
    if( $M["is_unread"] ) 
    {
        eval("\$Unread = \"" . $TSUE["TSUE_Template"]->LoadTemplate("unread") . "\";");
    }

    $_avatar = get_member_avatar($M["memberid"], $M["gender"], "s");
    $_membername = $M["membername"];
    $_memberid = $M["memberid"];
    $message_date = convert_relative_time($M["message_date"]);
    $_message_id = $M["message_id"];
    $Subject = strip_tags($M["subject"]);
    $_alt = "";
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    $_membername = getMembername($M["membername"], $M["groupstyle"]);
    eval("\$ShowMemberName = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    eval("\$ShowMemberMessages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_list") . "\";");
    return $ShowMemberMessages;
}

function prepareReplyList($R)
{
    global $TSUE;
    global $ModerationLinks;
    $_avatar = get_member_avatar($R["memberid"], $R["gender"], "s");
    $_membername = $R["membername"];
    $_memberid = $R["memberid"];
    $reply_date = convert_relative_time($R["reply_date"]);
    $reply = $TSUE["TSUE_Parser"]->parse($R["reply"]);
    $_alt = "";
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    $_membername = getMembername($R["membername"], $R["groupstyle"]);
    eval("\$messages_replies_list = \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_replies_list") . "\";");
    return $messages_replies_list;
}

function postMessage($receiver_membername, $subject, $message, $prepareMessageList = true)
{
    global $TSUE;
    if( !is_valid_string($receiver_membername) || $receiver_membername == $TSUE["TSUE_Member"]->info["membername"] ) 
    {
        ajax_message(get_phrase("messages_valid_recipient"), "-ERROR-");
    }

    if( !$subject ) 
    {
        ajax_message(get_phrase("messages_invalid_subject"), "-ERROR-");
    }

    if( !$message ) 
    {
        ajax_message(get_phrase("valid_message_error"), "-ERROR-");
    }

    $Receiver = $TSUE["TSUE_Database"]->query_result("SELECT m.memberid, m.membergroupid, m.gender, g.groupstyle \r\n\tFROM tsue_members m \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE m.membername = " . $TSUE["TSUE_Database"]->escape($receiver_membername));
    if( !$Receiver ) 
    {
        ajax_message(get_phrase("member_not_found"), "-ERROR-");
    }
    else
    {
        if( $Receiver["membergroupid"] == is_member_of("banned", true) ) 
        {
            ajax_message(get_phrase("messages_valid_recipient"), "-ERROR-");
        }

    }

    check_flood("messages_new_message");
    if( $message_id = sendPM($subject, $TSUE["TSUE_Member"]->info["memberid"], $Receiver["memberid"], $message) ) 
    {
        $Data = array( "groupstyle" => $Receiver["groupstyle"], "owner_memberid" => $TSUE["TSUE_Member"]->info["memberid"], "owner_deleted" => 0, "receiver_memberid" => $Receiver["memberid"], "receiver_deleted" => 0, "memberid" => $Receiver["memberid"], "gender" => $Receiver["gender"], "membername" => $receiver_membername, "message_date" => TIMENOW, "message_id" => $message_id, "subject" => strip_tags($subject), "is_unread" => 1 );
        autoAlert($message, $message_id);
        if( $prepareMessageList ) 
        {
            ajax_message(preparemessagelist($Data));
        }

    }

    if( $prepareMessageList ) 
    {
        ajax_message(get_phrase("database_error"), "-ERROR-");
    }

}



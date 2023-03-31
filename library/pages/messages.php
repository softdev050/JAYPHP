<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "messages.php");
require("./library/init/init.php");
if( is_member_of("unregistered") ) 
{
    show_error(get_phrase("permission_denied"));
}

$Page_Title = get_phrase("messages_title");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=messages&amp;pid=" . PAGEID ));
globalize("get", array( "message_id" => "INT" ));
require_once(REALPATH . "library/functions/functions_getMessages.php");
if( $message_id ) 
{
    AddBreadcrumb(array( get_phrase("messages_view_message") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=messages&amp;pid=" . PAGEID . "&amp;message_id=" . $message_id ));
    $MasterMessage = $TSUE["TSUE_Database"]->query_result("SELECT messages.*, members.memberid, members.membername, members.gender, g.groupstyle \r\n\tFROM tsue_messages_master messages \r\n\tLEFT JOIN tsue_members members ON (members.memberid=IF(messages.owner_memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . ",messages.receiver_memberid,messages.owner_memberid)) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tWHERE messages.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (messages.owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR messages.receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")");
    if( !$MasterMessage ) 
    {
        $ShowMemberMessages = show_information(get_phrase("messages_not_found"), "", 0);
    }
    else
    {
        if( $MasterMessage["owner_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $MasterMessage["owner_deleted"] || $MasterMessage["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $MasterMessage["receiver_deleted"] ) 
        {
            $ShowMemberMessages = show_information(get_phrase("messages_not_found"), "", 0);
        }
        else
        {
            if( $MasterMessage["viaAdminCP"] && $MasterMessage["receiver_memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
            {
                $ShowMemberMessages = show_information(get_phrase("messages_not_found"), "", 0);
            }
            else
            {
                $messages_post_reply = "";
                if( has_permission("canpost_a_reply_to_message") && $MasterMessage["receiver_memberid"] != $MasterMessage["owner_memberid"] ) 
                {
                    eval("\$messages_post_reply = \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_post_reply") . "\";");
                }

                $Replies = $TSUE["TSUE_Database"]->query("SELECT r.*, m.membername, m.gender, g.groupstyle \r\n\t\tFROM tsue_messages_replies r \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE r.message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " \r\n\t\tORDER BY r.reply_date ASC");
                if( !$TSUE["TSUE_Database"]->num_rows($Replies) ) 
                {
                    $ShowMemberMessages = show_information(get_phrase("messages_not_found"), "", 0);
                }
                else
                {
                    $dropDownMenuLinks = "\r\n\t\t\t" . (($MasterMessage["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] ? "\r\n\t\t\t<li><a href=\"#\" id=\"pm_markAsUnread\" rel=\"" . $message_id . "\">" . get_phrase("mark_as_unread") . "</a></li>" : "")) . "\r\n\t\t\t\r\n\t\t\t<li><a href=\"#\" id=\"pm_DeleteMessage\" rel=\"" . $message_id . "\">" . get_phrase("delete_message") . "</a></li>\r\n\t\t\t<li><a href=\"#\" id=\"pm_forwardMessage\" rel=\"" . $message_id . "\">" . get_phrase("forward_message") . "</a></li>";
                    eval("\$messageTools = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dropDownMenu") . "\";");
                    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("message_tools") . "\";");
                    $last_reply_sender = $MasterMessage["memberid"];
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
                        $last_reply_sender = $R["memberid"];
                        $last_reply_id = $R["reply_id"];
                    }
                    eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("messages_replies") . "\";");
                }

                if( $MasterMessage["is_unread"] == 1 ) 
                {
                    if( $MasterMessage["receiver_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $last_reply_sender != $TSUE["TSUE_Member"]->info["memberid"] || $MasterMessage["owner_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && $last_reply_sender != $TSUE["TSUE_Member"]->info["memberid"] || $MasterMessage["owner_memberid"] == $MasterMessage["receiver_memberid"] ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_messages_master", array( "is_unread" => 0 ), "message_id = " . $TSUE["TSUE_Database"]->escape($message_id) . " AND (owner_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " OR receiver_memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . ")", true);
                    }

                    $TSUE["TSUE_Database"]->update("tsue_members", array( "unread_messages" => array( "escape" => 0, "value" => "IF(unread_messages > 0, unread_messages-1, 0)" ) ), "memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]), true);
                }

            }

        }

    }

}

if( !isset($Output) ) 
{
    $Output = getMessages();
}

PrintOutput($Output, $Page_Title);


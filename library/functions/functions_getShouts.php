<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function canViewShoutbox()
{
    global $TSUE;
    if( !has_permission("canview_shout") ) 
    {
        return false;
    }

    $Result = $TSUE["TSUE_Database"]->query("SELECT cviewpermissions FROM tsue_shoutbox_channels WHERE cid = " . getActiveShoutboxChannel());
    if( !$TSUE["TSUE_Database"]->num_rows($Result) ) 
    {
        return false;
    }

    $Row = $TSUE["TSUE_Database"]->fetch_assoc($Result);
    if( $Row["cviewpermissions"] && !hasViewPermission($Row["cviewpermissions"]) ) 
    {
        return false;
    }

    return true;
}

function canPostShoutbox()
{
    global $TSUE;
    if( !has_permission("canview_shout") || !has_permission("canpost_shout") || isMuted($TSUE["TSUE_Member"]->info["muted"], "shoutbox") ) 
    {
        return false;
    }

    $Result = $TSUE["TSUE_Database"]->query("SELECT cviewpermissions, cshoutpermissions FROM tsue_shoutbox_channels WHERE cid = " . getActiveShoutboxChannel());
    if( !$TSUE["TSUE_Database"]->num_rows($Result) ) 
    {
        return false;
    }

    $Row = $TSUE["TSUE_Database"]->fetch_assoc($Result);
    if( $Row["cviewpermissions"] && !hasViewPermission($Row["cviewpermissions"]) || $Row["cshoutpermissions"] && !hasViewPermission($Row["cshoutpermissions"]) ) 
    {
        return false;
    }

    return true;
}

function buildShoutboxChannels()
{
    global $TSUE;
    $Result = $TSUE["TSUE_Database"]->query("SELECT cid, cname, cviewpermissions FROM tsue_shoutbox_channels ORDER BY `sort` ASC");
    if( $TSUE["TSUE_Database"]->num_rows($Result) ) 
    {
        $getActiveShoutboxChannel = getActiveShoutboxChannel();
        $Channels = "";
        while( $Row = $TSUE["TSUE_Database"]->fetch_assoc($Result) ) 
        {
            if( !$Row["cviewpermissions"] || hasViewPermission($Row["cviewpermissions"]) ) 
            {
                $Channels .= "\r\n\t\t\t\t<option value=\"" . $Row["cid"] . "\"" . (($getActiveShoutboxChannel == $Row["cid"] ? " selected=\"selected\"" : "")) . ">" . $Row["cname"] . "</option>";
            }

        }
        $Channels = "\r\n\t\t<div class=\"shoutboxChannels\">\r\n\t\t\t<select name=\"shoutboxCID\" rel=\"shoutboxCID\">\r\n\t\t\t\t<optgroup label=\"" . get_phrase("shoutbox_channels") . "\">\r\n\t\t\t\t\t" . $Channels . "\r\n\t\t\t\t</optgroup>\r\n\t\t\t</select>\r\n\t\t</div>";
        return $Channels;
    }

}

function getDefaultChannelID()
{
    return 1;
}

function getActiveShoutboxChannel()
{
    global $TSUE;
    $shoutboxChannelID = (isset($_COOKIE["shoutboxChannelID"]) && $_COOKIE["shoutboxChannelID"] ? intval($_COOKIE["shoutboxChannelID"]) : 0);
    if( !$shoutboxChannelID ) 
    {
        $shoutboxChannelID = getdefaultchannelid();
    }
    else
    {
        $Result = $TSUE["TSUE_Database"]->query("SELECT cviewpermissions FROM tsue_shoutbox_channels WHERE cid = " . $shoutboxChannelID);
        if( !$TSUE["TSUE_Database"]->num_rows($Result) ) 
        {
            $shoutboxChannelID = getdefaultchannelid();
        }
        else
        {
            $Row = $TSUE["TSUE_Database"]->fetch_assoc($Result);
            if( $Row["cviewpermissions"] && !hasViewPermission($Row["cviewpermissions"]) ) 
            {
                $shoutboxChannelID = getdefaultchannelid();
            }

        }

    }

    cookie_set("shoutboxChannelID", $shoutboxChannelID);
    return $shoutboxChannelID;
}

function prepareNewShouts($lastSID)
{
    global $TSUE;
    $Shouts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE s.sid, s.memberid, s.system, s.sdate, s.smessage, m.membername, m.gender, g.groupstyle, p.pluginOptions \r\n\tFROM tsue_shoutbox s \r\n\tLEFT JOIN tsue_members m USING(memberid) \r\n\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\tLEFT JOIN tsue_plugins p ON (p.pluginid=3) \r\n\tWHERE s.sid > " . $TSUE["TSUE_Database"]->escape($lastSID) . "  AND s.cid = " . getactiveshoutboxchannel() . "\r\n\tORDER BY s.sdate DESC LIMIT 100");
    return prepareShouts($Shouts);
}

function removeLastEmptyLines($smessage)
{
    $smessage = trim($smessage);
    while( substr($smessage, -6) == "<br />" ) 
    {
        $smessage = substr_replace($smessage, "", -6);
    }
    return $smessage;
}

function prepareShouts($Shouts = false)
{
    global $TSUE;
    $ShoutBOXRows = "";
    $lastSID = 0;
    if( !$Shouts ) 
    {
        $Shouts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE s.sid, s.memberid, s.system, s.sdate, s.smessage, m.membername, m.gender, g.groupstyle, p.pluginOptions \r\n\t\tFROM tsue_shoutbox s \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tLEFT JOIN tsue_plugins p ON (p.pluginid=3) \r\n\t\tWHERE s.sid >= 1 AND s.cid = " . getactiveshoutboxchannel() . "\r\n\t\tORDER BY s.sdate DESC LIMIT 100");
    }

    if( $TSUE["TSUE_Database"]->num_rows($Shouts) ) 
    {
        $pluginOptions = "";
        while( $S = $TSUE["TSUE_Database"]->fetch_assoc($Shouts) ) 
        {
            $_sid = $S["sid"];
            $pluginOptions = (!$pluginOptions ? unserialize($S["pluginOptions"]) : $pluginOptions);
            $date_time_format = getPluginOption($pluginOptions, "date_time_format", "H:i");
            $_sdate = convert_time($S["sdate"], $date_time_format);
            $_membername = ($S["membername"] ? getMembername($S["membername"], $S["groupstyle"]) : get_phrase(($S["system"] ? "shoutbox_announcement_bot_name" : "guest")));
            $_memberid = $S["memberid"];
            $_avatar = get_member_avatar($S["memberid"], $S["gender"], "s");
            $_smessage = $TSUE["TSUE_Parser"]->parse($S["smessage"]);
            eval("\$ShowMemberName = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            if( has_permission("canedit_own_shout") && $S["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_shout") ) 
            {
                eval("\$shoutbox_edit_shout_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_edit_shout_link") . "\";");
            }
            else
            {
                $shoutbox_edit_shout_link = "";
            }

            if( has_permission("candelete_own_shout") && $S["memberid"] === $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_shout") ) 
            {
                eval("\$shoutbox_delete_shout_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_delete_shout_link") . "\";");
            }
            else
            {
                $shoutbox_delete_shout_link = "";
            }

            if( $lastSID < $_sid ) 
            {
                $lastSID = $_sid;
            }

            eval("\$ShoutBOXRows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_rows") . "\";");
        }
    }

    return array( "lastSID" => $lastSID, "ShoutBOXRows" => $ShoutBOXRows );
}

function handleShoutboxCommands($smessage)
{
    global $TSUE;
    $explodeMessage = tsue_explode(" ", $TSUE["TSUE_Parser"]->clearTinymceP($smessage));
    if( !empty($explodeMessage) ) 
    {
        switch( $explodeMessage["0"] ) 
        {
            case "/pm":
                if( !has_permission("canpost_a_new_message") ) 
                {
                    ajax_message(get_phrase("permission_denied"), "-ERROR-");
                }

                $receiver_membername = $explodeMessage["1"];
                $message = trim($TSUE["TSUE_Parser"]->clearTinymceP(str_replace($explodeMessage["0"] . " " . $explodeMessage["1"], "", $smessage)));
                $subject = get_phrase("pm_via_shoutbox");
                require_once(REALPATH . "library/functions/functions_getMessages.php");
                postMessage($receiver_membername, $subject, $message, false);
                ajax_message(get_phrase("message_posted"), "-DONE-");
                break;
            case "/prune":
                if( has_permission("canprune_shouts") ) 
                {
                    if( isset($explodeMessage["1"]) ) 
                    {
                        $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($explodeMessage["1"]));
                        if( !$Member ) 
                        {
                            ajax_message(get_phrase("member_not_found"), "-ERROR-");
                        }

                        $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "memberid=" . $TSUE["TSUE_Database"]->escape($Member["memberid"]) . " AND cid = " . getactiveshoutboxchannel());
                        $phrase = get_phrase("x_shouts_pruned", $explodeMessage["1"]);
                        logAction($phrase);
                        ajax_message($phrase, "-DONE-");
                    }
                    else
                    {
                        $TSUE["TSUE_Database"]->delete("tsue_shoutbox", "cid = " . getactiveshoutboxchannel());
                        $phrase = get_phrase("shoutbox_pruned");
                        logAction($phrase);
                        ajax_message($phrase, "-INFORMATION-");
                    }

                }

                break;
            case "/notice":
                if( has_permission("canpost_notice") ) 
                {
                    if( !isset($explodeMessage["1"]) || !$explodeMessage["1"] ) 
                    {
                        ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                    }

                    $TSUE["TSUE_Database"]->insert("tsue_shoutbox", array( "memberid" => 0, "system" => 1, "sdate" => TIMENOW, "smessage" => str_replace($explodeMessage["0"], "", $smessage), "cid" => getactiveshoutboxchannel() ));
                    ajax_message(get_phrase("message_posted"), "-DONE-");
                }

        }
    }

}



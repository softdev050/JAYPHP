<?php 
define("SCRIPTNAME", "market.php");
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

if( $TSUE["action"] == "market" ) 
{
    if( !has_permission("canview_market") ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    $currentMemberPoints = $TSUE["TSUE_Member"]->info["points"];
    if( $TSUE["do"] == "purchase" ) 
    {
        if( !has_permission("canpurchase_item") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        globalize("post", array( "itemid" => "INT" ));
        if( !$itemid ) 
        {
            ajax_message(get_phrase("market_invalid_item"), "-ERROR-");
        }

        $item = $TSUE["TSUE_Database"]->query_result("SELECT title, required_points, item_type, amount, permissions FROM tsue_market WHERE itemid = " . $TSUE["TSUE_Database"]->escape($itemid));
        if( !$item ) 
        {
            ajax_message(get_phrase("market_invalid_item"), "-ERROR-");
        }

        if( $TSUE["TSUE_Member"]->info["points"] < $item["required_points"] ) 
        {
            ajax_message(get_phrase("market_not_enough_points"), "-ERROR-");
        }

        if( $item["permissions"] && !hasViewPermission($item["permissions"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        switch( $item["item_type"] ) 
        {
            case "invites_left":
            case "uploaded":
                if( profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( $item["item_type"] => array( "escape" => 0, "value" => $item["item_type"] . "+" . $item["amount"] ), "points" => array( "escape" => 0, "value" => "points-" . $item["required_points"] ) )) ) 
                {
                    if( $currentMemberPoints == $TSUE["TSUE_Member"]->info["points"] ) 
                    {
                        $TSUE["TSUE_Member"]->info["points"] -= $item["required_points"];
                    }

                    ajax_message(get_phrase("market_item_x_has_been_purchased") . "|" . get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"])));
                }

                break;
            case "custom_title":
                globalize("post", array( "custom_title" => "TRIM" ));
                if( $custom_title && profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( $item["item_type"] => $custom_title, "points" => array( "escape" => 0, "value" => "points-" . $item["required_points"] ) )) ) 
                {
                    if( $currentMemberPoints == $TSUE["TSUE_Member"]->info["points"] ) 
                    {
                        $TSUE["TSUE_Member"]->info["points"] -= $item["required_points"];
                    }

                    ajax_message(get_phrase("market_item_x_has_been_purchased") . "|" . get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"])));
                }

                eval("\$market_custom_title_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_custom_title_form") . "\";");
                ajax_message($market_custom_title_form);
                break;
            case "gift":
                globalize("post", array( "membername" => "TRIM" ));
                if( $membername ) 
                {
                    $checkMember = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($membername));
                    if( !$checkMember || $checkMember["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] ) 
                    {
                        ajax_message(get_phrase("member_not_found"), "-ERROR-");
                    }

                    profileUpdate($checkMember["memberid"], array( "uploaded" => array( "escape" => 0, "value" => "uploaded+" . $item["amount"] ) ));
                    alert_member($checkMember["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "market", $itemid, "gift", $item["amount"]);
                    if( profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "points" => array( "escape" => 0, "value" => "points-" . $item["required_points"] ) )) ) 
                    {
                        if( $currentMemberPoints == $TSUE["TSUE_Member"]->info["points"] ) 
                        {
                            $TSUE["TSUE_Member"]->info["points"] -= $item["required_points"];
                        }

                        ajax_message(get_phrase("market_item_x_has_been_purchased") . "|" . get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"])));
                    }

                }

                eval("\$market_gift_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_gift_form") . "\";");
                ajax_message($market_gift_form);
                break;
            case "hitrun":
                globalize("post", array( "tid" => "INT" ));
                if( getSetting("xbt", "active") ) 
                {
                    $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.fid as tid, t.name, t.size FROM xbt_files_users p INNER JOIN tsue_torrents t ON(p.fid=t.tid) WHERE p.isWarned = 1 AND p.uid=" . $TSUE["TSUE_Member"]->info["memberid"]);
                }
                else
                {
                    $Peers = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.tid, t.name, t.size FROM tsue_torrents_peers p INNER JOIN tsue_torrents t USING(tid) WHERE p.isWarned = 1 AND p.memberid=" . $TSUE["TSUE_Member"]->info["memberid"]);
                }

                if( !$TSUE["TSUE_Database"]->num_rows($Peers) ) 
                {
                    ajax_message(get_phrase("you_dont_have_any_hitrun_warning"), "-ERROR-");
                }

                if( $tid ) 
                {
                    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT size FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
                    if( !$Torrent || !$Torrent["size"] ) 
                    {
                        ajax_message(get_phrase("message_content_error"), "-ERROR-");
                    }

                    if( getSetting("xbt", "active") ) 
                    {
                        $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 0, "uploaded" => array( "escape" => 0, "value" => "IF(downloaded>0 && uploaded<" . $Torrent["size"] . "," . $Torrent["size"] . ",uploaded)" ) ), "fid=" . $TSUE["TSUE_Database"]->escape($tid) . " AND uid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                    }
                    else
                    {
                        $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 0, "total_uploaded" => array( "escape" => 0, "value" => "IF(total_downloaded>0 && total_uploaded<" . $Torrent["size"] . "," . $Torrent["size"] . ",total_uploaded)" ) ), "tid=" . $TSUE["TSUE_Database"]->escape($tid) . " AND memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                    }

                    $TSUE["TSUE_Database"]->update("tsue_member_profile", array( "hitRuns" => array( "escape" => 0, "value" => "IF(hitRuns>0,hitRuns-1,0)" ) ), "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
                    if( profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "points" => array( "escape" => 0, "value" => "points-" . $item["required_points"] ) )) ) 
                    {
                        if( $currentMemberPoints == $TSUE["TSUE_Member"]->info["points"] ) 
                        {
                            $TSUE["TSUE_Member"]->info["points"] -= $item["required_points"];
                        }

                        ajax_message(get_phrase("market_item_x_has_been_purchased") . "|" . get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"])));
                    }

                }

                $selectOptions = "";
                while( $Peer = $TSUE["TSUE_Database"]->fetch_assoc($Peers) ) 
                {
                    $selectOptions .= "\r\n\t\t\t\t\t<option value=\"" . $Peer["tid"] . "\">" . strip_tags($Peer["name"]) . " (" . friendly_size($Peer["size"]) . ")</option>";
                }
                eval("\$market_hitrun_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_hitrun_form") . "\";");
                ajax_message($market_hitrun_form);
                break;
            case "change_membername":
                globalize("post", array( "new_membername" => "TRIM" ));
                if( $new_membername ) 
                {
                    if( !is_valid_membername($new_membername) ) 
                    {
                        $Error = get_phrase("membername_match_regular_expression_error");
                    }
                    else
                    {
                        if( strlen($new_membername) < $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"] ) 
                        {
                            $Error = get_phrase("invalid_membername_min_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"]);
                        }
                        else
                        {
                            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_max_char"] < strlen($new_membername) ) 
                            {
                                $Error = get_phrase("invalid_membername_max_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_max_char"]);
                            }
                            else
                            {
                                $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($new_membername));
                                if( $Member ) 
                                {
                                    $Error = get_phrase("invalid_membername_in_use");
                                }
                                else
                                {
                                    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_illegal_member_names"] ) 
                                    {
                                        $member_name_illegal_member_names = preg_split("/\\r?\\n/", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_illegal_member_names"], -1, PREG_SPLIT_NO_EMPTY);
                                        if( $member_name_illegal_member_names ) 
                                        {
                                            foreach( $member_name_illegal_member_names as $name ) 
                                            {
                                                $name = trim($name);
                                                if( $name === "" ) 
                                                {
                                                    continue;
                                                }

                                                if( stripos($new_membername, $name) !== false ) 
                                                {
                                                    $Error = get_phrase("invalid_membername_in_use");
                                                }

                                            }
                                        }

                                    }

                                }

                            }

                        }

                    }

                    if( isset($Error) ) 
                    {
                        ajax_message($Error, "-ERROR-");
                    }
                    else
                    {
                        $TSUE["TSUE_Database"]->update("tsue_members", array( "membername" => $new_membername ), "memberid = " . $TSUE["TSUE_Member"]->info["memberid"]);
                        if( $TSUE["TSUE_Database"]->affected_rows() && profileUpdate($TSUE["TSUE_Member"]->info["memberid"], array( "points" => array( "escape" => 0, "value" => "points-" . $item["required_points"] ) )) ) 
                        {
                            if( $currentMemberPoints == $TSUE["TSUE_Member"]->info["points"] ) 
                            {
                                $TSUE["TSUE_Member"]->info["points"] -= $item["required_points"];
                            }

                            ajax_message(get_phrase("market_item_x_has_been_purchased") . "|" . get_phrase("market_you_have_x_points", friendly_number_format($TSUE["TSUE_Member"]->info["points"])));
                        }

                    }

                }

                eval("\$market_change_membername_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("market_change_membername_form") . "\";");
                ajax_message($market_change_membername_form);
        }
    }

}



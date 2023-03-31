<?php 
define("SCRIPTNAME", "request.php");
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

if( $TSUE["action"] == "request" ) 
{
    if( $TSUE["do"] == "new" || $TSUE["do"] == "saveNew" ) 
    {
        if( is_member_of("unregistered") || !has_permission("canrequest_torrent") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["TSUE_Member"]->info["permissions"]["request_limit"] ) 
        {
            $totalRequests = $TSUE["TSUE_Database"]->row_count("SELECT rid FROM tsue_requests WHERE tid = 0 AND memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
            if( $TSUE["TSUE_Member"]->info["permissions"]["request_limit"] <= $totalRequests ) 
            {
                ajax_message(get_phrase("request_limit_reached"), "-ERROR-");
            }

        }

        if( $TSUE["do"] == "saveNew" ) 
        {
            globalize("post", array( "title" => "TRIM", "description" => "TRIM", "category" => "INT" ));
            if( !$title || !$description || !$category ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $requestLength = strlen($description);
            $request_torrent_chars_limit = getSetting("global_settings", "request_torrent_chars_limit", 255);
            if( $request_torrent_chars_limit < $requestLength ) 
            {
                ajax_message(get_phrase("post_request_char_limit_error", friendly_number_format($request_torrent_chars_limit), friendly_number_format($requestLength)), "-ERROR-");
            }

            $requestLength = strlen($title);
            if( $request_torrent_chars_limit < $requestLength ) 
            {
                ajax_message(get_phrase("post_request_char_limit_error", friendly_number_format($request_torrent_chars_limit), friendly_number_format($requestLength)), "-ERROR-");
            }

            $checkCategory = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE cname FROM tsue_torrents_categories WHERE cid=" . $TSUE["TSUE_Database"]->escape($category));
            if( !$checkCategory ) 
            {
                ajax_message(get_phrase("torrent_upload_invalid_cid"), "-ERROR-");
            }

            $buildQuery = array( "tid" => 0, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "title" => $title, "description" => $description, "voters" => serialize(array(  )), "cid" => $category );
            if( !$TSUE["TSUE_Database"]->insert("tsue_requests", $buildQuery) ) 
            {
                ajax_message(get_phrase("database_error"), "-ERROR-");
            }

            deleteCache("TSUEPlugin_recentRequests_");
            $rid = $TSUE["TSUE_Database"]->insert_id();
            $searchMembergroups = searchPermissionInMembergroups("canfill_request");
            if( $searchMembergroups ) 
            {
                $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
                if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
                {
                    while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                    {
                        alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "requests", $rid, "new");
                    }
                }

            }

            require(REALPATH . "library/functions/functions_getRequests.php");
            $Request = array( "rid" => $rid, "tid" => 0, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "title" => $title, "description" => $description, "gender" => $TSUE["TSUE_Member"]->info["gender"], "membername" => $TSUE["TSUE_Member"]->info["membername"], "groupstyle" => $TSUE["TSUE_Member"]->info["groupstyle"], "voters" => serialize(array(  )), "cid" => $category, "cname" => $checkCategory["cname"] );
            ajax_message(prepareRequest($Request));
        }

        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $prepareTorrentCategoriesSelectbox = prepareTorrentCategoriesSelectbox();
        eval("\$request_a_torrent_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("request_a_torrent_form") . "\";");
        ajax_message($request_a_torrent_form, "", false, get_phrase("request_a_torrent"));
    }

    if( $TSUE["do"] == "search" ) 
    {
        if( is_member_of("unregistered") || !has_permission("canrequest_torrent") ) 
        {
            exit();
        }

        globalize("post", array( "title" => "TRIM", "description" => "TRIM" ));
        if( strlen($title) < 3 ) 
        {
            exit();
        }

        $searchString = explodeSearchKeywords("t.name", $title);
        $Images = array(  );
        if( $searchString ) 
        {
            $TorrentsQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.tid, t.name, " . $searchString . " as SCORE, a.filename FROM tsue_torrents t INNER JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) WHERE " . $searchString . " GROUP BY t.tid ORDER BY SCORE DESC, t.added DESC LIMIT 10");
            if( $TSUE["TSUE_Database"]->num_rows($TorrentsQuery) ) 
            {
                while( $Torrent = $TSUE["TSUE_Database"]->fetch_assoc($TorrentsQuery) ) 
                {
                    if( 4 < $Torrent["SCORE"] ) 
                    {
                        $title = addslashes(strip_tags($Torrent["name"]));
                        eval("\$Images[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("similarTorrents") . "\";");
                    }

                }
            }

            if( !empty($Images) ) 
            {
                eval("\$clear = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clear") . "\";");
                $Images = implode(" ", $Images);
                ajax_message(get_phrase("post_request_confirmation") . $Images . $clear, "", false);
            }

        }

    }

    if( $TSUE["do"] == "vote" ) 
    {
        globalize("post", array( "rid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("canvote_request") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Request = $TSUE["TSUE_Database"]->query_result("SELECT memberid, voters FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Request ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $Voters = ($Request["voters"] ? unserialize($Request["voters"]) : array(  ));
        if( $TSUE["TSUE_Member"]->info["memberid"] == $Request["memberid"] || in_array($TSUE["TSUE_Member"]->info["memberid"], $Voters) ) 
        {
            ajax_message(get_phrase("you_have_already_voted_on_this_request"), "-ERROR-");
        }

        $Voters[] = $TSUE["TSUE_Member"]->info["memberid"];
        $TSUE["TSUE_Database"]->update("tsue_requests", array( "voters" => serialize($Voters) ), "rid = " . $TSUE["TSUE_Database"]->escape($rid));
        exit( get_phrase("thank_you") . "|" . friendly_number_format(count($Voters)) );
    }

    if( $TSUE["do"] == "fill" || $TSUE["do"] == "saveFill" ) 
    {
        globalize("post", array( "rid" => "INT", "tid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("canfill_request") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Request = $TSUE["TSUE_Database"]->query_result("SELECT tid, memberid, title FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Request ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Request["tid"] && !has_permission("canreset_request") ) 
        {
            ajax_message(get_phrase("request_is_already_filled"), "-ERROR-");
        }

        if( $TSUE["do"] == "saveFill" ) 
        {
            if( !$tid ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name, owner FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
            if( !$Torrent ) 
            {
                ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
            }

            $TSUE["TSUE_Database"]->update("tsue_requests", array( "tid" => $tid, "filled_by" => $TSUE["TSUE_Member"]->info["memberid"] ), "rid = " . $TSUE["TSUE_Database"]->escape($rid));
            alert_member($Request["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "requests", $tid, "filled", $rid);
            updateMemberPoints(getSetting("global_settings", "points_fill_request", 0), $Torrent["owner"]);
            ajax_message(get_phrase("request_has_been_filled"), "-DONE-");
        }

        eval("\$fill_request_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("fill_request_form") . "\";");
        ajax_message($fill_request_form, "", false, get_phrase("fill_this_request") . ": " . substr(strip_tags($Request["title"]), 0, 85) . " ...");
    }

    if( $TSUE["do"] == "reset" ) 
    {
        globalize("post", array( "rid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("canreset_request") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Request = $TSUE["TSUE_Database"]->query_result("SELECT tid,title,filled_by FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Request || !$Request["tid"] ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->update("tsue_requests", array( "tid" => 0, "filled_by" => 0 ), "rid = " . $TSUE["TSUE_Database"]->escape($rid));
        $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT owner FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($Request["tid"]));
        if( $Torrent ) 
        {
            updateMemberPoints(getSetting("global_settings", "points_fill_request", 0), $Torrent["owner"], false);
        }

        $Phrase = get_phrase("request_has_been_reset", substr(strip_tags($Request["title"]), 0, 85), $TSUE["TSUE_Member"]->info["membername"]);
        logAction($Phrase);
        ajax_message($Phrase);
    }

    if( $TSUE["do"] == "delete" ) 
    {
        globalize("post", array( "rid" => "INT", "reason" => "TRIM" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("candelete_request") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Request = $TSUE["TSUE_Database"]->query_result("SELECT memberid, title FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Request ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( !$reason ) 
        {
            eval("\$delete_request_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_request_form") . "\";");
            ajax_message($delete_request_form, NULL, false, get_phrase("button_delete") . ": " . substr(strip_tags($Request["title"]), 0, 85));
        }

        check_flood("delete-request");
        $TSUE["TSUE_Database"]->delete("tsue_requests", "rid = " . $TSUE["TSUE_Database"]->escape($rid));
        deleteCache("TSUEPlugin_recentRequests_");
        $subject = get_phrase("your_request_has_been_deleted");
        $reply = nl2br(get_phrase("your_request_was_deleted_x_y_z", strip_tags($Request["title"]), getMembername($TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Member"]->info["groupstyle"]), strip_tags($reason)));
        sendPM($subject, $TSUE["TSUE_Member"]->info["memberid"], $Request["memberid"], $reply);
        $Phrase = get_phrase("request_has_been_deleted", substr(strip_tags($Request["title"]), 0, 85), $TSUE["TSUE_Member"]->info["membername"]);
        logAction($Phrase);
        ajax_message($Phrase);
    }

    if( $TSUE["do"] == "edit" || $TSUE["do"] == "saveEdit" ) 
    {
        globalize("post", array( "rid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("canedit_request") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Request = $TSUE["TSUE_Database"]->query_result("SELECT title, description, cid FROM tsue_requests WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Request ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $TSUE["do"] == "saveEdit" ) 
        {
            globalize("post", array( "title" => "TRIM", "description" => "TRIM", "category" => "INT" ));
            if( !$title || !$description || !$category ) 
            {
                jsonError(get_phrase("message_required_fields_error"));
            }

            $requestLength = strlen($title);
            $request_torrent_chars_limit = getSetting("global_settings", "request_torrent_chars_limit", 255);
            if( $request_torrent_chars_limit < $requestLength ) 
            {
                jsonError(get_phrase("post_request_char_limit_error", friendly_number_format($request_torrent_chars_limit), friendly_number_format($requestLength)));
            }

            $requestLength = strlen($description);
            if( $request_torrent_chars_limit < $requestLength ) 
            {
                jsonError(get_phrase("post_request_char_limit_error", friendly_number_format($request_torrent_chars_limit), friendly_number_format($requestLength)));
            }

            $checkCategory = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE cname FROM tsue_torrents_categories WHERE cid=" . $TSUE["TSUE_Database"]->escape($category));
            if( !$checkCategory ) 
            {
                ajax_message(get_phrase("torrent_upload_invalid_cid"), "-ERROR-");
            }

            $buildQuery = array( "title" => $title, "description" => $description, "cid" => $category );
            if( !$TSUE["TSUE_Database"]->update("tsue_requests", $buildQuery, "rid = " . $TSUE["TSUE_Database"]->escape($rid)) ) 
            {
                jsonError(get_phrase("database_error"));
            }

            deleteCache("TSUEPlugin_recentRequests_");
            $Phrase = get_phrase("request_has_been_updated", substr(strip_tags($title), 0, 85), $TSUE["TSUE_Member"]->info["membername"]);
            logAction($Phrase);
            $Output = array( "title" => strip_tags($title), "description" => html_clean($description), "cid" => $category, "cname" => $checkCategory["cname"] );
            jsonHeaders($Output);
            return 1;
        }

        $title = strip_tags($Request["title"]);
        $description = html_clean($Request["description"]);
        require_once(REALPATH . "/library/functions/functions_getTorrents.php");
        $prepareTorrentCategoriesSelectbox = prepareTorrentCategoriesSelectbox(array( $Request["cid"] ));
        eval("\$edit_a_request_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_a_request_form") . "\";");
        ajax_message($edit_a_request_form, "", false, get_phrase("button_edit") . ": " . substr($title, 0, 30));
    }

}



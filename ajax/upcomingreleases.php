<?php 
define("SCRIPTNAME", "upcomingrelease.php");
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

if( $TSUE["action"] == "upcomingrelease" ) 
{
    if( $TSUE["do"] == "new" || $TSUE["do"] == "saveNew" ) 
    {
        if( is_member_of("unregistered") || !has_permission("canupload_torrents") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "saveNew" ) 
        {
            globalize("post", array( "title" => "TRIM", "description" => "TRIM" ));
            if( !$title || !$description ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $releaseLength = strlen($description);
            $upcoming_releases_chars_limit = getSetting("global_settings", "upcoming_releases_chars_limit", 255);
            if( $upcoming_releases_chars_limit < $releaseLength ) 
            {
                ajax_message(get_phrase("upcoming_release_char_limit_error", friendly_number_format($upcoming_releases_chars_limit), friendly_number_format($releaseLength)), "-ERROR-");
            }

            $releaseLength = strlen($title);
            if( $upcoming_releases_chars_limit < $releaseLength ) 
            {
                ajax_message(get_phrase("upcoming_release_char_limit_error", friendly_number_format($upcoming_releases_chars_limit), friendly_number_format($releaseLength)), "-ERROR-");
            }

            $buildQuery = array( "tid" => 0, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "title" => $title, "description" => $description );
            if( !$TSUE["TSUE_Database"]->insert("tsue_upcoming_releases", $buildQuery) ) 
            {
                ajax_message(get_phrase("database_error"), "-ERROR-");
            }

            $rid = $TSUE["TSUE_Database"]->insert_id();
            require(REALPATH . "library/functions/functions_getupComingReleases.php");
            $upcomingRelease = array( "rid" => $rid, "tid" => 0, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "title" => $title, "description" => $description, "gender" => $TSUE["TSUE_Member"]->info["gender"], "membername" => $TSUE["TSUE_Member"]->info["membername"], "groupstyle" => $TSUE["TSUE_Member"]->info["groupstyle"] );
            ajax_message(prepareUpComingRelease($upcomingRelease, 503));
        }

        eval("\$add_a_release_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("add_a_release_form") . "\";");
        ajax_message($add_a_release_form, "", false, get_phrase("upcoming_releases"));
    }

    if( $TSUE["do"] == "delete" ) 
    {
        globalize("post", array( "rid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $Release = $TSUE["TSUE_Database"]->query_result("SELECT memberid, title FROM tsue_upcoming_releases WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Release ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Release["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("candelete_upcomingreleases") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_upcoming_releases", "rid = " . $TSUE["TSUE_Database"]->escape($rid));
        $Phrase = get_phrase("release_x_has_been_deleted", substr(strip_tags($Release["title"]), 0, 85), $TSUE["TSUE_Member"]->info["membername"]);
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

        $Release = $TSUE["TSUE_Database"]->query_result("SELECT memberid, title, description FROM tsue_upcoming_releases WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Release ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Release["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("canedit_upcomingreleases") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "saveEdit" ) 
        {
            globalize("post", array( "title" => "TRIM", "description" => "TRIM" ));
            if( !$title || !$description ) 
            {
                jsonError(get_phrase("message_required_fields_error"));
            }

            $releaseLength = strlen($title);
            $upcoming_releases_chars_limit = getSetting("global_settings", "upcoming_releases_chars_limit", 255);
            if( $upcoming_releases_chars_limit < $releaseLength ) 
            {
                jsonError(get_phrase("upcoming_release_char_limit_error", friendly_number_format($upcoming_releases_chars_limit), friendly_number_format($releaseLength)));
            }

            $releaseLength = strlen($description);
            if( $upcoming_releases_chars_limit < $releaseLength ) 
            {
                jsonError(get_phrase("upcoming_release_char_limit_error", friendly_number_format($upcoming_releases_chars_limit), friendly_number_format($releaseLength)));
            }

            $buildQuery = array( "title" => $title, "description" => $description );
            if( !$TSUE["TSUE_Database"]->update("tsue_upcoming_releases", $buildQuery, "rid = " . $TSUE["TSUE_Database"]->escape($rid)) ) 
            {
                jsonError(get_phrase("database_error"));
            }

            $Phrase = get_phrase("release_has_been_updated", substr(strip_tags($title), 0, 85), $TSUE["TSUE_Member"]->info["membername"]);
            logAction($Phrase);
            $Output = array( "title" => strip_tags($title), "description" => nl2br(html_clean($description)) );
            jsonHeaders($Output);
        }
        else
        {
            $title = strip_tags($Release["title"]);
            $description = html_clean($Release["description"]);
            eval("\$edit_a_release_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_a_release_form") . "\";");
            ajax_message($edit_a_release_form, "", false, get_phrase("button_edit") . ": " . substr($title, 0, 30));
        }

    }

    if( $TSUE["do"] == "fill" || $TSUE["do"] == "saveFill" ) 
    {
        globalize("post", array( "rid" => "INT", "tid" => "INT" ));
        if( !$rid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $Release = $TSUE["TSUE_Database"]->query_result("SELECT tid, memberid, title FROM tsue_upcoming_releases WHERE rid = " . $TSUE["TSUE_Database"]->escape($rid));
        if( !$Release ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Release["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] && !has_permission("canfill_upcomingreleases") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "saveFill" ) 
        {
            if( !$tid ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }

            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT name FROM tsue_torrents WHERE tid = " . $TSUE["TSUE_Database"]->escape($tid));
            if( !$Torrent ) 
            {
                ajax_message(get_phrase("torrents_not_found"), "-ERROR-");
            }

            $TSUE["TSUE_Database"]->update("tsue_upcoming_releases", array( "tid" => $tid ), "rid = " . $TSUE["TSUE_Database"]->escape($rid));
            ajax_message(get_phrase("message_saved"), "-DONE-");
        }

        eval("\$release_completed_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("release_completed_form") . "\";");
        ajax_message($release_completed_form, "", false, get_phrase("release_completed") . ": " . substr(strip_tags($Release["title"]), 0, 85) . " ...");
    }

}



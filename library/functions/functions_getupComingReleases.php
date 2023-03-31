<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function prepareUpComingRelease($Release, $pageID)
{
    global $TSUE;
    $added = convert_relative_time($Release["added"]);
    $_avatar = get_member_avatar($Release["memberid"], $Release["gender"], "s");
    $_memberid = $Release["memberid"];
    $_membername = getMembername($Release["membername"], $Release["groupstyle"]);
    $_alt = "";
    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    $statusClass = ($Release["tid"] ? "filled" : "pending");
    $statusPhrase = get_phrase("upcoming_release_" . (($Release["tid"] ? "done" : "pending")));
    $viewTorrent = ($Release["tid"] ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Release["tid"] . "\">[" . get_phrase("view_torrent") . "]</a>" : "");
    $fillThisRelease = ($Release["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !$Release["tid"] || has_permission("canfill_upcomingreleases") ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&amp;pid=" . $pageID . "&amp;do=fill&amp;rid=" . $Release["rid"] . "\" id=\"fillRelease\" rel=\"fillRelease_" . $Release["rid"] . "\">[" . get_phrase("release_completed") . "]</a>" : "");
    $deleteThisRelease = ($Release["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] || has_permission("candelete_upcomingreleases") ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&amp;pid=" . $pageID . "&amp;do=delete&amp;rid=" . $Release["rid"] . "\" id=\"deleteRelease\" rel=\"deleteRelease_" . $Release["rid"] . "\">[" . get_phrase("button_delete") . "]</a>" : "");
    $editThisRelease = ($Release["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] || has_permission("canedit_upcomingreleases") ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upcomingreleases&amp;pid=" . $pageID . "&amp;do=edit&amp;rid=" . $Release["rid"] . "\" id=\"editRelease\" rel=\"editRelease_" . $Release["rid"] . "\">[" . get_phrase("button_edit") . "]</a>" : "");
    $Release["title"] = strip_tags($Release["title"]);
    $Release["description"] = nl2br(html_clean($Release["description"]));
    if( 210 < strlen($Release["title"]) ) 
    {
        $Release["title"] = substr($Release["title"], 0, 210) . "...";
    }

    eval("\$HTML = \"" . $TSUE["TSUE_Template"]->LoadTemplate("prepareUpcomingRelease") . "\";");
    return $HTML;
}



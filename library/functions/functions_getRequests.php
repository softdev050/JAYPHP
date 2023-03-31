<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function prepareRequest($Request)
{
    global $TSUE;
    $Voters = ($Request["voters"] ? unserialize($Request["voters"]) : array(  ));
    $added = convert_relative_time($Request["added"]);
    $_avatar = get_member_avatar($Request["memberid"], $Request["gender"], "s");
    $_memberid = $Request["memberid"];
    $_membername = getMembername($Request["membername"], $Request["groupstyle"]);
    $_alt = "";
    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
    $statusClass = ($Request["tid"] ? "filled" : "pending");
    if( $Request["tid"] && $Request["filled_by"] ) 
    {
        $_avatar = get_member_avatar($Request["filled_by"], $Request["fgender"], "s");
        $_memberid = $Request["filled_by"];
        $_membername = getMembername($Request["fmembername"], $Request["fgroupstyle"]);
        $_alt = "";
        eval("\$filled_by = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        eval("\$filled_by_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
        $statusPhrase = get_phrase("filled_by_x", $filled_by);
    }
    else
    {
        $statusPhrase = get_phrase("request_" . (($Request["tid"] ? "filled" : "pending")));
    }

    $voteButton = (in_array($TSUE["TSUE_Member"]->info["memberid"], $Voters) || $Request["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] ? get_phrase("thank_you") : get_phrase("request_vote_button"));
    $viewTorrent = ($Request["tid"] ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $Request["tid"] . "\">[" . get_phrase("view_torrent") . "]</a>" : "");
    $fillThisRequest = (has_permission("canfill_request") && !$Request["tid"] ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=101&amp;do=fill&amp;rid=" . $Request["rid"] . "\" id=\"fillRequest\" rel=\"fillRequest_" . $Request["rid"] . "\">[" . get_phrase("fill_this_request") . "]</a>" : "");
    $resetThisRequest = (has_permission("canreset_request") && $Request["tid"] ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=101&amp;do=reset&amp;rid=" . $Request["rid"] . "\" id=\"resetRequest\" rel=\"resetRequest_" . $Request["rid"] . "\">[" . get_phrase("button_reset") . "]</a>" : "");
    $deleteThisRequest = (has_permission("candelete_request") ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=101&amp;do=delete&amp;rid=" . $Request["rid"] . "\" id=\"deleteRequest\" rel=\"deleteRequest_" . $Request["rid"] . "\">[" . get_phrase("button_delete") . "]</a>" : "");
    $editThisRequest = (has_permission("canedit_request") ? "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=request&amp;pid=101&amp;do=edit&amp;rid=" . $Request["rid"] . "\" id=\"editRequest\" rel=\"editRequest_" . $Request["rid"] . "\">[" . get_phrase("button_edit") . "]</a>" : "");
    $Request["title"] = strip_tags($Request["title"]);
    $Request["description"] = html_clean($Request["description"]);
    if( 210 < strlen($Request["title"]) ) 
    {
        $Request["title"] = substr($Request["title"], 0, 210) . "...";
    }

    $Voters = friendly_number_format(count($Voters));
    $categoryImage = "";
    if( $Request["cid"] ) 
    {
        $Image = array( "src" => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/category_images/" . $Request["cid"] . ".png", "alt" => $Request["cname"], "title" => $Request["cname"], "class" => "middle", "id" => "", "rel" => "resized_by_tsue" );
        $categoryImage = getImage($Image);
    }

    eval("\$HTML = \"" . $TSUE["TSUE_Template"]->LoadTemplate("prepareRequest") . "\";");
    return $HTML;
}



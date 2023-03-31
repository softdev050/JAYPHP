<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function getLMDetails($Report)
{
    global $TSUE;
    if( $Report["last_modified_date"] && $Report["last_modified_memberid"] ) 
    {
        $_memberid = $Report["last_modified_memberid"];
        $_membername = getMembername($Report["last_modified_membername"], $Report["last_modified_groupstyle"]);
        eval("\$last_modified = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $last_modified_date = convert_relative_time($Report["last_modified_date"]);
    }
    else
    {
        $_memberid = $Report["reported_by_memberid"];
        $_membername = getMembername($Report["reported_by_membername"], $Report["reported_by_groupstyle"]);
        eval("\$last_modified = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $last_modified_date = convert_relative_time($Report["first_report_date"]);
    }

    return array( $last_modified, $last_modified_date );
}

function getReports($Report, $Count)
{
    global $TSUE;
    $_memberid = $Report["reported_by_memberid"];
    $_membername = getMembername($Report["reported_by_membername"], $Report["reported_by_groupstyle"]);
    eval("\$Report['reported_by'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    list($Report["last_modified"], $Report["last_modified_date"]) = getlmdetails($Report);
    $Report["first_report_date"] = convert_relative_time($Report["first_report_date"]);
    $Report["content_type"] = buildReportName($Report);
    $Report["report_state"] = get_phrase("report_state_" . $Report["report_state"]);
    $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
    eval("\$listReports = \"" . $TSUE["TSUE_Template"]->LoadTemplate("reports_list_reports_rows") . "\";");
    return $listReports;
}

function buildReportContentURL($Report)
{
    global $TSUE;
    $gotoLink = "";
    switch( $Report["content_type"] ) 
    {
        case "torrent":
            $gotoLink = "?p=torrents&pid=10&action=details&tid=" . $Report["content_id"];
            break;
        case "torrent_comments":
            $searchContentID = $TSUE["TSUE_Database"]->query_result("SELECT content_id FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($Report["content_id"]));
            if( $searchContentID ) 
            {
                $gotoLink = "?p=torrents&pid=10&action=details&tid=" . $searchContentID["content_id"] . "&comment_id=" . $Report["content_id"] . "#Commentsbox_" . $Report["content_id"];
            }

            break;
        case "profile_comments":
            $searchContentID = $TSUE["TSUE_Database"]->query_result("SELECT content_id FROM tsue_comments WHERE comment_id = " . $TSUE["TSUE_Database"]->escape($Report["content_id"]));
            if( $searchContentID ) 
            {
                $gotoLink = "?p=profile&pid=18&memberid=" . $searchContentID["content_id"] . "&comment_id=" . $Report["content_id"] . "#Commentsbox_" . $Report["content_id"];
            }

            break;
        case "forum_post":
            $searchContentID = $TSUE["TSUE_Database"]->query_result("SELECT p.threadid, t.forumid FROM tsue_forums_posts p LEFT JOIN tsue_forums_threads t USING(threadid) WHERE p.postid = " . $TSUE["TSUE_Database"]->escape($Report["content_id"]));
            if( $searchContentID ) 
            {
                $gotoLink = "?p=forums&pid=11&fid=" . $searchContentID["forumid"] . "&tid=" . $searchContentID["threadid"] . "&postid=" . $Report["content_id"] . "#show_post_" . $Report["content_id"];
            }

            break;
        case "ig_foto":
            $gotoLink = "?p=gallery&pid=400&action=viewFile&attachment_id=" . $Report["content_id"];
            break;
        case "peer":
            $Member = $TSUE["TSUE_Database"]->query_result("SELECT membername FROM tsue_members WHERE memberid = " . $TSUE["TSUE_Database"]->escape($Report["content_id"]));
            if( $Member ) 
            {
                $gotoLink = "admincp/?action=Member Manager&do=Peers&searchType=membername&keywords=" . urlencode($Member["membername"]);
            }

            break;
        case "message":
            $Message = $TSUE["TSUE_Database"]->query_result("SELECT message_id FROM tsue_messages_replies WHERE reply_id = " . $TSUE["TSUE_Database"]->escape($Report["content_id"]));
            if( $Message ) 
            {
                $gotoLink = "admincp/?action=Dashboard&do=Read PM&message_id=" . $Message["message_id"];
            }

    }
    return $gotoLink;
}

function buildReportName($Report)
{
    $content_type = "";
    switch( $Report["content_type"] ) 
    {
        case "message":
            $content_type = get_phrase("messages_report");
            break;
        case "profile_comments":
            $content_type = get_phrase("report_profile_post");
            break;
        case "torrent":
            $content_type = get_phrase("torrents_report");
            break;
        case "torrent_comments":
            $content_type = get_phrase("torrents_report_comment");
            break;
        case "peer":
            $content_type = get_phrase("torrents_peer_report");
            break;
        case "forum_post":
            $content_type = get_phrase("forums_report_post");
            break;
        case "file_comments":
            $content_type = get_phrase("report_file");
            break;
        case "ig_foto":
            $content_type = get_phrase("report_image");
    }
    return $content_type;
}



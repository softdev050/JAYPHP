<?php 
define("SCRIPTNAME", "poll.php");
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
    case "edit_poll":
        globalize("post", array( "pid" => "INT", "threadid" => "INT", "do" => "TRIM", "poll_question" => "TRIM", "pollOptions" => "TRIM", "closeDaysAfter" => "INT", "multiple" => "INT" ));
        if( !$pid || !$threadid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $Poll = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_poll WHERE pid = " . $TSUE["TSUE_Database"]->escape($pid) . " AND active = 1 AND createdinThread = 1");
        if( !$Poll ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, memberid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canEditPoll = $Poll["createdinThread"] && (has_forum_permission("canedit_polls", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_polls", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $thread["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered"));
        if( !$canEditPoll ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["do"] == "save" ) 
        {
            if( !$poll_question || !$pollOptions ) 
            {
                ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
            }
            else
            {
                $pollOptions = tsue_explode(",", $pollOptions);
                $Options = array(  );
                foreach( $pollOptions as $Option ) 
                {
                    $Option = trim($Option);
                    if( $Option ) 
                    {
                        $Options[] = $Option;
                    }

                }
                if( !$Options ) 
                {
                    ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
                }
                else
                {
                    $buildQuery = array( "question" => $poll_question, "options" => implode("~", $Options), "multiple" => ($multiple == "1" ? 1 : 0), "closeDaysAfter" => $closeDaysAfter );
                    $TSUE["TSUE_Database"]->update("tsue_poll", $buildQuery, "pid=" . $TSUE["TSUE_Database"]->escape($pid));
                    $uploadPhrase = get_phrase("poll_x_updated_by_y", strip_tags($poll_question), $TSUE["TSUE_Member"]->info["membername"]);
                    logAction($uploadPhrase);
                    ajax_message($uploadPhrase, "", false);
                }

            }

        }

        $poll_question = strip_tags($Poll["question"]);
        $closeDaysAfter = 0 + $Poll["closeDaysAfter"];
        $multipleChecked = ($Poll["multiple"] ? " checked=\"checked\"" : "");
        $pollOptions = tsue_explode("~", $Poll["options"]);
        $_pollOptionList = "";
        foreach( $pollOptions as $pollOption ) 
        {
            $_pollOptionList .= "<div><input type=\"text\" name=\"pollOptions[]\" value=\"" . strip_tags($pollOption) . "\" class=\"s\" /></div>";
        }
        eval("\$forums_edit_poll_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_edit_poll_form") . "\";");
        ajax_message($forums_edit_poll_form, "", false, get_phrase("button_edit_poll"));
        break;
    case "delete_poll":
        globalize("post", array( "pid" => "INT", "threadid" => "INT" ));
        if( !$pid || !$threadid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $Poll = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_poll WHERE pid = " . $TSUE["TSUE_Database"]->escape($pid) . " AND active = 1 AND createdinThread = 1");
        if( !$Poll ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid, memberid, locked FROM tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( !$thread ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        require(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums();
        if( !$TSUE_Forums->forumCategories || !isset($TSUE_Forums->availableForums[$thread["forumid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $forum = $TSUE_Forums->availableForums[$thread["forumid"]];
        if( $thread["locked"] && !has_forum_permission("canmanage_locked_threads", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("forums_thread_locked"), "-ERROR-");
        }

        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( !$TSUE_Forums->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $canDeletePoll = $Poll["createdinThread"] && (has_forum_permission("candelete_polls", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_polls", $TSUE_Forums->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $thread["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered"));
        if( !$canDeletePoll ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $TSUE["TSUE_Database"]->delete("tsue_poll", "pid=" . $TSUE["TSUE_Database"]->escape($pid));
        $TSUE["TSUE_Database"]->update("tsue_poll", array( "threadid" => 0 ), "threadid=" . $TSUE["TSUE_Database"]->escape($threadid));
        $uploadPhrase = get_phrase("poll_x_deleted_by_y", strip_tags($Poll["question"]), $TSUE["TSUE_Member"]->info["membername"]);
        logAction($uploadPhrase);
        ajax_message($uploadPhrase, "", false);
        break;
    case "list_voters":
        globalize("post", array( "pid" => "INT" ));
        if( !$pid ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        if( !has_permission("canview_poll_voters") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $Poll = $TSUE["TSUE_Database"]->query_result("SELECT pid, question, options, votes, voters FROM tsue_poll WHERE pid = " . $TSUE["TSUE_Database"]->escape($pid) . " AND active = 1");
        if( !$Poll ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Poll["voters"] ) 
        {
            $Poll["voters"] = tsue_explode("~", $Poll["voters"]);
        }

        if( !$Poll["voters"] ) 
        {
            ajax_message(get_phrase("message_nothing_found"), "-ERROR-");
        }

        $Voters = $TSUE["TSUE_Database"]->query("SELECT m.memberid, m.membername, m.gender, g.groupname, g.groupstyle, b.memberid as isBanned, mp.custom_title FROM tsue_members m LEFT JOIN tsue_membergroups g USING(membergroupid) LEFT JOIN tsue_member_bans b ON(m.memberid=b.memberid) LEFT JOIN tsue_member_profile mp on(m.memberid=mp.memberid) WHERE m.memberid IN (" . implode(",", array_map("intval", $Poll["voters"])) . ")");
        if( !$TSUE["TSUE_Database"]->num_rows($Voters) ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $Output = "";
        for( $count = 0; $List = $TSUE["TSUE_Database"]->fetch_assoc($Voters); $count++ ) 
        {
            $groupname = getGroupname($List);
            $_avatar = get_member_avatar($List["memberid"], $List["gender"], "s");
            $_memberid = $List["memberid"];
            $_membername = getMembername($List["membername"], $List["groupstyle"]);
            $like_date = "";
            $_alt = "";
            eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
            eval("\$ShowMemberName = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
            eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_full_people_list") . "\";");
        }
        ajax_message($Output, "", false, get_phrase("list_voters") . " (" . friendly_number_format($count) . ") " . strip_tags($Poll["question"]));
        break;
    case "vote":
        globalize("post", array( "pid" => "INT", "option" => "" ));
        if( !$pid || !$option ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $Poll = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE * FROM tsue_poll WHERE pid = " . $TSUE["TSUE_Database"]->escape($pid) . " AND active = 1");
        if( !$Poll ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        if( $Poll["voters"] ) 
        {
            $PollVoters = tsue_explode("~", $Poll["voters"]);
        }
        else
        {
            $PollVoters = array(  );
        }

        require_once(REALPATH . "library/functions/functions_getPoll.php");
        if( isPollClosed($Poll) ) 
        {
            ajax_message(get_phrase("this_poll_is_closed"), "-ERROR-");
        }

        if( !has_permission("canvote_polls") || $PollVoters && !is_member_of("unregistered") && in_array($TSUE["TSUE_Member"]->info["memberid"], $PollVoters) ) 
        {
            ajax_message(get_phrase("poll_already_voted"), "-ERROR-");
        }

        if( !is_member_of("unregistered") ) 
        {
            $PollVoters[] = $TSUE["TSUE_Member"]->info["memberid"];
        }

        $PollVoters = implode("~", $PollVoters);
        $PollOptions = tsue_explode("~", $Poll["options"]);
        $PollVotes = ($Poll["votes"] ? tsue_explode("~", $Poll["votes"]) : array(  ));
        $optionsCount = count($PollOptions);
        for( $i = 0; $i < $optionsCount; $i++ ) 
        {
            if( !isset($PollVotes[$i]) ) 
            {
                $PollVotes[$i] = 0;
            }

        }
        for( $i = 0; $i < $optionsCount; $i++ ) 
        {
            if( $Poll["multiple"] && is_array($option) && in_array($PollOptions[$i], $option) || !$Poll["multiple"] && !is_array($option) && $option == $PollOptions[$i] ) 
            {
                $PollVotes[$i] += 1;
            }

        }
        $TSUE["TSUE_Database"]->update("tsue_poll", array( "votes" => implode("~", $PollVotes), "voters" => $PollVoters ), "pid = " . $TSUE["TSUE_Database"]->escape($pid));
        updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_poll"], $TSUE["TSUE_Member"]->info["memberid"]);
        $Poll["question"] = strip_tags($Poll["question"]);
        $POLL = showResults($Poll, $PollVotes, $PollOptions);
        ajax_message($POLL);
}


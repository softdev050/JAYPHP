<?php 
function TSUEPlugin_poll($pluginPosition = "", $pluginOptions = array(  ), $activePoll = "", $showDiscussPoll = true, $canEditPoll = false, $canDeletePoll = false, $threadid = 0)
{
    global $TSUE;
    if( !$activePoll ) 
    {
        $activePoll = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE * FROM tsue_poll WHERE active = 1 AND createdinThread = 0 ORDER BY `date` DESC LIMIT 1");
    }

    if( !$activePoll ) 
    {
        return NULL;
    }

    $activePoll["question"] = strip_tags($activePoll["question"]);
    $TSUE["TSUE_Template"]->loadJavascripts("poll");
    if( has_permission("canview_poll_voters") ) 
    {
        eval("\$poll_list_voters_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_list_voters_link") . "\";");
    }
    else
    {
        $poll_list_voters_link = "";
    }

    if( $canEditPoll ) 
    {
        eval("\$edit_poll_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("edit_poll_link") . "\";");
    }
    else
    {
        $edit_poll_link = "";
    }

    if( $canDeletePoll ) 
    {
        eval("\$delete_poll_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_poll_link") . "\";");
    }
    else
    {
        $delete_poll_link = "";
    }

    $POLL = "";
    $PollOptions = tsue_explode("~", $activePoll["options"]);
    $PollVotes = tsue_explode("~", $activePoll["votes"]);
    if( $activePoll["voters"] ) 
    {
        $PollVoters = tsue_explode("~", $activePoll["voters"]);
    }
    else
    {
        $PollVoters = array(  );
    }

    require_once(REALPATH . "library/functions/functions_getPoll.php");
    $pollClosed = "";
    if( isPollClosed($activePoll) ) 
    {
        $pollClosed = get_phrase("this_poll_is_closed");
    }

    if( $pollClosed || !has_permission("canvote_polls") || $PollVoters && !is_member_of("unregistered") && in_array($TSUE["TSUE_Member"]->info["memberid"], $PollVoters) ) 
    {
        $POLL = showResults($activePoll, $PollVotes, $PollOptions, $showDiscussPoll);
    }
    else
    {
        $Options = "";
        $polloptionCount = 0;
        foreach( $PollOptions as $Option ) 
        {
            $Option = strip_tags($Option);
            eval("\$Options .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_options" . (($activePoll["multiple"] ? "_multiple" : ""))) . "\";");
            $polloptionCount++;
        }
        eval("\$POLL = \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_form") . "\";");
    }

    $isToggled = isToggled("recentPoll");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    eval("\$TSUEPlugin_poll = \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_main") . "\";");
    return $TSUEPlugin_poll;
}



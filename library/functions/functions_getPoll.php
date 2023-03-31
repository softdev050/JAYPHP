<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function showResults($activePoll, $PollVotes, $PollOptions, $showDiscussPoll = true)
{
    global $TSUE;
    $pollStyle = array(  );
    $TotalVotes = array_sum($PollVotes);
    $Options = "";
    $Counter = 0;
    $pollClosed = "";
    $discussPoll = "";
    $activePoll["question"] = strip_tags($activePoll["question"]);
    if( isPollClosed($activePoll) ) 
    {
        $pollClosed = get_phrase("this_poll_is_closed");
    }
    else
    {
        if( $activePoll["closeDaysAfter"] ) 
        {
            $closeDate = $activePoll["date"] + $activePoll["closeDaysAfter"] * 86400;
            $pollClosed = get_phrase("poll_will_close", convert_time($closeDate, "d-m-Y"), convert_time($closeDate, "h:i"));
        }

    }

    if( $activePoll["threadid"] && !$activePoll["createdinThread"] && $showDiscussPoll ) 
    {
        $discussPoll = get_phrase("click_here_to_discuss_poll", $activePoll["threadid"]);
    }

    foreach( $PollOptions as $Option ) 
    {
        $Option = strip_tags($Option);
        if( !isset($PollVotes[$Counter]) || $PollVotes[$Counter] <= 0 || $TotalVotes <= 0 ) 
        {
            $pollStyle["procent"] = 0;
        }
        else
        {
            $pollStyle["procent"] = friendly_number_format(($PollVotes[$Counter] < $TotalVotes ? $PollVotes[$Counter] / $TotalVotes * 100 : 100), 1);
        }

        $pollStyle["graphicnumber"] = $Counter % 6 + 1;
        $pollStyle["barnumber"] = round($pollStyle["procent"]);
        if( 100 < $pollStyle["barnumber"] ) 
        {
            $pollStyle["barnumber"] = 100;
        }

        $title = (!isset($PollVotes[$Counter]) ? "" : get_phrase("poll_voters", friendly_number_format($PollVotes[$Counter])));
        eval("\$Options .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_results") . "\";");
        $Counter++;
    }
    eval("\$POLL = \"" . $TSUE["TSUE_Template"]->LoadTemplate("poll_results_main") . "\";");
    return $POLL;
}

function isPollClosed($activePoll)
{
    return ($activePoll["closed"] || $activePoll["closeDaysAfter"] && $activePoll["date"] + $activePoll["closeDaysAfter"] * 86400 <= TIMENOW ? true : false);
}



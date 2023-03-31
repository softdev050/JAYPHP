<?php 
function TSUEPlugin_forumStats($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    global $TSUE_Forums;
    $isToggled = isToggled("forumStats");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !isset($TSUE_Forums->availableForums) || !$TSUE_Forums->availableForums ) 
    {
        require_once(REALPATH . "/library/classes/class_forums.php");
        $TSUE_Forums = new forums(true);
    }

    if( isset($TSUE_Forums->availableForums) && $TSUE_Forums->availableForums && count($TSUE_Forums->availableForums) ) 
    {
        $threads = $replies = 0;
        foreach( $TSUE_Forums->availableForums as $forumid => $forum ) 
        {
            $threads += $forum["threadcount"];
            $replies += $forum["replycount"];
        }
        $threads = friendly_number_format($threads);
        $replies = friendly_number_format($replies);
        eval("\$TSUEPlugin_forumStats = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_stats") . "\";");
        return $TSUEPlugin_forumStats;
    }

}



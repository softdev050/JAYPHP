<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "goto.php");
require("./library/init/init.php");
switch( $TSUE["do"] ) 
{
    case "forumPost":
        globalize("get", array( "postID" => "INT" ));
        if( $postID ) 
        {
            $Post = $TSUE["TSUE_Database"]->query_result("SELECT p.postid, t.threadid, f.forumid FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) INNER JOIN tsue_forums f USING(forumid) WHERE p.postid=" . $TSUE["TSUE_Database"]->escape($postID));
            if( $Post ) 
            {
                redirect("?p=forums&pid=11&fid=" . $Post["forumid"] . "&tid=" . $Post["threadid"] . "&postid=" . $Post["postid"]);
            }

        }

        break;
    case "profilePost":
}
show_error(get_phrase("message_content_error"));


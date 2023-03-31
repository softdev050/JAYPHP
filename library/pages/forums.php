<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "forums.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "forums", "fileuploader" ));
$TSUE["TSUE_Template"]->loadJSPhrase(array( "button_delete", "button_move", "confirm_mass_delete_threads", "button_lock", "button_unlock", "forums_icon_sticky", "button_unsticky", "forums_icon_locked", "forums_icon_sticky" ));
globalize("GET", array( "fid" => "INT", "tid" => "INT", "attachment_id" => "INT", "searchid" => "INT" ));
require("./library/classes/class_forums.php");
$TSUE_Forums = new forums();
$Page_Title = get_phrase("forums_page_title", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("forums_forums") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID ));
if( $TSUE["action"] == "new_thread" && $fid ) 
{
    $Output = $TSUE_Forums->preparePostNewThread($fid);
    $Page_Title = get_phrase("forums_post_new_thread");
}
else
{
    if( $TSUE["action"] == "search_forums" ) 
    {
        $Output = $TSUE_Forums->prepareSearch($searchid);
        $Page_Title = get_phrase("navigation_forums_search_forums");
    }
    else
    {
        if( $TSUE["action"] == "new_threads" ) 
        {
            $Output = $TSUE_Forums->prepareNewThreads($searchid);
            $Page_Title = get_phrase("navigation_forums_whats_new");
        }
        else
        {
            if( $TSUE["action"] == "todays_posts" ) 
            {
                $Output = $TSUE_Forums->prepareNewThreads($searchid, true);
                $Page_Title = get_phrase("navigation_forums_todays_posts");
            }
            else
            {
                if( $TSUE["action"] == "rss" && $fid ) 
                {
                    $Output = $TSUE_Forums->prepareRSS($fid);
                }
                else
                {
                    if( $TSUE["action"] == "download" && $attachment_id ) 
                    {
                        $Output = $TSUE_Forums->prepareAttachmentDownload($attachment_id);
                    }
                    else
                    {
                        if( $fid && $tid ) 
                        {
                            if( isset($_GET["postid"]) && ($_postid = intval($_GET["postid"])) ) 
                            {
                                $pageNumber = findPageNumber("SELECT 1 FROM tsue_forums_posts WHERE postid <= " . $TSUE["TSUE_Database"]->escape($_postid) . " AND threadid = " . $TSUE["TSUE_Database"]->escape($tid), getSetting("global_settings", "forums_posts_perpage"));
                                redirect("?p=forums&pid=11&fid=" . $fid . "&tid=" . $tid . "&page=" . $pageNumber . "#show_post_" . $_postid);
                            }
                            else
                            {
                                if( isset($_GET["goLast"]) && $_GET["goLast"] == "true" ) 
                                {
                                    $findPost = $TSUE["TSUE_Database"]->query_result("SELECT postid FROM tsue_forums_posts WHERE threadid=" . $TSUE["TSUE_Database"]->escape($tid) . " ORDER BY post_date DESC LIMIT 1");
                                    if( $findPost ) 
                                    {
                                        $pageNumber = findPageNumber("SELECT 1 FROM tsue_forums_posts WHERE postid <= " . $TSUE["TSUE_Database"]->escape($findPost["postid"]) . " AND threadid = " . $TSUE["TSUE_Database"]->escape($tid), getSetting("global_settings", "forums_posts_perpage"));
                                        redirect("?p=forums&pid=11&fid=" . $fid . "&tid=" . $tid . "&page=" . $pageNumber . "#show_post_" . $findPost["postid"]);
                                    }

                                }

                            }

                            $Output = $TSUE_Forums->prepareThreadPosts($fid, $tid);
                        }
                        else
                        {
                            if( $fid ) 
                            {
                                $Output = $TSUE_Forums->prepareThreads($fid);
                            }
                            else
                            {
                                if( $tid ) 
                                {
                                    $Thread = $TSUE["TSUE_Database"]->query_result("SELECT forumid FROM tsue_forums_threads WHERE threadid=" . $TSUE["TSUE_Database"]->escape($tid));
                                    if( $Thread ) 
                                    {
                                        $fid = $Thread["forumid"];
                                        $Output = $TSUE_Forums->prepareThreadPosts($fid, $tid);
                                    }
                                    else
                                    {
                                        show_error(get_phrase("message_content_error"));
                                    }

                                }
                                else
                                {
                                    $Output = $TSUE_Forums->prepareForumList();
                                }

                            }

                        }

                    }

                }

            }

        }

    }

}

PrintOutput($Output, $Page_Title);


<?php 

class forums
{
    public $forumCategories = false;
    public $forums = false;
    public $availableForums = false;
    public $forumPermissions = NULL;
    public $attachmentCache = NULL;

    public function forums($isPlugin = false)
    {
        global $TSUE;
        if( !has_permission("canview_any_forum") && !$isPlugin ) 
        {
            if( !defined("IS_AJAX") ) 
            {
                show_error(get_phrase("permission_denied"));
            }
            else
            {
                show_error(get_phrase("permission_denied"));
            }

        }

        if( !empty($TSUE["TSUE_Settings"]->settings["forums_permissions_cache"]) ) 
        {
            foreach( $TSUE["TSUE_Settings"]->settings["forums_permissions_cache"] as $permission ) 
            {
                $this->forumPermissions[$permission["forumid"]][$permission["membergroupid"]] = unserialize($permission["permissions"]);
            }
        }

        $forums = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE f.*, t.title as last_thread_title \r\n\t\tFROM tsue_forums f \r\n\t\tLEFT JOIN tsue_forums_threads t ON (f.last_post_threadid=t.threadid) \r\n\t\tORDER BY f.displayorder ASC", false);
        if( $forums ) 
        {
            while( $forum = $TSUE["TSUE_Database"]->fetch_assoc($forums) ) 
            {
                if( isset($this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && has_forum_permission("canview_forum", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
                {
                    if( $forum["parentid"] == -1 ) 
                    {
                        $this->forumCategories[] = $forum;
                    }
                    else
                    {
                        $this->forums[$forum["parentid"]][] = $forum;
                    }

                    $this->prepareCache($forum);
                }

            }
        }

    }

    public function prepareForumList()
    {
        global $TSUE;
        $PAGEID = PAGEID;
        if( !$this->forumCategories ) 
        {
            show_error(get_phrase("forums_no_registered_forum_yet"));
        }

        $forumCategories = "";
        foreach( $this->forumCategories as $forum ) 
        {
            $isToggled = isToggled("forumList" . $forum["forumid"]);
            $class = (!$isToggled ? "" : "hidden");
            $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
            $subForums = $this->prepareSubForums($forum["forumid"], $class);
            if( $subForums ) 
            {
                eval("\$forumCategories .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_category_list") . "\";");
            }
            else
            {
                eval("\$forumCategories .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_category_list_no_sub") . "\";");
            }

        }
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_home") . "\";");
        return $Output;
    }

    public function prepareSubForums($forumid, $class)
    {
        global $TSUE;
        $Output = "";
        if( isset($this->forums[$forumid]) ) 
        {
            foreach( $this->forums[$forumid] as $forum ) 
            {
                $subForums = $prepareDeepSubForums = $last_post_info = "";
                if( isset($this->forums[$forum["forumid"]]) ) 
                {
                    $prepareDeepSubForums = $this->prepareDeepSubForums($forum["forumid"]);
                    if( $prepareDeepSubForums ) 
                    {
                        $subForums = $prepareDeepSubForums["Output"];
                        $currentLastPostInfo = unserialize($forum["last_post_info"]);
                        if( $currentLastPostInfo["lastpostdate"] <= $prepareDeepSubForums["lastpostdate"] ) 
                        {
                            $last_post_info = $this->prepareLastPostInfo($prepareDeepSubForums["last_post_info"], $prepareDeepSubForums["last_thread_title"], $prepareDeepSubForums["forumid"], $prepareDeepSubForums["last_post_threadid"]);
                        }

                        unset($prepareDeepSubForums);
                        unset($currentLastPostInfo);
                    }

                }

                if( !$last_post_info ) 
                {
                    $last_post_info = $this->prepareLastPostInfo($forum["last_post_info"], $forum["last_thread_title"], $forum["forumid"], $forum["last_post_threadid"]);
                }

                if( $forum["external_link"] ) 
                {
                    eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_subcategory_list_link") . "\";");
                }
                else
                {
                    $_rel = "";
                    if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
                    {
                        $_rel = "password_required";
                    }

                    eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_subcategory_list") . "\";");
                }

            }
        }

        return $Output;
    }

    public function prepareDeepSubForums($forumid)
    {
        global $TSUE;
        $Output = "";
        if( isset($this->forums[$forumid]) ) 
        {
            $lastpostdate = 0;
            $deepCategories = "";
            $lastPostInfo = $last_thread_title = $last_post_threadid = "";
            foreach( $this->forums[$forumid] as $forum ) 
            {
                $last_post_info = unserialize($forum["last_post_info"]);
                if( $lastpostdate < $last_post_info["lastpostdate"] ) 
                {
                    $lastpostdate = $last_post_info["lastpostdate"];
                    $lastPostInfo = $forum["last_post_info"];
                    $last_thread_title = $forum["last_thread_title"];
                    $forumid = $forum["forumid"];
                    $last_post_threadid = $forum["last_post_threadid"];
                    unset($last_post_info);
                }

                $_rel = "";
                if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
                {
                    $_rel = "password_required";
                }

                eval("\$deepCategories .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_deepcategory_list") . "\";");
            }
            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_deepcategory") . "\";");
            return array( "Output" => $Output, "lastpostdate" => $lastpostdate, "last_post_info" => $lastPostInfo, "last_thread_title" => $last_thread_title, "forumid" => $forumid, "last_post_threadid" => $last_post_threadid );
        }

    }

    public function prepareDeepSubForums2($forumid)
    {
        global $TSUE;
        $Output = "";
        if( isset($this->forums[$forumid]) ) 
        {
            $deepSubForums = "";
            foreach( $this->forums[$forumid] as $forum ) 
            {
                $last_post_info = $this->prepareLastPostInfo($forum["last_post_info"], $forum["last_thread_title"], $forum["forumid"], $forum["last_post_threadid"]);
                $_rel = "";
                if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
                {
                    $_rel = "password_required";
                }

                $subForums = "";
                $class = "";
                eval("\$deepSubForums .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_subcategory_list") . "\";");
            }
            eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_category_list2") . "\";");
        }

        return $Output;
    }

    public function prepareForumListMultipleSelectbox($class = "forums_search_multiple_textarea", $id = "multiple")
    {
        global $TSUE;
        if( !$this->forumCategories ) 
        {
            show_error(get_phrase("forums_no_registered_forum_yet"));
        }

        $Output = "\r\n\t\t<select title=\"" . get_phrase("multiple_select_tip") . "\" name=\"forums[]\" multiple=\"multiple\" id=\"" . $id . "\"" . (($class ? " class=\"" . $class . "\"" : "")) . "> \r\n\t\t\t<option value=\"-1\" selected=\"selected\">" . get_phrase("forums_search_forum_all") . "</option>";
        $forumCategories = "";
        foreach( $this->forumCategories as $forum ) 
        {
            $Output .= "<optgroup label=\"" . $forum["title"] . "\">";
            $Output .= $this->prepareSubForumsSelectbox($forum["forumid"]);
            $Output .= "</optgroup>";
        }
        $Output .= "\r\n\t\t</select>";
        return $Output;
    }

    public function prepareForumListSelectbox($selectname, $selected = 0, $id = "cat_content_small")
    {
        global $TSUE;
        if( !$this->forumCategories ) 
        {
            return "";
        }

        $Output = "\r\n\t\t<select name=\"" . $selectname . "\" id=\"" . $id . "\">";
        $forumCategories = "";
        foreach( $this->forumCategories as $forum ) 
        {
            $Output .= "<optgroup label=\"" . $forum["title"] . "\">";
            $Output .= $this->prepareSubForumsSelectbox($forum["forumid"], $selected);
            $Output .= "</optgroup>";
        }
        $Output .= "\r\n\t\t</select>";
        return $Output;
    }

    public function prepareSubForumsSelectbox($forumid, $selected = 0)
    {
        global $TSUE;
        $Output = "";
        if( isset($this->forums[$forumid]) ) 
        {
            foreach( $this->forums[$forumid] as $forum ) 
            {
                if( !$forum["external_link"] && $this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
                {
                    $Output .= "<option value=\"" . $forum["forumid"] . "\"" . (($selected == $forum["forumid"] ? " selected=\"selected\"" : "")) . ">" . $forum["title"] . "</option>";
                    if( isset($this->forums[$forum["forumid"]]) ) 
                    {
                        $Output .= $this->prepareDeepSubForumsSelectbox($forum["forumid"]);
                    }

                }

            }
        }

        return $Output;
    }

    public function prepareDeepSubForumsSelectbox($forumid)
    {
        global $TSUE;
        $Output = "";
        if( isset($this->forums[$forumid]) ) 
        {
            foreach( $this->forums[$forumid] as $forum ) 
            {
                if( !$forum["external_link"] && $this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
                {
                    $Output .= "<option value=\"" . $forum["forumid"] . "\">&nbsp;&nbsp;|-- " . $forum["title"] . "</option>";
                }

            }
        }

        return $Output;
    }

    public function prepareSearch($searchid)
    {
        global $TSUE;
        AddBreadcrumb(array( get_phrase("navigation_forums_search_forums") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=search_forums" ));
        if( !has_permission("cansearch_in_forums") ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        if( !$this->forumCategories ) 
        {
            show_error(get_phrase("forums_no_registered_forum_yet"));
        }

        if( $searchid ) 
        {
            return $this->prepareSearchResults($searchid, "search_forums");
        }

        $selectBOX = $this->prepareForumListMultipleSelectbox();
        eval("\$forums_search = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_search") . "\";");
        return $forums_search;
    }

    public function prepareRecentThreads($max_recent_threads = 5)
    {
        global $TSUE;
        if( !$this->forumCategories ) 
        {
            return NULL;
        }

        $Threads = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.*, f.title as forumtitle, f.password, m.membername, m.gender \r\n\t\tFROM tsue_forums_threads t \r\n\t\tLEFT JOIN tsue_forums f USING(forumid) \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tORDER BY t.last_post_date DESC LIMIT " . $max_recent_threads);
        if( !$Threads || !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
        {
            return NULL;
        }

        $recentThreadList = "";
        $count = 0;
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
        {
            if( isset($this->availableForums[$thread["forumid"]]) && has_forum_permission("canview_thread_list", $this->forumPermissions[$thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $this->checkForumPassword($thread["forumid"], $thread["password"]) ) 
            {
                $_memberid = $thread["memberid"];
                $_membername = strip_tags($thread["membername"]);
                $post_date = convert_relative_time($thread["post_date"]);
                $last_post_info = $this->prepareLastPostInfo($thread["last_post_info"]);
                $thread["title"] = strip_tags($thread["title"]);
                $sticky = ($thread["sticky"] ? "sticky" : "");
                $locked = ($thread["locked"] ? "locked" : "");
                $forum["forumid"] = $thread["forumid"];
                $threadREL = $threadLinkTitle = "";
                $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($thread["title"]) ) 
                {
                    $thread["title"] = substr($thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                }

                eval("\$thread_owner = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                eval("\$recentThreadList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recentThreads_list") . "\";");
                $count++;
            }

        }
        return $recentThreadList;
    }

    public function prepareSubscribedThreads()
    {
        global $TSUE;
        if( !$this->forumCategories ) 
        {
            return show_error(get_phrase("forums_no_registered_forum_yet"));
        }

        $Threads = $TSUE["TSUE_Database"]->query("SELECT s.email_notification, t.*, f.title as forumtitle, f.password, m.membername, m.gender \r\n\t\tFROM tsue_forums_thread_subscribe s \r\n\t\tLEFT JOIN tsue_forums_threads t USING(threadid) \r\n\t\tLEFT JOIN tsue_forums f USING(forumid) \r\n\t\tLEFT JOIN tsue_members m ON(m.memberid=t.memberid) \r\n\t\tWHERE s.memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " \r\n\t\tORDER BY t.last_post_date");
        if( !$Threads || !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
        {
            return show_error(get_phrase("message_nothing_found"));
        }

        $recentThreadList = "";
        $count = 0;
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
        {
            if( isset($this->availableForums[$thread["forumid"]]) && has_forum_permission("canview_thread_list", $this->forumPermissions[$thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $this->checkForumPassword($thread["forumid"], $thread["password"]) ) 
            {
                $_memberid = $thread["memberid"];
                $_membername = strip_tags($thread["membername"]);
                $post_date = convert_relative_time($thread["post_date"]);
                $last_post_info = $this->prepareLastPostInfo($thread["last_post_info"]);
                $thread["title"] = strip_tags($thread["title"]);
                $sticky = ($thread["sticky"] ? "sticky" : "");
                $locked = ($thread["locked"] ? "locked" : "");
                $forum["forumid"] = $thread["forumid"];
                $threadREL = $threadLinkTitle = "";
                $tdClass = ($count % 2 == 0 ? "secondRow" : "firstRow");
                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($thread["title"]) ) 
                {
                    $thread["title"] = substr($thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                }

                eval("\$post_date .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_subscribed_thread_link") . "\";");
                eval("\$thread_owner = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                eval("\$recentThreadList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("recentThreads_list") . "\";");
                $count++;
            }

        }
        if( !$recentThreadList ) 
        {
            return NULL;
        }

        $TSUE["TSUE_Language"]->phrase["forums_recent_threads"] = get_phrase("subscribed_threads");
        $isToggled = isToggled("recentThreads");
        $class = (!$isToggled ? "" : "hidden");
        $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("recentThreads_table") . "\";");
        return $Output;
    }

    public function handleSubscribedThreads($threadid, $forumid, $threadTitle, $poster, $postid)
    {
        global $TSUE;
        $Members = $TSUE["TSUE_Database"]->query("SELECT s.memberid, s.email_notification, m.membername, m.email \r\n\t\tFROM tsue_forums_thread_subscribe s \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tWHERE s.threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        if( $TSUE["TSUE_Database"]->num_rows($Members) ) 
        {
            $emailSubject = get_phrase("subscribed_threads_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
            $threadLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $forumid . "&amp;tid=" . $threadid;
            while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Members) ) 
            {
                if( $poster != $Member["memberid"] ) 
                {
                    if( $Member["email_notification"] ) 
                    {
                        $emailMessage = get_phrase("subscribed_threads_email_message", $Member["membername"], $threadTitle, "<a href=\"" . $threadLink . "\">" . $threadLink . "</a>", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                        sent_mail($Member["email"], $emailSubject, $emailMessage, $Member["membername"]);
                    }

                    alert_member($Member["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "thread_posts", $threadid, "subscribed_threads_new_post", $postid);
                }

            }
        }

    }

    public function prepareNewThreads($searchid, $todaysPosts = false)
    {
        global $TSUE;
        if( !$this->forumCategories ) 
        {
            show_error(get_phrase("forums_no_registered_forum_yet"));
        }

        if( is_member_of("unregistered") ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        $phrase = "navigation_forums_whats_new";
        $action = "new_threads";
        $maxDays = 1296000;
        $postDateCheck = $TSUE["TSUE_Member"]->info["lastforumvisit"];
        if( $todaysPosts ) 
        {
            $phrase = "navigation_forums_todays_posts";
            $action = "todays_posts";
            $postDateCheck = TIMENOW - 86400;
        }

        AddBreadcrumb(array( get_phrase($phrase) => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=" . $action ));
        if( !$searchid ) 
        {
            if( $TSUE["TSUE_Member"]->info["lastforumvisit"] + $maxDays < TIMENOW ) 
            {
                show_error(get_phrase("forums_no_unread_thread"));
            }

            $Threads = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.postid, t.threadid, t.forumid, f.password \r\n\t\t\tFROM tsue_forums_posts p \r\n\t\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\t\tLEFT JOIN tsue_forums f USING(forumid) \r\n\t\t\tWHERE p.post_date > " . $TSUE["TSUE_Database"]->escape($postDateCheck) . " \r\n\t\t\tGROUP BY t.threadid ORDER BY t.last_post_date DESC");
            if( !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
            {
                show_error(get_phrase("forums_no_unread_thread"));
            }

            $threadCache = array(  );
            while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
            {
                if( isset($this->availableForums[$thread["forumid"]]) && has_forum_permission("canview_thread_list", $this->forumPermissions[$thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $this->checkForumPassword($thread["forumid"], $thread["password"]) ) 
                {
                    $threadCache[] = $thread["threadid"];
                }

            }
            if( empty($threadCache) ) 
            {
                show_error(get_phrase("forums_no_unread_thread"));
            }

            $BuildQuery = array( "threads" => implode(",", $threadCache), "search_date" => TIMENOW, "search_type" => $action, "result_type" => "threads", "keywords" => $action );
            $TSUE["TSUE_Database"]->insert("tsue_search", $BuildQuery);
            $searchid = $TSUE["TSUE_Database"]->insert_id();
            redirect("?p=forums&pid=" . PAGEID . "&action=" . $action . "&searchid=" . $searchid);
        }

        return $this->prepareSearchResults($searchid, $action);
    }

    public function prepareSearchResultsAsPosts($searchThreads)
    {
        global $TSUE;
        $Output = "";
        AddBreadcrumb(array( get_phrase("forums_search_results") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=search_forums&amp;searchid=" . $searchThreads["searchid"] ));
        if( !$searchThreads["posts"] ) 
        {
            show_error(get_phrase("no_results_found"));
        }

        $PostsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE p.postid, t.threadid, f.password \r\n\t\tFROM tsue_forums_posts p \r\n\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\tINNER JOIN tsue_forums f USING(forumid) \r\n\t\tWHERE p.postid IN (" . $searchThreads["posts"] . ")");
        if( !$PostsCountQuery ) 
        {
            show_error(get_phrase("no_results_found"));
        }

        $Pagination = Pagination($PostsCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_posts_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=" . $searchThreads["search_type"] . "&amp;searchid=" . $searchThreads["searchid"] . "&amp;");
        $order_by = "p.post_date DESC";
        if( $searchThreads["order_by"] == "most_replies" ) 
        {
            $order_by = "t.reply_count DESC";
        }
        else
        {
            if( $searchThreads["order_by"] == "most_views" ) 
            {
                $order_by = "t.view_count DESC";
            }

        }

        $Posts = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE p.postid, p.memberid, p.post_date, p.message, t.threadid, t.forumid, t.title as threadTitle, f.title as forumTitle, f.password, m.membername, m.gender, g.groupname, g.groupstyle \r\n\t\tFROM tsue_forums_posts p \r\n\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\tINNER JOIN tsue_forums f USING(forumid) \r\n\t\tLEFT JOIN tsue_members m ON (p.memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE p.postid IN (" . $searchThreads["posts"] . ")\r\n\t\tORDER BY " . $order_by . " " . $Pagination["0"]);
        while( $Post = $TSUE["TSUE_Database"]->fetch_assoc($Posts) ) 
        {
            if( isset($this->availableForums[$Post["forumid"]]) && has_forum_permission("canview_thread_list", $this->forumPermissions[$Post["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $this->checkForumPassword($Post["forumid"], $Post["password"]) ) 
            {
                $_memberid = $Post["memberid"];
                $_membername = getMembername($Post["membername"], $Post["groupstyle"]);
                $_avatar = get_member_avatar($Post["memberid"], $Post["gender"], "s");
                $post_date = convert_relative_time($Post["post_date"]);
                $threadTitle = strip_tags($Post["threadTitle"]);
                $forumTitle = $Post["forumTitle"];
                $message = strip_tags($Post["message"]);
                if( 150 < strlen($message) ) 
                {
                    $message = substr($Post["message"], 0, 150) . " ... ";
                }

                $message = highlightString($searchThreads["keywords"], $message);
                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                eval("\$poster = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("show_results_as_posts") . "\";");
            }

        }
        return $Output . ((isset($Pagination["1"]) ? $Pagination["1"] : ""));
    }

    public function prepareSearchResults($searchid, $search_type)
    {
        global $TSUE;
        if( !$searchid ) 
        {
            show_error(get_phrase("forums_no_unread_thread"));
        }

        $searchThreads = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_search WHERE searchid = " . $TSUE["TSUE_Database"]->escape($searchid) . " AND search_type = " . $TSUE["TSUE_Database"]->escape($search_type));
        if( !$searchThreads ) 
        {
            show_error(get_phrase("forums_no_unread_thread"));
        }

        if( $searchThreads["result_type"] == "posts" ) 
        {
            return $this->prepareSearchResultsAsPosts($searchThreads);
        }

        $ThreadsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT SQL_NO_CACHE threadid FROM tsue_forums_threads WHERE threadid IN (" . $searchThreads["threads"] . ") GROUP BY threadid");
        if( !$ThreadsCountQuery ) 
        {
            show_error(get_phrase("forums_there_are_no_threads"));
        }

        $Pagination = Pagination($ThreadsCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_threads_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=" . $search_type . "&amp;searchid=" . $searchid . "&amp;");
        $order_by = "t.last_post_date DESC";
        if( $searchThreads["order_by"] == "most_replies" ) 
        {
            $order_by = "t.reply_count DESC";
        }
        else
        {
            if( $searchThreads["order_by"] == "most_views" ) 
            {
                $order_by = "t.view_count DESC";
            }

        }

        $Threads = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE t.*, p.postid, f.password, f.title as forumtitle, m.membername, m.gender \r\n\t\tFROM tsue_forums_threads t \r\n\t\tLEFT JOIN tsue_forums f USING(forumid) \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_forums_posts p USING(threadid) \r\n\t\tWHERE threadid IN (" . $searchThreads["threads"] . ") \r\n\t\tGROUP BY t.threadid ORDER BY " . $order_by . " " . $Pagination["0"]);
        if( !$Threads || !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
        {
            show_error(get_phrase("forums_there_are_no_threads"));
        }

        $forums_search_thread_rows = $last_post_info = "";
        while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
        {
            if( isset($this->availableForums[$thread["forumid"]]) && has_forum_permission("canview_thread_list", $this->forumPermissions[$thread["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $this->checkForumPassword($thread["forumid"], $thread["password"]) ) 
            {
                $_memberid = $thread["memberid"];
                $_membername = strip_tags($thread["membername"]);
                $_avatar = get_member_avatar($thread["memberid"], $thread["gender"], "s");
                $post_date = convert_relative_time($thread["post_date"]);
                $last_post_info = $this->prepareLastPostInfo($thread["last_post_info"]);
                $thread["title"] = strip_tags($thread["title"]);
                $sticky = ($thread["sticky"] ? "sticky" : "");
                $locked = ($thread["locked"] ? "locked" : "");
                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($thread["title"]) ) 
                {
                    $thread["title"] = substr($thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                }

                $forum["forumid"] = $thread["forumid"];
                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                eval("\$thread_owner = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                eval("\$forums_search_thread_rows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_search_thread_rows") . "\";");
            }

        }
        if( $searchThreads["search_type"] == "search_forums" ) 
        {
            AddBreadcrumb(array( get_phrase("forums_search_results") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=search_forums&amp;searchid=" . $searchid ));
        }
        else
        {
            AddBreadcrumb(array( get_phrase("forums_search_results") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;action=new_threads&amp;searchid=" . $searchid ));
        }

        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_search_thread_list") . "\";");
        return $Output;
    }

    public function prepareThreads($forumid)
    {
        global $TSUE;
        if( !isset($this->availableForums[$forumid]) ) 
        {
            show_error(get_phrase("forums_invalid_forum"));
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission("canview_thread_list", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        AddBreadcrumb(array( $forum["title"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] ));
        global $Page_Title;
        $Page_Title = strip_tags($forum["title"]);
        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return $this->preparePasswordForm($forum["forumid"]);
        }

        $forums_thread_rows = $last_post_info = "";
        $ThreadsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT threadid FROM tsue_forums_threads WHERE forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
        if( !$ThreadsCountQuery ) 
        {
            $Pagination["1"] = "";
            eval("\$forums_thread_rows = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_thread_no_rows") . "\";");
        }
        else
        {
            $Pagination = Pagination($ThreadsCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_threads_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] . "&amp;");
            $Threads = $TSUE["TSUE_Database"]->query("SELECT t.*, m.membername, m.gender, p.pname, p.cssname, poll.pid as poll\r\n\t\t\tFROM tsue_forums_threads t \r\n\t\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\t\tLEFT JOIN tsue_forums_thread_prefixes p USING(pid) \r\n\t\t\tLEFT JOIN tsue_poll poll USING(threadid)\r\n\t\t\tWHERE t.forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]) . " \r\n\t\t\tORDER BY t.sticky DESC, t.last_post_date DESC, t.post_date DESC " . $Pagination["0"]);
            if( !$Threads || !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
            {
                eval("\$forums_thread_rows = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_thread_no_rows") . "\";");
            }
            else
            {
                $canDeleteThreads = has_forum_permission("candelete_threads", $this->forumPermissions[$forumid][$TSUE["TSUE_Member"]->info["membergroupid"]]);
                $canMoveThreads = has_forum_permission("canmove_threads", $this->forumPermissions[$forumid][$TSUE["TSUE_Member"]->info["membergroupid"]]);
                $canLockThreads = has_forum_permission("canlock_threads", $this->forumPermissions[$forumid][$TSUE["TSUE_Member"]->info["membergroupid"]]);
                $canStickyThreads = has_forum_permission("cansticky_threads", $this->forumPermissions[$forumid][$TSUE["TSUE_Member"]->info["membergroupid"]]);
                while( $thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
                {
                    $checkboxes = "";
                    if( $canDeleteThreads || $canMoveThreads || $canLockThreads || $canStickyThreads ) 
                    {
                        $checkboxes = "<input type=\"checkbox\" name=\"mass_action_threads[]\" value=\"" . $thread["threadid"] . "\" id=\"thread_input_" . $thread["threadid"] . "\" />";
                    }

                    $_memberid = $thread["memberid"];
                    $_membername = strip_tags($thread["membername"]);
                    $_avatar = get_member_avatar($thread["memberid"], $thread["gender"], "s");
                    $post_date = convert_relative_time($thread["post_date"]);
                    $last_post_info = $this->prepareLastPostInfo($thread["last_post_info"]);
                    $thread["title"] = strip_tags($thread["title"]);
                    $sticky = ($thread["sticky"] ? "sticky" : "");
                    $locked = ($thread["locked"] ? "locked" : "");
                    $poll = ($thread["poll"] ? "poll" : "");
                    $thread["reply_count"] = friendly_number_format($thread["reply_count"]);
                    $thread["view_count"] = friendly_number_format($thread["view_count"]);
                    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] < strlen($thread["title"]) ) 
                    {
                        $thread["title"] = substr($thread["title"], 0, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_max_thread_title_limit"] - 3) . "...";
                    }

                    $thread_prefix = "";
                    if( $thread["pname"] && $thread["cssname"] ) 
                    {
                        $thread_prefix = "<span class=\"prefixButton " . $thread["cssname"] . "\">" . $thread["pname"] . "</span> ";
                    }

                    $canEditThreads = has_forum_permission("canedit_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $thread["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
                    $threadREL = ($canEditThreads ? "editThreadHD" : "");
                    $threadLinkTitle = ($canEditThreads ? get_phrase("forums_hold_down_to_edit_thread") : "");
                    $multiPages = PaginationNoMySQL($thread["reply_count"], $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_posts_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=" . PAGEID . "&fid=" . $forum["forumid"] . "&tid=" . $thread["threadid"] . "&");
                    $_alt = "";
                    eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                    eval("\$thread_owner = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    eval("\$forums_thread_rows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_thread_rows") . "\";");
                }
            }

        }

        $forums_post_thread_button = "";
        if( has_forum_permission("canpost_new_thread", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            eval("\$forums_post_thread_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_thread_button") . "\";");
        }

        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_thread_list") . "\";");
        $prepareDeepSubForums2 = $this->prepareDeepSubForums2($forumid);
        if( $prepareDeepSubForums2 ) 
        {
            $Output = $prepareDeepSubForums2 . $Output;
        }

        return $Output;
    }

    public function showThreadFirstPostForAjax($forumid, $threadid)
    {
        global $TSUE;
        if( !isset($this->availableForums[$forumid]) ) 
        {
            return -1;
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            return -2;
        }

        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return -3;
        }

        $Post = $TSUE["TSUE_Database"]->query_result("SELECT message FROM tsue_forums_posts WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " ORDER BY post_date ASC LIMIT 1");
        if( $Post ) 
        {
            return substr(strip_tags($TSUE["TSUE_Parser"]->parse($Post["message"])), 0, 197) . " ...";
        }

    }

    public function prepareThreadPosts($forumid, $threadid, $searchAgainCount = 0)
    {
        global $TSUE;
        if( !isset($this->availableForums[$forumid]) ) 
        {
            show_error(get_phrase("forums_invalid_forum"));
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission(array( "canview_thread_list", "canview_thread_posts" ), $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        AddBreadcrumb(array( $forum["title"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] ));
        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return $this->preparePasswordForm($forum["forumid"]);
        }

        $PostsCountQuery = $TSUE["TSUE_Database"]->row_count("SELECT p.postid, t.forumid \r\n\t\tFROM tsue_forums_posts p \r\n\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\tWHERE p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND t.forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
        if( !$PostsCountQuery ) 
        {
            if( isset($_GET["sc"]) ) 
            {
                $searchAgainCount = intval($_GET["sc"]);
            }

            if( $searchAgainCount ) 
            {
                show_error(get_phrase("message_content_error"));
            }

            $Thread = $TSUE["TSUE_Database"]->query_result("SELECT threadid,forumid from tsue_forums_threads WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
            if( $Thread ) 
            {
                redirect("?p=forums&pid=" . PAGEID . "&fid=" . $Thread["forumid"] . "&tid=" . $Thread["threadid"] . "&sc=1");
                exit();
            }

            show_error(get_phrase("message_content_error"));
        }

        $Pagination = Pagination($PostsCountQuery, $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_posts_perpage"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] . "&amp;tid=" . $threadid . "&amp;");
        $Posts = $TSUE["TSUE_Database"]->query("SELECT p.*, t.forumid, t.title, t.memberid as threadOwner, t.locked, m.membername, m.gender, m.joindate, m.lastactivity, m.visible, mp.total_posts, mp.signature, mp.custom_title, g.groupname, g.groupstyle, prefix.pname, prefix.cssname, b.memberid as isBanned, poll.pid, poll.date, poll.active, poll.question, poll.options, poll.votes, poll.voters, poll.multiple, poll.closeDaysAfter, poll.closed, poll.createdinThread\r\n\t\tFROM tsue_forums_posts p \r\n\t\tLEFT JOIN tsue_members m USING(memberid) \r\n\t\tLEFT JOIN tsue_member_profile mp USING(memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tINNER JOIN tsue_forums_threads t USING(threadid) \r\n\t\tLEFT JOIN tsue_forums_thread_prefixes prefix ON(t.pid=prefix.pid) \r\n\t\tLEFT JOIN tsue_member_bans b ON(p.memberid=b.memberid) \r\n\t\tLEFT JOIN tsue_poll poll USING(threadid)\r\n\t\tWHERE p.threadid = " . $TSUE["TSUE_Database"]->escape($threadid) . " AND t.forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]) . " \r\n\t\tORDER BY p.post_date ASC " . $Pagination["0"]);
        if( !$Posts || !$TSUE["TSUE_Database"]->num_rows($Posts) ) 
        {
            show_error(get_phrase("message_content_error"));
        }
        else
        {
            $forums_post_rows = "";
            $TSUE["TSUE_Database"]->shutdown_query("UPDATE tsue_forums_threads SET view_count = view_count + 1 WHERE threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
            $canUpload = has_forum_permission("canupload", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
            $canReply = has_forum_permission("canreply_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
            $canReport = has_permission("canreport");
            $post_count = 0;
            require_once(REALPATH . "library/classes/class_likes.php");
            $contentType = "thread_posts";
            $Likes = new TSUE_Likes();
            $Likes->prepareThreadLikesCache($threadid);
            $Likes->prepareMemberLikeCounts();
            $this->prepareAttachmentCache();
            $queryCache = $memberids = array(  );
            while( $post = $TSUE["TSUE_Database"]->fetch_assoc($Posts) ) 
            {
                $queryCache[] = $post;
                $memberids[] = $post["memberid"];
            }
            require_once(REALPATH . "/library/classes/class_awards.php");
            $TSUE_Awards = new TSUE_Awards($memberids);
            unset($memberids);
            if( is_file(REALPATH . "/library/plugins/TSUEPlugin_poll.php") ) 
            {
                require_once(REALPATH . "/library/plugins/TSUEPlugin_poll.php");
            }

            $threadPoll = "";
            foreach( $queryCache as $post ) 
            {
                if( function_exists("TSUEPlugin_poll") && $post["active"] && $post["question"] && !$threadPoll ) 
                {
                    $canEditPoll = $post["createdinThread"] && (has_forum_permission("canedit_polls", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_polls", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["threadOwner"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered"));
                    $canDeletePoll = $post["createdinThread"] && (has_forum_permission("candelete_polls", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_polls", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["threadOwner"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered"));
                    $threadPoll = TSUEPlugin_poll("left", array(  ), $post, false, $canEditPoll, $canDeletePoll, $threadid);
                }

                $post_count++;
                if( !isset($threadOwner) ) 
                {
                    $threadOwner = $post["threadOwner"];
                }

                if( !isset($threadTitle) ) 
                {
                    global $Page_Title;
                    $Page_Title = $threadTitle = strip_tags($post["title"]);
                    AddBreadcrumb(array( $threadTitle => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] . "&amp;tid=" . $threadid ));
                }

                if( !isset($thread_prefix) ) 
                {
                    $thread_prefix = "";
                    if( $post["pname"] && $post["cssname"] ) 
                    {
                        $thread_prefix = "<span class=\"prefixButton " . $post["cssname"] . "\">" . $post["pname"] . "</span> ";
                    }

                }

                $_memberid = $post["memberid"];
                $_membername = strip_tags($post["membername"]);
                $_avatar = get_member_avatar($post["memberid"], $post["gender"], "m");
                $post_date = convert_relative_time($post["post_date"]);
                $post["message"] = $TSUE["TSUE_Parser"]->parse($post["message"]);
                if( $post["edited_membername"] ) 
                {
                    $reason_for_editing = get_phrase("last_edited_by", strip_tags($post["edited_membername"]), convert_relative_time($post["edited_date"]), ($post["edit_reason"] ? get_phrase("reason") . " " . strip_tags($post["edit_reason"]) : ""));
                    eval("\$post['message'] .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("show_reason_for_editing") . "\";");
                }

                $memberSignature = memberSignature($post["signature"]);
                if( $post["locked"] && !has_forum_permission("canmanage_locked_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
                {
                    $canReply = false;
                    $canDelete = false;
                    $canEdit = false;
                    $canUpload = false;
                }
                else
                {
                    $canDelete = has_forum_permission("candelete_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
                    $canEdit = has_forum_permission("canedit_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $post["memberid"] == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
                }

                $reply_button = "";
                if( $canReply ) 
                {
                    eval("\$reply_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forum_posts_reply_button") . "\";");
                }

                $delete_button = "";
                if( $canDelete ) 
                {
                    eval("\$delete_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forum_posts_delete_button") . "\";");
                }

                $edit_button = "";
                if( $canEdit ) 
                {
                    eval("\$edit_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forum_posts_edit_button") . "\";");
                }

                $report_button = "";
                if( $canReport ) 
                {
                    $content_type = "forum_post";
                    $content_id = $post["postid"];
                    eval("\$report_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("report_post_no_image") . "\";");
                }

                $LikeList = $Likes->preparePostLikes($post["postid"]);
                if( isset($Likes->likedThisContent[$post["postid"]][$TSUE["TSUE_Member"]->info["memberid"]]) ) 
                {
                    $LikeLink = $Likes->unlikeButton($post["postid"], $post["memberid"], $contentType, $threadid, true);
                }
                else
                {
                    $LikeLink = $Likes->likeButton($post["postid"], $post["memberid"], $contentType, $threadid, true);
                }

                $_alt = "";
                eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
                $_membername = getMembername($_membername, $post["groupstyle"]);
                eval("\$poster = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $groupname = getGroupname($post);
                $total_posts = friendly_number_format($post["total_posts"]);
                $member_since = convert_time($post["joindate"], "d-m-Y");
                $gender = get_phrase("memberinfo_gender_" . (($post["gender"] == "m" ? "male" : ($post["gender"] == "f" ? "female" : "unspecified"))));
                $memberAwards = $TSUE_Awards->getMemberAwards($post["memberid"]);
                $likesReceived = (isset($Likes->memberLikeCounts[$post["memberid"]]) ? $Likes->memberLikeCounts[$post["memberid"]] : 0);
                $attachedFiles = "";
                if( isset($this->attachmentCache[$post["postid"]]) ) 
                {
                    $attachedFiles = $this->prepareAttachments($this->attachmentCache[$post["postid"]], $post["postid"], $canDelete);
                }

                $isMemberOnline = isMemberOnline($post);
                eval("\$forums_post_rows .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_rows") . "\";");
            }
            unset($queryCache);
            $forums_post_reply = $upload_javascript = $forums_upload_button = $moderatorTools = "";
            if( $canReply ) 
            {
                if( $canUpload ) 
                {
                    $content_type = "posts";
                    eval("\$upload_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_javascript") . "\";");
                    eval("\$forums_upload_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_upload_button") . "\";");
                }

                eval("\$forums_post_reply = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_reply") . "\";");
            }

            $social_media_buttons = social_media_buttons("forums_thread");
            $dropDownMenuLinks = $threadTools = "";
            $canMoveThreads = has_forum_permission("canmove_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
            $canDeleteThreads = has_forum_permission("candelete_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
            $canEditThreads = has_forum_permission("canedit_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) && $threadOwner == $TSUE["TSUE_Member"]->info["memberid"] && !is_member_of("unregistered");
            $canSubscribeToThreads = has_forum_permission("cansubscribe_to_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
            if( $canMoveThreads || $canDeleteThreads || $canEditThreads || $canSubscribeToThreads ) 
            {
                $dropDownMenuLinks = "\r\n\t\t\t\t" . (($canEditThreads ? "<li><a href=\"#\" rel=\"editThread\" id=\"" . $threadid . "\">" . get_phrase("forums_edit_thread") . "</a></li>" : "")) . "\r\n\t\t\t\t" . (($canMoveThreads ? "<li><a href=\"#\" rel=\"moveThread\" id=\"" . $threadid . "\">" . get_phrase("forums_move_thread") . "</a></li>" : "")) . "\r\n\t\t\t\t" . (($canDeleteThreads ? "<li><a href=\"#\" rel=\"deleteThread\" id=\"" . $threadid . "\">" . get_phrase("forums_delete_thread") . "</a></li>" : "")) . "\r\n\t\t\t\t" . (($canSubscribeToThreads ? "<li><a href=\"#\" rel=\"subscribeToThread\" id=\"" . $threadid . "\">" . get_phrase("subscribe_to_this_thread") . "</a></li>" : ""));
                eval("\$threadTools = \"" . $TSUE["TSUE_Template"]->LoadTemplate("dropDownMenu") . "\";");
            }

            eval("\$forums_post_list = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_list") . "\";");
            return $forums_post_list;
        }

    }

    public function prepareSinglePostData($post, $forum)
    {
        global $TSUE;
        $this->prepareAttachmentCache();
        $post_count = $post["postid"];
        $_memberid = $TSUE["TSUE_Member"]->info["memberid"];
        $_membername = strip_tags($TSUE["TSUE_Member"]->info["membername"]);
        $_avatar = get_member_avatar($TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["gender"], "m");
        $post_date = convert_relative_time(TIMENOW);
        $post["message"] = $TSUE["TSUE_Parser"]->parse($post["message"]);
        $memberSignature = memberSignature($post["signature"]);
        $canDelete = has_forum_permission("candelete_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("candelete_own_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        $canEdit = has_forum_permission("canedit_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) || has_forum_permission("canedit_own_posts", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        $reply_button = $LikeLink = $LikeList = "";
        $threadid = $post["threadid"];
        $forumid = $post["forumid"];
        $delete_button = "";
        if( $canDelete ) 
        {
            eval("\$delete_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forum_posts_delete_button") . "\";");
        }

        $edit_button = "";
        if( $canEdit ) 
        {
            eval("\$edit_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forum_posts_edit_button") . "\";");
        }

        $attachedFiles = "";
        if( isset($this->attachmentCache[$post["postid"]]) ) 
        {
            $attachedFiles = $this->prepareAttachments($this->attachmentCache[$post["postid"]], $post["postid"], $canDelete);
        }

        $_alt = "";
        eval("\$clickable_member_avatar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("clickable_member_avatar") . "\";");
        $_membername = getMembername($_membername, $TSUE["TSUE_Member"]->info["groupstyle"]);
        eval("\$poster = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $groupname = getMembername($TSUE["TSUE_Member"]->info["groupname"], $TSUE["TSUE_Member"]->info["groupstyle"]);
        $total_posts = friendly_number_format($TSUE["TSUE_Member"]->info["total_posts"]);
        $member_since = convert_time($TSUE["TSUE_Member"]->info["joindate"], "d-m-Y");
        $gender = get_phrase("memberinfo_gender_" . (($TSUE["TSUE_Member"]->info["gender"] == "m" ? "male" : ($TSUE["TSUE_Member"]->info["gender"] == "f" ? "female" : "unspecified"))));
        require_once(REALPATH . "/library/classes/class_awards.php");
        $TSUE_Awards = new TSUE_Awards($TSUE["TSUE_Member"]->info["memberid"]);
        $memberAwards = $TSUE_Awards->getMemberAwards($TSUE["TSUE_Member"]->info["memberid"]);
        $report_button = "";
        require_once(REALPATH . "library/classes/class_likes.php");
        $Likes = new TSUE_Likes();
        $Likes->prepareMemberLikeCounts();
        $likesReceived = (isset($Likes->memberLikeCounts[$TSUE["TSUE_Member"]->info["memberid"]]) ? $Likes->memberLikeCounts[$TSUE["TSUE_Member"]->info["memberid"]] : 0);
        $isMemberOnline = isMemberOnline($TSUE["TSUE_Member"]->info, true);
        eval("\$forums_post_rows = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_rows") . "\";");
        return $forums_post_rows;
    }

    public function prepareAttachments($attachmentCache, $postid, $canDelete = false)
    {
        global $TSUE;
        $attachedFiles = "";
        $deleteAttachment = "";
        foreach( $attachmentCache as $attachment_id => $attachment ) 
        {
            if( $canDelete ) 
            {
                eval("\$deleteAttachment = \"" . $TSUE["TSUE_Template"]->LoadTemplate("delete_attachment") . "\";");
            }

            $Rel = (is_valid_image($attachment["filename"]) ? " id=\"fancybox\" rel=\"post_" . $postid . "\"" : "");
            $filename = friendly_short_name(strip_tags($attachment["filename"]), 20);
            $filesize = friendly_size($attachment["filesize"]);
            $views = friendly_number_format($attachment["view_count"]);
            $fileIcon = file_icon($attachment["filename"]);
            eval("\$attachedFiles .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_attachments_list") . "\";");
        }
        eval("\$attachedFiles = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_attachments") . "\";");
        return $attachedFiles;
    }

    public function preparePostNewThread($forumid)
    {
        global $TSUE;
        if( isMuted($TSUE["TSUE_Member"]->info["muted"], "forums") ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        if( !isset($this->availableForums[$forumid]) ) 
        {
            show_error(get_phrase("forums_invalid_forum"));
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission("canpost_new_thread", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        AddBreadcrumb(array( $forum["title"] => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"], get_phrase("forums_post_new_thread") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=" . PAGEID . "&amp;fid=" . $forum["forumid"] . "&amp;action=new_thread" ));
        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return $this->preparePasswordForm($forum["forumid"]);
        }

        $forums_upload_button = $upload_javascript = "";
        $canUpload = has_forum_permission("canupload", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]);
        if( $canUpload ) 
        {
            $content_type = "threads";
            eval("\$upload_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("upload_javascript") . "\";");
            eval("\$forums_upload_button = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_upload_button") . "\";");
        }

        $forums_post_new_thread_subscribe = $forums_post_new_thread_poll = "";
        if( has_forum_permission("cansubscribe_to_threads", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            eval("\$forums_post_new_thread_subscribe = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_new_thread_subscribe") . "\";");
        }

        if( has_forum_permission("canpost_polls", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            eval("\$forums_post_new_thread_poll = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_new_thread_poll") . "\";");
        }

        $autoDescription = autoDescription(3);
        eval("\$forums_post_new_thread = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_post_new_thread") . "\";");
        return $forums_post_new_thread;
    }

    public function prepareRSS($forumid)
    {
        global $TSUE;
        if( !isset($this->availableForums[$forumid]) ) 
        {
            show_error(get_phrase("forums_invalid_forum"));
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission("canview_thread_list", $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return $this->preparePasswordForm($forum["forumid"]);
        }

        require(REALPATH . "/library/classes/class_rss.php");
        $FeedWriter = new FeedWriter();
        $FeedWriter->setTitle($forum["title"]);
        $FeedWriter->setLink($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=" . PAGEID . "&fid=" . $forum["forumid"]);
        $FeedWriter->setDescription($forum["description"]);
        $FeedWriter->setImage($forum["title"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=" . PAGEID . "&fid=" . $forum["forumid"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/forums/forum_icons/" . $forum["icon"] . ".png");
        $FeedWriter->setChannelElement("language", $TSUE["TSUE_Language"]->content_language);
        $FeedWriter->setChannelElement("pubDate", date(DATE_RSS, TIMENOW));
        $Threads = $TSUE["TSUE_Database"]->query("SELECT threadid, title, post_date FROM tsue_forums_threads WHERE forumid = " . $TSUE["TSUE_Database"]->escape($forum["forumid"]) . " ORDER BY last_post_date DESC LIMIT " . $TSUE["TSUE_Settings"]->settings["global_settings"]["forums_threads_perpage"]);
        if( !$Threads || !$TSUE["TSUE_Database"]->num_rows($Threads) ) 
        {
            show_error(get_phrase("forums_there_are_no_threads"));
        }

        while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
        {
            $itemLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&pid=" . PAGEID . "&fid=" . $forum["forumid"] . "&tid=" . $Thread["threadid"];
            $newItem = $FeedWriter->createNewItem();
            $newItem->setTitle(strip_tags($Thread["title"]));
            $newItem->setLink($itemLink);
            $newItem->setDate($Thread["post_date"]);
            $newItem->addElement("guid", $itemLink, array( "isPermaLink" => "true" ));
            $FeedWriter->addItem($newItem);
        }
        $FeedWriter->genarateFeed();
        exit();
    }

    public function prepareAttachmentDownload($attachment_id)
    {
        global $TSUE;
        $Attachment = $TSUE["TSUE_Database"]->query_result("SELECT a.content_type, a.upload_date, a.associated, a.filename, a.filesize, p.postid, t.forumid FROM tsue_attachments a INNER JOIN tsue_forums_posts p ON (a.content_id=p.postid) INNER JOIN tsue_forums_threads t USING(threadid) WHERE a.attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id));
        if( !$Attachment ) 
        {
            show_error(get_phrase("message_content_error"));
        }

        if( $Attachment["content_type"] != "posts" || !$Attachment["associated"] ) 
        {
            show_error(get_phrase("message_content_error"));
        }

        $forumid = $Attachment["forumid"];
        if( !isset($this->availableForums[$forumid]) ) 
        {
            show_error(get_phrase("forums_invalid_forum"));
        }

        $forum = $this->availableForums[$forumid];
        if( !has_forum_permission(array( "candownload", "canview_thread_list", "canview_thread_posts" ), $this->forumPermissions[$forum["forumid"]][$TSUE["TSUE_Member"]->info["membergroupid"]]) ) 
        {
            show_error(get_phrase("permission_denied"));
        }

        if( !$this->checkForumPassword($forum["forumid"], $forum["password"]) ) 
        {
            return $this->preparePasswordForm($forum["forumid"]);
        }

        $TSUE["TSUE_Database"]->update("tsue_attachments", array( "view_count" => array( "escape" => 0, "value" => "view_count+1" ) ), "attachment_id = " . $TSUE["TSUE_Database"]->escape($attachment_id), true);
        require_once(REALPATH . "/library/functions/functions_downloadFile.php");
        downloadFile(REALPATH . "/data/posts/" . $Attachment["filename"], $Attachment["filename"]);
        exit();
    }

    public function prepareAttachmentCache()
    {
        global $TSUE;
        $Attachments = $TSUE["TSUE_Database"]->query("SELECT attachment_id, content_id, view_count, filename, filesize FROM tsue_attachments WHERE content_type = 'posts' AND associated = 1 ORDER BY upload_date ASC");
        if( $Attachments && $TSUE["TSUE_Database"]->num_rows($Attachments) ) 
        {
            while( $A = $TSUE["TSUE_Database"]->fetch_assoc($Attachments) ) 
            {
                $this->attachmentCache[$A["content_id"]][$A["attachment_id"]] = $A;
            }
        }

    }

    public function deletePost($postid, $memberid)
    {
        global $TSUE;
        if( !$TSUE["TSUE_Database"]->delete("tsue_forums_posts", "postid = " . $TSUE["TSUE_Database"]->escape($postid)) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_new_replies"], $memberid, false);
        profileUpdate($memberid, array( "total_posts" => array( "escape" => 0, "value" => "IF(total_posts > 0, total_posts-1, 0)" ) ));
        $Attachments = $TSUE["TSUE_Database"]->query("SELECT filename FROM tsue_attachments WHERE content_type = 'posts' AND content_id = " . $TSUE["TSUE_Database"]->escape($postid));
        if( $TSUE["TSUE_Database"]->num_rows($Attachments) ) 
        {
            while( $Attachment = $TSUE["TSUE_Database"]->fetch_assoc($Attachments) ) 
            {
                $filename = REALPATH . "/data/posts/" . $Attachment["filename"];
                if( is_file($filename) ) 
                {
                    @unlink($filename);
                }

            }
            $TSUE["TSUE_Database"]->delete("tsue_attachments", "content_type = 'posts' AND content_id = " . $TSUE["TSUE_Database"]->escape($postid));
        }

        $TSUE["TSUE_Database"]->delete("tsue_liked_content", "content_type = 'thread_posts' AND content_id = " . $TSUE["TSUE_Database"]->escape($postid));
    }

    public function deleteThread($threadid, $forumid, $threadOwner = 0)
    {
        global $TSUE;
        $TSUE["TSUE_Database"]->delete("tsue_forums_threads", "threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        $TSUE["TSUE_Database"]->delete("tsue_forums_thread_subscribe", "threadid = " . $TSUE["TSUE_Database"]->escape($threadid));
        $TSUE["TSUE_Database"]->delete("tsue_poll", "threadid=" . $TSUE["TSUE_Database"]->escape($threadid) . " AND createdinThread = 1");
        $TSUE["TSUE_Database"]->update("tsue_poll", array( "threadid" => 0 ), "threadid=" . $TSUE["TSUE_Database"]->escape($threadid));
        updateMemberPoints($TSUE["TSUE_Settings"]->settings["global_settings"]["points_new_thread"], $threadOwner, false);
        $this->updateForumCounters($forumid);
        deleteCache("TSUEPlugin_recentThreads_");
    }

    public function moveThread($newforumid, $threadid, $forum)
    {
        global $TSUE;
        if( $TSUE["TSUE_Database"]->update("tsue_forums_threads", array( "forumid" => $newforumid ), "threadid=" . $TSUE["TSUE_Database"]->escape($threadid)) ) 
        {
            $newForum = $TSUE["TSUE_Database"]->query_result("SELECT forumid,parentid FROM tsue_forums WHERE forumid=" . $TSUE["TSUE_Database"]->escape($newforumid));
            $this->updateForumCounters($newForum["forumid"]);
            if( 0 < $newForum["parentid"] ) 
            {
                $this->updateForumCounters($newForum["parentid"]);
            }

            $oldForum = $TSUE["TSUE_Database"]->query_result("SELECT forumid,parentid FROM tsue_forums WHERE forumid=" . $TSUE["TSUE_Database"]->escape($forum["forumid"]));
            $this->updateForumCounters($oldForum["forumid"]);
            if( 0 < $oldForum["parentid"] ) 
            {
                $this->updateForumCounters($oldForum["parentid"]);
            }

            deleteCache("TSUEPlugin_recentThreads_");
        }

    }

    public function updateForumCounters($forumid = 0)
    {
        global $TSUE;
        if( !$forumid ) 
        {
            return false;
        }

        $Threads = $TSUE["TSUE_Database"]->query("SELECT threadid,reply_count FROM tsue_forums_threads WHERE forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
        $threadCount = $TSUE["TSUE_Database"]->num_rows($Threads);
        $replyCount = 0;
        if( $threadCount ) 
        {
            while( $Thread = $TSUE["TSUE_Database"]->fetch_assoc($Threads) ) 
            {
                $replyCount += $Thread["reply_count"];
            }
        }

        $last_post_info = "";
        $last_post_threadid = 0;
        $lastPostData = $TSUE["TSUE_Database"]->query_result("SELECT p.memberid, p.post_date, t.threadid, m.membername FROM tsue_forums_posts p INNER JOIN tsue_forums_threads t USING(threadid) LEFT JOIN tsue_members m ON (p.memberid=m.memberid) WHERE t.forumid = " . $TSUE["TSUE_Database"]->escape($forumid) . " ORDER BY p.post_date DESC LIMIT 1");
        if( $lastPostData ) 
        {
            $last_post_info = serialize(array( "lastpostdate" => $lastPostData["post_date"], "lastposter" => $lastPostData["membername"], "lastposterid" => $lastPostData["memberid"] ));
            $last_post_threadid = $lastPostData["threadid"];
        }

        $buildQuery = array( "replycount" => $replyCount, "threadcount" => $threadCount, "last_post_info" => $last_post_info, "last_post_threadid" => $last_post_threadid );
        $TSUE["TSUE_Database"]->update("tsue_forums", $buildQuery, "forumid = " . $TSUE["TSUE_Database"]->escape($forumid));
    }

    public function prepareLastPostInfo($info, $last_thread_title = "", $forumid = 0, $last_post_threadid = 0)
    {
        global $TSUE;
        if( $info ) 
        {
            $info = unserialize($info);
            if( $info && is_array($info) ) 
            {
                if( $last_thread_title && $forumid && $last_post_threadid ) 
                {
                    $last_thread_title = strip_tags($last_thread_title);
                    $last_thread_title = (40 < strlen($last_thread_title) ? substr($last_thread_title, 0, 40) . "..." : $last_thread_title);
                }

                $_memberid = $info["lastposterid"];
                $_membername = strip_tags($info["lastposter"]);
                eval("\$last_poster = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $last_post_date = convert_relative_time($info["lastpostdate"]);
            }

        }

        if( !isset($last_poster) ) 
        {
            $last_post_date = "";
            $last_poster = get_phrase("forums_no_last_post");
        }

        eval("\$forums_last_post_info = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_last_post_info") . "\";");
        return $forums_last_post_info;
    }

    public function prepareCache($forum)
    {
        $this->availableForums[$forum["forumid"]] = $forum;
    }

    public function preparePasswordForm($forumid)
    {
        global $TSUE;
        eval("\$forums_password_required_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forums_password_required_form") . "\";");
        return $forums_password_required_form;
    }

    public function checkForumPassword($forumid, $forumPassword = "")
    {
        if( strlen($forumPassword) != 40 ) 
        {
            return true;
        }

        $cookieName = "tsue_fp" . $forumid;
        if( !isset($_COOKIE[$cookieName]) || isset($_COOKIE[$cookieName]) && strlen($_COOKIE[$cookieName]) != 40 ) 
        {
            return false;
        }

        if( $_COOKIE[$cookieName] != $forumPassword ) 
        {
            return false;
        }

        return true;
    }

}



<?php 

class TSUE_Likes
{
    public $CanLike = false;
    public $TotalLikesCount = 0;
    public $likesCache = array(  );
    public $likedThisContent = false;
    public $memberLikeCounts = array(  );

    public function TSUE_Likes()
    {
        $this->CanLike = (!is_member_of("unregistered") && has_permission("canlike") ? true : false);
    }

    public function unlikeButton($ContentID = 0, $ContentMemberID = 0, $ContentType = "", $Extra = 0, $useTextLink = false)
    {
        global $TSUE;
        $unlikeButton = "";
        if( $this->CanLike ) 
        {
            $Phrase = get_phrase("button_unlike");
            $Image = "button_unlike";
            if( $useTextLink ) 
            {
                eval("\$unlikeButton = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_clickable_link_no_image") . "\";");
            }
            else
            {
                eval("\$unlikeButton = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_clickable_link") . "\";");
            }

        }

        return $unlikeButton;
    }

    public function likeButton($ContentID = 0, $ContentMemberID = 0, $ContentType = "", $Extra = 0, $useTextLink = false)
    {
        global $TSUE;
        $likeButton = "";
        if( $this->CanLike ) 
        {
            $Phrase = get_phrase("button_like");
            $Image = "button_like";
            if( $useTextLink ) 
            {
                eval("\$likeButton = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_clickable_link_no_image") . "\";");
            }
            else
            {
                eval("\$likeButton = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_clickable_link") . "\";");
            }

        }

        return $likeButton;
    }

    public function getValidLikeLink($ContentID = 0, $ContentMemberID = 0, $ContentType = "", $Extra = 0, $useTextLink = false)
    {
        if( $this->CanLike ) 
        {
            if( $this->likedThisContent ) 
            {
                $LikeLink = $this->unlikeButton($ContentID, $ContentMemberID, $ContentType, $Extra, $useTextLink);
            }
            else
            {
                $LikeLink = $this->likeButton($ContentID, $ContentMemberID, $ContentType, $Extra, $useTextLink);
            }

            return $LikeLink;
        }

    }

    public function saveLikeAndAlertContentOwner($ContentID = 0, $ContentMemberID = 0, $ContentType = "", $Extra = 0)
    {
        global $TSUE;
        $BuildQuery = array( "content_type" => $ContentType, "content_id" => $ContentID, "like_memberid" => $TSUE["TSUE_Member"]->info["memberid"], "like_date" => TIMENOW, "content_memberid" => $ContentMemberID, "extra_content_id" => $Extra );
        if( $TSUE["TSUE_Database"]->replace("tsue_liked_content", $BuildQuery) ) 
        {
            $this->alertMember($ContentID, $ContentMemberID, $Extra, $ContentType);
        }

    }

    public function alertMember($ContentID = 0, $ContentMemberID = 0, $Extra = 0, $ContentType)
    {
        global $TSUE;
        if( $ContentMemberID == $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            return NULL;
        }

        if( $ContentType == "profile_comments" ) 
        {
            alert_member($ContentMemberID, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $ContentType, $ContentID, "like_post");
        }
        else
        {
            if( $ContentType == "torrent" ) 
            {
                alert_member($ContentMemberID, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $ContentType, $ContentID, "like_torrent");
            }
            else
            {
                if( $ContentType == "torrent_comments" ) 
                {
                    alert_member($ContentMemberID, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $ContentType, $ContentID, "like_torrent_comments");
                }
                else
                {
                    if( $ContentType == "thread_posts" ) 
                    {
                        alert_member($ContentMemberID, $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], $ContentType, $ContentID, "like_thread_posts", $Extra);
                    }

                }

            }

        }

    }

    public function prepareMemberLikeCounts()
    {
        global $TSUE;
        $cacheName = "memberLikeCounts";
        if( !($this->memberLikeCounts = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
        {
            $Query = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE content_memberid, count(like_id) as totalLikes FROM tsue_liked_content GROUP BY content_memberid");
            if( $TSUE["TSUE_Database"]->num_rows($Query) ) 
            {
                while( $L = $TSUE["TSUE_Database"]->fetch_object($Query) ) 
                {
                    $this->memberLikeCounts[$L->content_memberid] = friendly_number_format($L->totalLikes);
                }
            }

            $TSUE["TSUE_Cache"]->saveCache($cacheName, serialize($this->memberLikeCounts));
        }
        else
        {
            $this->memberLikeCounts = unserialize($this->memberLikeCounts);
        }

    }

    public function prepareThreadLikesCache($threadid = 0)
    {
        global $TSUE;
        $LikeQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.content_id, l.like_memberid, m.membername, g.groupstyle \r\n\t\tFROM tsue_liked_content l \r\n\t\tINNER JOIN tsue_members m ON (l.like_memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE l.content_type = 'thread_posts' AND l.extra_content_id = " . $TSUE["TSUE_Database"]->escape($threadid) . " \r\n\t\tORDER BY l.like_date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($LikeQuery) ) 
        {
            while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($LikeQuery) ) 
            {
                $this->likesCache[$Like["content_id"]][] = $Like;
            }
        }

    }

    public function preparePostLikes($ContentID, $ContentType = "thread_posts")
    {
        global $TSUE;
        $LikeList = "";
        if( isset($this->likesCache[$ContentID]) && $this->likesCache[$ContentID] ) 
        {
            $LikeList = $this->prepareLikeList($this->likesCache[$ContentID], $ContentID, $ContentType);
        }

        eval("\$LikeList = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_list_holder") . "\";");
        return $LikeList;
    }

    public function prepareCommentLikesCache($torrentid = 0, $content_type)
    {
        global $TSUE;
        $LikeQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.content_id, l.like_memberid, m.membername, g.groupstyle \r\n\t\tFROM tsue_liked_content l \r\n\t\tINNER JOIN tsue_members m ON (l.like_memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE l.content_type = '" . $content_type . "' AND l.extra_content_id = " . $TSUE["TSUE_Database"]->escape($torrentid) . " \r\n\t\tORDER BY l.like_date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($LikeQuery) ) 
        {
            while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($LikeQuery) ) 
            {
                $this->likesCache[$Like["content_id"]][] = $Like;
            }
        }

    }

    public function prepareCommentLikes($ContentID, $ContentType = "torrent_comments")
    {
        global $TSUE;
        $LikeList = "";
        if( isset($this->likesCache[$ContentID]) && $this->likesCache[$ContentID] ) 
        {
            $LikeList = $this->prepareLikeList($this->likesCache[$ContentID], $ContentID, $ContentType);
        }

        eval("\$LikeList = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_list_holder") . "\";");
        return $LikeList;
    }

    public function getContentLikes($ContentID = 0, $ContentType)
    {
        global $TSUE;
        $LikeList = "";
        $LikeQuery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE l.content_id, l.like_memberid, m.membername, g.groupstyle \r\n\t\tFROM tsue_liked_content l \r\n\t\tINNER JOIN tsue_members m ON (l.like_memberid=m.memberid) \r\n\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\tWHERE l.content_type = " . $TSUE["TSUE_Database"]->escape($ContentType) . " AND l.content_id = " . $TSUE["TSUE_Database"]->escape($ContentID) . " \r\n\t\tORDER BY l.like_date DESC");
        if( $TSUE["TSUE_Database"]->num_rows($LikeQuery) ) 
        {
            while( $Like = $TSUE["TSUE_Database"]->fetch_assoc($LikeQuery) ) 
            {
                $this->likesCache[] = $Like;
            }
            $LikeList = $this->prepareLikeList(array(  ), $ContentID, $ContentType);
        }

        eval("\$LikeList = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_list_holder") . "\";");
        return $LikeList;
    }

    public function prepareLikeList($workWith = array(  ), $ContentID, $ContentType)
    {
        global $TSUE;
        if( !$workWith ) 
        {
            $workWith = $this->likesCache;
        }

        if( !$workWith ) 
        {
            return NULL;
        }

        foreach( $workWith as $LikeArray ) 
        {
            if( $LikeArray["like_memberid"] == $TSUE["TSUE_Member"]->info["memberid"] ) 
            {
                $this->likedThisContent[$LikeArray["content_id"]][$LikeArray["like_memberid"]] = true;
                $_memberid = $LikeArray["like_memberid"];
                $_membername = getMembername(get_phrase("you"), $LikeArray["groupstyle"]);
                eval("\$LikeList[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                break;
            }

        }
        $count = 0;
        foreach( $workWith as $LikeArray ) 
        {
            if( 3 < $count ) 
            {
                break;
            }

            if( $LikeArray["like_memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
            {
                $_memberid = $LikeArray["like_memberid"];
                $_membername = getMembername($LikeArray["membername"], $LikeArray["groupstyle"]);
                eval("\$LikeList[] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $count++;
            }

        }
        $TotalLikes = $this->TotalLikesCount = count($workWith);
        if( $TotalLikes <= 3 ) 
        {
            switch( $TotalLikes ) 
            {
                case 1:
                    $LikeList = get_phrase("like_x_likes_this", $LikeList["0"]);
                    break;
                case 2:
                    $LikeList = get_phrase("like_2_likes_this", $LikeList["0"], $LikeList["1"]);
                    break;
                case 3:
                    $LikeList = get_phrase("like_3_likes_this", $LikeList["0"], $LikeList["1"], $LikeList["2"]);
                    break;
            }
        }
        else
        {
            $TotalLikes -= 3;
            eval("\$TotalLikes = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_x_people_like_this") . "\";");
            $LikeList = get_phrase("like_others_likes_this", $LikeList["0"], $LikeList["1"], $LikeList["2"], $TotalLikes);
        }

        if( !has_permission("canview_like_list") ) 
        {
            $LikeList = strip_tags($LikeList, "<b><strong>");
        }

        if( !$LikeList ) 
        {
            return NULL;
        }

        eval("\$LikeList = \"" . $TSUE["TSUE_Template"]->LoadTemplate("like_list") . "\";");
        return $LikeList;
    }

}



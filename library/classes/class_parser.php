<?php 

class TSUE_Parser
{
    public $valid_elements = "<p><div><span><img><a><strong><b><em><ul><li><ol><br>";
    private $buildCensorArray = NULL;

    public function TSUE_Parser()
    {
    }

    public function stripEmptyTags($result)
    {
        $regexps = "~<(\\w+)\\b[^\\>]*>\\s*</\\1>~";
        do
        {
            $string = $result;
            $result = preg_replace($regexps, "", $string);
        }
        while( $result != $string );
        return $result;
    }

    public function clearTinymceP($text = "")
    {
        $text = str_replace("&nbsp;", " ", $text);
        if( substr($text, 0, 3) == "<p>" && substr($text, -4) == "</p>" ) 
        {
            $text = substr_replace($text, "", 0, 3);
            $text = substr_replace($text, "", -4);
        }

        $text = $this->stripEmptyTags($text);
        return $text;
    }

    public function parse($text = "")
    {
        global $TSUE;
        $text = trim($text);
        $text = $this->censorString($text);
        if( strlen($text) <= 2 ) 
        {
            return $text;
        }

        preg_match_all("#\\[(code|php)\\](.*?)\\[/\\1\\](\r\n?|\n?)#si", $text, $code_matches, PREG_SET_ORDER);
        $text = preg_replace("#\\[(code|php)\\](.*?)\\[/\\1\\](\r\n?|\n?)#si", "~~~TSUE_CODE~~~", $text);
        $text = $this->clearTinymceP($text);
        $text = $this->clean($text);
        $pattern = array( "#\\[quote=(.*?)\\](.*?)\\[\\/quote\\]#esi", "#\\[quote\\](.*?)\\[\\/quote\\]#esi" );
        $replace = array( "\$this->parseQuotesWithName('\$1','\$2')", "\$this->parseQuotes('\$1')" );
        while( preg_match($pattern[0], $text) || preg_match($pattern[1], $text) ) 
        {
            $text = preg_replace($pattern, $replace, $text);
        }
        $text = $this->parseBBCode($text);
        $text = $this->parseSmilies($text);
        $text = $this->parseURLS($text);
        if( $code_matches && count($code_matches) ) 
        {
            foreach( $code_matches as $match ) 
            {
                $match["2"] = html_declean(str_replace("\\\$", "\\\\\$", strip_tags($match["2"])));
                switch( strtolower($match["1"]) ) 
                {
                    case "php":
                        $CodeTitle = get_phrase("tinymce_php_code");
                        $CodeContent = $this->PHPCode($match["2"]);
                        $CodeMD5 = md5($CodeTitle . $CodeContent);
                        break;
                    default:
                        $CodeTitle = get_phrase("tinymce_code");
                        $CodeContent = html_clean($match["2"]);
                        $CodeMD5 = md5($CodeTitle . $CodeContent);
                        break;
                }
                eval("\$codeOutput = \"" . $TSUE["TSUE_Template"]->LoadTemplate("codeblocks_code") . "\";");
                $codeOutput = str_replace(array( "<code><code>", "</code></code>" ), array( "<code>", "</code>" ), $codeOutput);
                $text = preg_replace("#~~~TSUE_CODE~~~?#", $codeOutput, $text, 1);
            }
        }

        return trim($text);
    }

    public function parseURL($URL)
    {
        global $TSUE;
        $website_url = getSetting("global_settings", "website_url");
        $isInternal = substr($URL, 0, strlen($website_url)) == $website_url;
        $targetREL = ($isInternal ? "" : " target=\"_blank\" rel=\"nofollow\"");
        if( !$isInternal ) 
        {
            $URL = "http://anonym.to/?" . $URL;
        }

        return "<a href=\"" . $URL . "\"" . $targetREL . " title=\"\">";
    }

    public function parseURLS($text)
    {
        return preg_replace("|<a.*?href=\"(.*?)\".*?>|esi", "\$this->parseURL('\$1')", $text);
    }

    public function clean($text)
    {
        global $TSUE;
        $text = strip_tags($text, $this->valid_elements);
        return $TSUE["TSUE_Security"]->xss_clean($text);
    }

    public function remove_quotes($text)
    {
        return str_replace(array( "\"", "'" ), "", $text);
    }

    public function parseSmilies($text)
    {
        global $TSUE;
        if( $TSUE["TSUE_Settings"]->settings["dialog_smilies_cache"] ) 
        {
            foreach( $TSUE["TSUE_Settings"]->settings["dialog_smilies_cache"] as $Smilie ) 
            {
                $Smilie["smilie_title"] = $this->remove_quotes(strip_tags($Smilie["smilie_title"]));
                eval("\$smilieImage = \"" . $TSUE["TSUE_Template"]->LoadTemplate("smilie") . "\";");
                $smilieText = tsue_explode(" ", trim($Smilie["smilie_text"]));
                foreach( $smilieText as $Replace ) 
                {
                    $text = str_replace($Replace, $smilieImage, $text);
                }
            }
        }

        return $text;
    }

    public function parseBBCode($string)
    {
        global $TSUE;
        $search = array( "/\\[b\\](.*?)\\[\\/b\\]/si", "/\\[i\\](.*?)\\[\\/i\\]/si", "/\\[u\\](.*?)\\[\\/u\\]/si", "/\\[s\\](.*?)\\[\\/s\\]/si", "/\\[align\\=(left|center|right)\\](.*?)\\[\\/align\\]/si", "/\\[center\\](.*?)\\[\\/center\\]/si", "/\\[color\\=(.*?)\\](.*?)\\[\\/color\\]/is", "/\\[size\\=(.*?)\\](.*?)\\[\\/size\\]/is", "/\\[url\\=(.*?)\\](.*?)\\[\\/url\\]/is", "/\\[url\\](.*?)\\[\\/url\\]/si", "/\\[img\\](.*?)\\[\\/img\\]/si", "#\\[youtube\\](.*?)\\[\\/youtube\\]#esi", "#\\[facebook\\](.*?)\\[\\/facebook\\]#esi", "#\\[nfo\\](.*?)\\[\\/nfo\\]#esi", "#\\[spoiler\\](.*?)\\[\\/spoiler\\]#is", "#\\[YOU\\]#esi" );
        $replace = array( "<span style=\"font-weight:bold;\">\$1</span>", "<span style=\"font-style:italic;\">\$1</span>", "<span style=\"text-decoration:underline;\">\$1</span>", "<span style=\"text-decoration:line-through;\">\$1</span>", "<div style=\"text-align: \$1;\">\$2</div>", "<div style=\"text-align: center;\">\$1</div>", "\$2", "\$2", "<a href=\"\$1\" rel=\"nofollow\" title=\"\" target=\"_blank\">\$2</a>", "<a href=\"\$1\" rel=\"nofollow\" title=\"\" target=\"_blank\">\$1</a>", "<img src=\"\$1\" alt=\"\" />", "\$this->parseYoutube('\$1')", "\$this->parseFacebook('\$1')", "\$this->parseNFO('\$1')", $this->parseSpoiler("\$1"), "\$this->parseYou()" );
        return preg_replace($search, $replace, $string);
    }

    public function parseQuotesWithName($QuoteDetails = "", $CodeQuoteContent = "")
    {
        global $TSUE;
        if( !$CodeQuoteContent ) 
        {
            return NULL;
        }

        $explode = @tsue_explode("|", @trim($QuoteDetails));
        $MemberName = $PostID = "";
        if( isset($explode["0"]) ) 
        {
            $MemberName = trim(str_replace("\\\"", "\"", $explode["0"]));
        }

        if( isset($explode["1"]) ) 
        {
            $PostID = intval($explode["1"]);
        }

        $CodeQuoteContent = trim(str_replace("\\\"", "\"", $CodeQuoteContent));
        $MemberName = get_phrase("member_x_said", ucfirst($MemberName));
        eval("\$quoteOutput = \"" . $TSUE["TSUE_Template"]->LoadTemplate("codeblocks_quote_with_name") . "\";");
        return $quoteOutput;
    }

    public function parseQuotes($CodeQuoteContent = "")
    {
        global $TSUE;
        if( !$CodeQuoteContent ) 
        {
            return NULL;
        }

        $CodeQuoteContent = trim(str_replace("\\\"", "\"", $CodeQuoteContent));
        eval("\$quoteOutput = \"" . $TSUE["TSUE_Template"]->LoadTemplate("codeblocks_quote_without_name") . "\";");
        return $quoteOutput;
    }

    public function parseYoutube($videoTag = "")
    {
        global $TSUE;
        $youtube = "";
        if( $videoTag ) 
        {
            $videoTag = "http://www." . str_replace(array( "http://", "https://", "www." ), "", $videoTag);
            if( in_array(substr($videoTag, 0, 19), array( "http://www.youtu.be", "http://www.youtube." )) ) 
            {
                $parseURL = parse_url($videoTag);
                if( isset($parseURL["query"]) && $parseURL["query"] ) 
                {
                    $Query = explode("&", $parseURL["query"]);
                    $videoTag = str_replace("v=", "", $Query["0"]);
                }
                else
                {
                    if( isset($parseURL["path"]) && $parseURL["path"] ) 
                    {
                        $videoTag = str_replace("/", "", $parseURL["path"]);
                    }

                }

                if( $videoTag ) 
                {
                    eval("\$youtube = \"" . $TSUE["TSUE_Template"]->LoadTemplate("youtube") . "\";");
                }

                unset($videoTag);
                unset($parseURL);
                unset($Query);
            }

        }

        return $youtube;
    }

    public function parseFacebook($videoTag = "")
    {
        global $TSUE;
        $facebook = "";
        if( $videoTag ) 
        {
            if( strstr($videoTag, "facebook") !== false ) 
            {
                eval("\$facebook = \"" . $TSUE["TSUE_Template"]->LoadTemplate("facebook_full") . "\";");
            }
            else
            {
                eval("\$facebook = \"" . $TSUE["TSUE_Template"]->LoadTemplate("facebook") . "\";");
            }

        }

        return $facebook;
    }

    public function parseNFO($nfoTag = "")
    {
        global $TSUE;
        $nfo = "";
        if( $nfoTag ) 
        {
            eval("\$nfo = \"" . $TSUE["TSUE_Template"]->LoadTemplate("nfo") . "\";");
        }

        return $nfo;
    }

    public function parseSpoiler($spoilerTag = "")
    {
        global $TSUE;
        $spoiler = "";
        if( $spoilerTag ) 
        {
            eval("\$spoiler = \"" . $TSUE["TSUE_Template"]->LoadTemplate("spoiler") . "\";");
        }

        return $spoiler;
    }

    public function parseYou()
    {
        global $TSUE;
        $_memberid = $TSUE["TSUE_Member"]->info["memberid"];
        $_membername = getMembername(strip_tags($TSUE["TSUE_Member"]->info["membername"]), $TSUE["TSUE_Member"]->info["groupstyle"]);
        eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        return $member_info_link;
    }

    public function PHPCode($content = "")
    {
        if( strpos($content, "<?") == false ) 
        {
            $tagAdded = true;
            $content = "<?php\n" . $content;
        }
        else
        {
            $tagAdded = false;
        }

        $content = highlight_string($content, true);
        if( $tagAdded ) 
        {
            $content = preg_replace("#&lt;\\?php<br\\s*/?>#", "", $content, 1);
        }

        return trim($content);
    }

    public function censorString($string)
    {
        global $TSUE;
        if( !isset($TSUE["TSUE_Settings"]->settings["censor_cache"]["censorWords"]) ) 
        {
            return $string;
        }

        if( !isset($TSUE["TSUE_Settings"]->settings["censor_cache"]["censoring_censor_character"]) ) 
        {
            $TSUE["TSUE_Settings"]->settings["censor_cache"]["censoring_censor_character"] = "";
        }

        if( !$this->buildCensorArray ) 
        {
            $this->buildCensorArray = $this->buildCensorArray($TSUE["TSUE_Settings"]->settings["censor_cache"]["censorWords"], $TSUE["TSUE_Settings"]->settings["censor_cache"]["censoring_censor_character"]);
        }

        if( !empty($this->buildCensorArray["exact"]) ) 
        {
            $string = preg_replace(array_keys($this->buildCensorArray["exact"]), $this->buildCensorArray["exact"], $string);
        }

        if( !empty($this->buildCensorArray["any"]) ) 
        {
            $string = str_ireplace(array_keys($this->buildCensorArray["any"]), $this->buildCensorArray["any"], $string);
        }

        return $string;
    }

    public function buildCensorArray(array $words, $censorString)
    {
        $censorCache = array(  );
        if( !empty($words["exact"]) ) 
        {
            $exact = array(  );
            foreach( $words["exact"] as $word => $replace ) 
            {
                $search = "#(?<=\\W|^)(" . preg_quote($word, "#") . ")(?=\\W|\$)#i";
                if( is_int($replace) ) 
                {
                    $exact[$search] = str_repeat($censorString, $replace);
                }
                else
                {
                    $exact[$search] = $replace;
                }

            }
            $censorCache["exact"] = $exact;
        }

        if( !empty($words["any"]) ) 
        {
            $any = array(  );
            foreach( $words["any"] as $word => $replace ) 
            {
                if( is_int($replace) ) 
                {
                    $any[$word] = str_repeat($censorString, $replace);
                }
                else
                {
                    $any[$word] = $replace;
                }

            }
            $censorCache["any"] = $any;
        }

        return $censorCache;
    }

    public function parseHyperLinks($text, $secure = true)
    {
        $reg_exUrl = "/(http|https|ftp|ftps)\\:\\/\\/[a-zA-Z0-9\\-\\.]+\\.[a-zA-Z]{2,3}(\\/\\S*)?/";
        if( preg_match($reg_exUrl, $text, $url) ) 
        {
            $url = ($secure ? htmlspecialchars($url["0"]) : $url["0"]);
            return preg_replace($reg_exUrl, "<a href=\"" . $url . "\" rel=\"nofollow\" title=\"\" target=\"_blank\">" . $url . "</a>", $text);
        }

        return $text;
    }

}



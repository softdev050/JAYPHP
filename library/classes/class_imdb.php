<?php 

class IMDB
{
    public $movieInfo = array(  );
    public $posterPath = "";
    public $fetchedData = "";

    public function IMDB($movieKeyword)
    {
        $imdbUrl = $this->getIMDbUrlFromGoogle($movieKeyword);
        $html = ($imdbUrl ? $this->geturl($imdbUrl) : false);
        $html = preg_replace("~(\\r|\\n|\\r\\n)~", "", $html);
        if( stripos($html, "<link rel=\"canonical\" href=\"" . $imdbUrl . "\" />") !== false ) 
        {
            $this->scrapMovieInfo($html);
            $this->movieInfo["imdb_url"] = $imdbUrl;
        }
        else
        {
            $this->movieInfo["error"] = "No Title found on IMDb!";
            $this->fetchedData = $imdbUrl;
        }

    }

    public function getIMDbUrlFromGoogle($title)
    {
        $url = "http://www.google.com/search?q=site:imdb.com+" . rawurlencode($title);
        $html = $this->geturl($url);
        $imdburls = $this->match_all("/<a.*?href=\".*?(http:\\/\\/www\\.imdb.com\\/title\\/tt.*?\\/).*?\".*?>.*?<\\/a>/ms", $html, 1);
        return ($imdburls ? $imdburls[0] : false);
    }

    public function scrapMovieInfo($html)
    {
        $this->movieInfo["title_id"] = $this->match("/<link rel=\"canonical\" href=\"http:\\/\\/www.imdb.com\\/title\\/(tt[0-9]+)\\/\" \\/>/ms", $html, 1);
        $this->movieInfo["title"] = trim($this->match("/<title>(.*?) \\(.*?<\\/title>/ms", $html, 1));
        $this->movieInfo["year"] = trim($this->match("/<title>.*?\\(.*?([0-9][0-9][0-9][0-9]).*?\\).*?<\\/title>/ms", $html, 1));
        $this->movieInfo["rating"] = $this->match("/itemprop=\"ratingValue\">([0-9].[0-9])<\\/span>/ms", $html, 1);
        $this->movieInfo["genres"] = array(  );
        foreach( $this->match_all("/<a.*?>(.*?)<\\/a>/ms", $this->match("/Genre.?:(.*?)(<\\/div>|See more)/ms", $html, 1), 1) as $m ) 
        {
            array_push($this->movieInfo["genres"], $m);
        }
        $this->movieInfo["directors"] = array(  );
        foreach( $this->match_all("/<a.*?>(.*?)<\\/a>/ms", $this->match("/Director.?:(.*?)(<\\/div>|>.?and )/ms", $html, 1), 1) as $m ) 
        {
            array_push($this->movieInfo["directors"], $m);
        }
        $this->movieInfo["writers"] = array(  );
        foreach( $this->match_all("/<a.*?>(.*?)<\\/a>/ms", $this->match("/Writer.?:(.*?)(<\\/div>|>.?and )/ms", $html, 1), 1) as $m ) 
        {
            if( !preg_match("#more credits#", $m) ) 
            {
                array_push($this->movieInfo["writers"], $m);
            }

        }
        $this->movieInfo["stars"] = array(  );
        foreach( $this->match_all("/<a.*?>(.*?)<\\/a>/ms", $this->match("/Stars:(.*?)<\\/div>/ms", $html, 1), 1) as $m ) 
        {
            if( $m != "See full cast and crew" ) 
            {
                array_push($this->movieInfo["stars"], $m);
            }

        }
        $regex = "#<td class=\"primary_photo\"><a.*?>.*?<\\/a>.*?<\\/td>.*?<td.*?itemprop=\"actor\".*?><a.*?>.*?<span class=\"itemprop\" itemprop=\"name\">(.*?)<\\/span>.*?<\\/a>.*?<\\/td>.*?<td class=\"ellipsis\">.*?<\\/td>.*?<td class=\"character\">.*?<div>.*?<a.*?>.*?<\\/a>.*?<\\/div>.*?<\\/td>#ms";
        $this->movieInfo["cast"] = array(  );
        foreach( $this->match_all($regex, $html, 1) as $m ) 
        {
            array_push($this->movieInfo["cast"], trim(strip_tags($m)));
        }
        $this->movieInfo["mpaa_rating"] = $this->match("/infobar\">.<img.*?alt=\"(.*?)\".*?>/ms", $html, 1);
        if( $this->movieInfo["title_id"] != "" ) 
        {
            $releaseinfoHtml = $this->geturl("http://www.imdb.com/title/" . $this->movieInfo["title_id"] . "/releaseinfo");
            $this->movieInfo["also_known_as"] = $this->getAkaTitles($releaseinfoHtml, $usa_title);
            $this->movieInfo["usa_title"] = $usa_title;
            $this->movieInfo["release_date"] = $this->match("/Release Date:<\\/h4>.*?([0-9][0-9]? (January|February|March|April|May|June|July|August|September|October|November|December) (19|20)[0-9][0-9]).*?(\\(|<span)/ms", $html, 1);
            $this->movieInfo["release_dates"] = $this->getReleaseDates($releaseinfoHtml);
        }

        $this->movieInfo["plot"] = trim(strip_tags($this->match("/<p itemprop=\"description\">(.*?)<\\/p>/ms", $html, 1)));
        $this->movieInfo["poster"] = $this->match("/img_primary\">.*?<img.*?src=\"(.*?)\".*?<\\/td>/ms", $html, 1);
        $this->movieInfo["poster_large"] = "";
        $this->movieInfo["poster_small"] = "";
        if( $this->movieInfo["poster"] != "" && strrpos($this->movieInfo["poster"], "nopicture") === false && strrpos($this->movieInfo["poster"], "ad.doubleclick") === false ) 
        {
            $this->movieInfo["poster_large"] = substr($this->movieInfo["poster"], 0, strrpos($this->movieInfo["poster"], "_V1.")) . "_V1._SY500.jpg";
            $this->movieInfo["poster_small"] = substr($this->movieInfo["poster"], 0, strrpos($this->movieInfo["poster"], "_V1.")) . "_V1._SY150.jpg";
        }
        else
        {
            $this->movieInfo["poster"] = "";
        }

        $this->movieInfo["runtime"] = trim($this->match("/Runtime:<\\/h4>.*?([0-9]+) min.*?<\\/div>/ms", $html, 1));
        if( $this->movieInfo["runtime"] == "" ) 
        {
            $this->movieInfo["runtime"] = trim($this->match("/infobar.*?([0-9]+) min.*?<\\/div>/ms", $html, 1));
        }

        $this->movieInfo["top_250"] = trim($this->match("/Top 250 #([0-9]+)</ms", $html, 1));
        $this->movieInfo["oscars"] = trim($this->match("/Won ([0-9]+) Oscars./ms", $html, 1));
        $this->movieInfo["storyline"] = trim(strip_tags($this->match("/Storyline<\\/h2>(.*?)(<em|<\\/p>|<span)/ms", $html, 1)));
        $this->movieInfo["tagline"] = trim(strip_tags($this->match("/Tagline.?:<\\/h4>(.*?)(<span|<\\/div)/ms", $html, 1)));
        $this->movieInfo["votes"] = $this->match("/<span itemprop=\"ratingCount\">(.*?)<\\/span>/ms", $html, 1);
        if( $this->movieInfo["title_id"] != "" ) 
        {
            $this->movieInfo["media_images"] = $this->getMediaImages($this->movieInfo["title_id"]);
        }

    }

    public function getReleaseDates($html)
    {
        $releaseDates = array(  );
        foreach( $this->match_all("/<tr>(.*?)<\\/tr>/ms", $this->match("/Date<\\/th><\\/tr>(.*?)<\\/table>/ms", $html, 1), 1) as $r ) 
        {
            $country = trim(strip_tags($this->match("/<td><b>(.*?)<\\/b><\\/td>/ms", $r, 1)));
            $date = trim(strip_tags($this->match("/<td align=\"right\">(.*?)<\\/td>/ms", $r, 1)));
            array_push($releaseDates, $country . " = " . $date);
        }
        return $releaseDates;
    }

    public function getAkaTitles($html, &$usa_title)
    {
        $akaTitles = array(  );
        foreach( $this->match_all("/<tr>(.*?)<\\/tr>/msi", $this->match("/Also Known As(.*?)<\\/table>/ms", $html, 1), 1) as $m ) 
        {
            $akaTitleMatch = $this->match_all("/<td>(.*?)<\\/td>/ms", $m, 1);
            $akaTitle = trim($akaTitleMatch[0]);
            $akaCountry = trim($akaTitleMatch[1]);
            array_push($akaTitles, $akaTitle . " = " . $akaCountry);
            if( $akaCountry != "" && strrpos(strtolower($akaCountry), "usa") !== false ) 
            {
                $usa_title = $akaTitle;
            }

        }
        return $akaTitles;
    }

    public function getMediaImages($titleId)
    {
        $url = "http://www.imdb.com/title/" . $titleId . "/mediaindex";
        $html = $this->geturl($url);
        $media = array(  );
        $media = array_merge($media, $this->scanMediaImages($html));
        foreach( $this->match_all("/<a href=\"\\?page=(.*?)\">/ms", $this->match("/<span style=\"padding: 0 1em;\">(.*?)<\\/span>/ms", $html, 1), 1) as $p ) 
        {
            $html = $this->geturl($url . "?page=" . $p);
            $media = array_merge($media, $this->scanMediaImages($html));
        }
        return $media;
    }

    public function scanMediaImages($html)
    {
        $pics = array(  );
        foreach( $this->match_all("/src=\"(.*?)\"/ms", $this->match("/<div class=\"thumb_list\" style=\"font-size: 0px;\">(.*?)<\\/div>/ms", $html, 1), 1) as $i ) 
        {
            $i = substr($i, 0, strrpos($i, "_V1.")) . "_V1._SY500.jpg";
            array_push($pics, $i);
        }
        return $pics;
    }

    public function geturl($URL)
    {
        $html = "";
        if( function_exists("curl_init") && ($ch = curl_init()) ) 
        {
            $USERAGENT = "Googlebot/2.1 (+http://www.google.com/bot.html)";
            $REFERER = "http://www.google.com";
            curl_setopt($ch, CURLOPT_URL, $URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_REFERER, $REFERER);
            curl_setopt($ch, CURLOPT_USERAGENT, $USERAGENT);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $html = curl_exec($ch);
            curl_close($ch);
        }

        return $html;
    }

    public function match_all($regex, $str, $i = 0)
    {
        if( preg_match_all($regex, $str, $matches) === false ) 
        {
            return false;
        }

        return $matches[$i];
    }

    public function match($regex, $str, $i = 0)
    {
        if( preg_match($regex, $str, $match) == 1 ) 
        {
            return $match[$i];
        }

        return false;
    }

    public function savePoster($JPGURL)
    {
        $JPG = $this->geturl($JPGURL);
        if( $JPG ) 
        {
            return file_put_contents($this->posterPath . $this->movieInfo["title_id"] . ".jpg", $JPG);
        }

    }

}



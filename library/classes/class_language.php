<?php 

class TSUE_Language
{
    public $phrase = array(  );
    public $charset = NULL;
    public $date_format = NULL;
    public $time_format = NULL;
    public $content_language = NULL;

    public function TSUE_Language()
    {
        global $TSUE;
        $languageid = (isset($TSUE["TSUE_Member"]->info["languageid"]) && $TSUE["TSUE_Member"]->info["languageid"] ? $TSUE["TSUE_Member"]->info["languageid"] : $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"]);
        $cacheName = "language_" . $languageid;
        if( $Content = $TSUE["TSUE_Cache"]->readCache($cacheName) ) 
        {
            $Cache = unserialize($Content);
            $this->content_language = $Cache["content_language"];
            $this->charset = $Cache["charset"];
            $this->date_format = $Cache["date_format"];
            $this->time_format = $Cache["time_format"];
            $this->phrase = $Cache["phrase"];
            unset($Content);
            unset($Cache);
        }
        else
        {
            $Language = $TSUE["TSUE_Database"]->query_result("SELECT content_language, charset, date_format, time_format, phrase_global \r\n\t\tFROM tsue_languages \r\n\t\tWHERE languageid = " . $TSUE["TSUE_Database"]->escape($languageid) . " AND active = 1");
            if( $Language ) 
            {
                $this->content_language = strtolower($Language["content_language"]);
                $this->charset = strtolower($Language["charset"]);
                $this->date_format = $Language["date_format"];
                $this->time_format = $Language["time_format"];
                $this->phrase = unserialize($Language["phrase_global"]);
                $TSUE["TSUE_Cache"]->saveCache($cacheName, serialize(array( "content_language" => $this->content_language, "charset" => $this->charset, "date_format" => $this->date_format, "time_format" => $this->time_format, "phrase" => $this->phrase )));
            }
            else
            {
                exit( "<h1>Fatal Error:</h1>Invalid Language!" );
            }

        }

    }

}



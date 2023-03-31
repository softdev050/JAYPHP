<?php 

class TSUE_Template
{
    private $Cache = array(  );
    public $ThemeName = NULL;
    public $loadJavascripts = array( "jquery", "tsue" );
    public $loadJSPhrases = array(  );

    //public function TSUE_Template()
	function __construct()
    {
        global $TSUE;
        $cacheName = "templates_" . $TSUE["TSUE_Member"]->info["themeid"];
        if( $Content = $TSUE["TSUE_Cache"]->readCache($cacheName) ) 
        {
            $this->Cache = unserialize($Content);
            $this->ThemeName = $this->Cache["-ThemeName-"];
            unset($Content);
            unset($this->Cache["-ThemeName-"]);
        }
        else
        {
            $Templates = $TSUE["TSUE_Database"]->query("SELECT template.templatename, template.template, themes.themename \r\n\t\tFROM tsue_templates template \r\n\t\tINNER JOIN tsue_themes themes USING(themeid) \r\n\t\tWHERE template.themeid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["themeid"]) . " AND themes.active = 1");
            while( $Template = $TSUE["TSUE_Database"]->fetch_assoc($Templates) ) 
            {
                if( !$this->ThemeName ) 
                {
                    $this->ThemeName = $Template["themename"];
                    $this->Cache["-ThemeName-"] = $Template["themename"];
                }

                $this->Cache[$Template["templatename"]] = $Template["template"];
            }
            $TSUE["TSUE_Database"]->free($Templates);
            $TSUE["TSUE_Cache"]->saveCache($cacheName, serialize($this->Cache));
        }

    }

    public function loadJSPhrase($phrase)
    {
        if( is_array($phrase) ) 
        {
            foreach( $phrase as $phrs ) 
            {
                $this->loadJSPhrases[] = $phrs;
            }
            return NULL;
        }
        else
        {
            $this->loadJSPhrases[] = $phrase;
        }

    }

    public function prepareJSPhrases($phrase = "")
    {
        global $TSUE;
        if( $phrase ) 
        {
            $this->loadJSPhrase($phrase);
        }

        if( !empty($this->loadJSPhrases) ) 
        {
            $variables = "";
            foreach( $this->loadJSPhrases as $Phrase ) 
            {
                $variables .= "," . $Phrase . ": \"" . get_phrase($Phrase) . "\"";
            }
            return $variables;
        }

    }

    public function loadJavascripts($loadJS)
    {
        if( is_array($loadJS) ) 
        {
            foreach( $loadJS as $script ) 
            {
                $this->loadJavascripts[] = $script;
            }
            return NULL;
        }
        else
        {
            $this->loadJavascripts[] = $loadJS;
        }

    }

    public function prepareJS()
    {
        global $TSUE;
        if( count($this->loadJavascripts) ) 
        {
            $JS = "<script type=\"text/javascript\" src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/js.php?lv=" . V . "&s=" . implode(",", $this->loadJavascripts) . "\"></script>";
            $this->loadJavascripts = array( "jquery", "tsue" );
            return $JS;
        }

    }

    public function LoadTemplate($Name)
    {
        if( !isset($this->Cache[$Name]) ) 
        {
            global $TSUE;
            $Template = $TSUE["TSUE_Database"]->query_result("SELECT template FROM tsue_templates WHERE themeid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["themeid"]) . " AND templatename = " . $TSUE["TSUE_Database"]->escape($Name));
            if( $Template ) 
            {
                $this->Cache[$Name] = $Template["template"];
                logAction("Critical Performance Warning: An extra query used for the following template: " . $Name . " -- Filename: " . SCRIPTNAME);
            }
            else
            {
                logAction("Critical Performance Warning: Following template does not exists: " . $Name . " -- Filename: " . SCRIPTNAME);
            }

        }

        return $this->fixTemplate($this->Cache[$Name]);
    }

    public function fixTemplate($Content)
    {
		if(is_null($Content)){
			return "";
		}
		else{
			return str_replace("\\'", "'", addslashes($Content));
		}
        
    }

}



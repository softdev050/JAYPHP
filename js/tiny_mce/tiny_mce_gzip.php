<?php 
if( TinyMCE_Compressor::getParam("js") ) 
{
    $tinyMCECompressor = new TinyMCE_Compressor(array(  ));
    $tinyMCECompressor->handleRequest();
}


class TinyMCE_Compressor
{
    private $files = NULL;
    private $settings = NULL;
    private static $defaultSettings = array( "plugins" => "", "themes" => "", "languages" => "", "disk_cache" => true, "expires" => "30d", "cache_dir" => "./../../data/cache/", "compress" => true, "suffix" => "", "files" => "", "source" => false );

    public function __construct($settings = array(  ))
    {
        $this->settings = array_merge(self::$defaultSettings, $settings);
        if( empty($this->settings["cache_dir"]) ) 
        {
            $this->settings["cache_dir"] = dirname(__FILE__);
        }

    }

    public function &addFile($file)
    {
        $this->files .= (($this->files ? "," : "")) . $file;
        return $this;
    }

    public function handleRequest()
    {
        $files = array(  );
        $supportsGzip = false;
        $expiresOffset = $this->parseTime($this->settings["expires"]);
        $tinymceDir = dirname(__FILE__);
        $plugins = self::getParam("plugins");
        if( $plugins ) 
        {
            $this->settings["plugins"] = $plugins;
        }

        $plugins = explode(",", $this->settings["plugins"]);
        $themes = self::getParam("themes");
        if( $themes ) 
        {
            $this->settings["themes"] = $themes;
        }

        $themes = explode(",", $this->settings["themes"]);
        $languages = self::getParam("languages");
        if( $languages ) 
        {
            $this->settings["languages"] = $languages;
        }

        $languages = explode(",", $this->settings["languages"]);
        $tagFiles = self::getParam("files");
        if( $tagFiles ) 
        {
            $this->settings["files"] = $tagFiles;
        }

        $diskCache = self::getParam("diskcache");
        if( $diskCache ) 
        {
            $this->settings["disk_cache"] = $diskCache === "true";
        }

        $src = self::getParam("src");
        if( $src ) 
        {
            $this->settings["source"] = $src === "true";
        }

        $files[] = "tiny_mce";
        foreach( $languages as $language ) 
        {
            $files[] = "langs/" . $language;
        }
        foreach( $plugins as $plugin ) 
        {
            $files[] = "plugins/" . $plugin . "/editor_plugin";
            foreach( $languages as $language ) 
            {
                $files[] = "plugins/" . $plugin . "/langs/" . $language;
            }
        }
        foreach( $themes as $theme ) 
        {
            $files[] = "themes/" . $theme . "/editor_template";
            foreach( $languages as $language ) 
            {
                $files[] = "themes/" . $theme . "/langs/" . $language;
            }
        }
        $allFiles = array_merge($files, explode(",", $this->settings["files"]));
        for( $i = 0; $i < count($allFiles); $i++ ) 
        {
            $file = $allFiles[$i];
            if( $this->settings["source"] && file_exists($file . "_src.js") ) 
            {
                $file .= "_src.js";
            }
            else
            {
                if( file_exists($file . ".js") ) 
                {
                    $file .= ".js";
                }
                else
                {
                    $file = "";
                }

            }

            $allFiles[$i] = $file;
        }
        $hash = md5(implode("", $allFiles));
        $zlibOn = ini_get("zlib.output_compression") || ini_set("zlib.output_compression", 0) === false;
        $encodings = (isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ? strtolower($_SERVER["HTTP_ACCEPT_ENCODING"]) : "");
        $encoding = (preg_match("/\\b(x-gzip|gzip)\\b/", $encodings, $match) ? $match[1] : "");
        if( isset($_SERVER["---------------"]) ) 
        {
            $encoding = "x-gzip";
        }

        $supportsGzip = $this->settings["compress"] && !empty($encoding) && !$zlibOn && function_exists("gzencode");
        $cacheFile = $this->settings["cache_dir"] . "/" . $hash . (($supportsGzip ? ".gz" : ".js"));
        header("Content-type: text/javascript");
        header("Vary: Accept-Encoding");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expiresOffset) . " GMT");
        header("Cache-Control: public, max-age=" . $expiresOffset);
        if( $supportsGzip ) 
        {
            header("Content-Encoding: " . $encoding);
        }

        if( $this->settings["disk_cache"] && file_exists($cacheFile) ) 
        {
            readfile($cacheFile);
            return NULL;
        }

        $buffer = "var tinyMCEPreInit={base:'" . dirname($_SERVER["SCRIPT_NAME"]) . "',suffix:''};";
        foreach( $allFiles as $file ) 
        {
            if( $file ) 
            {
                $fileContents = $this->getFileContents($tinymceDir . "/" . $file);
                $buffer .= $fileContents;
            }

        }
        $buffer .= "tinymce.each(\"" . implode(",", $files) . "\".split(\",\"),function(f){tinymce.ScriptLoader.markDone(tinyMCE.baseURL+\"/\"+f+\".js\");});";
        if( $supportsGzip ) 
        {
            $buffer = gzencode($buffer, 9, FORCE_GZIP);
        }

        if( $this->settings["disk_cache"] ) 
        {
            @file_put_contents($cacheFile, $buffer);
        }

        echo $buffer;
    }

    public static function renderTag($tagSettings, $return = false)
    {
        $settings = array_merge(self::$defaultSettings, $tagSettings);
        if( empty($settings["cache_dir"]) ) 
        {
            $settings["cache_dir"] = dirname(__FILE__);
        }

        $scriptSrc = $settings["url"] . "?js=1";
        if( isset($settings["plugins"]) ) 
        {
            $scriptSrc .= "&plugins=" . ((is_array($settings["plugins"]) ? implode(",", $settings["plugins"]) : $settings["plugins"]));
        }

        if( isset($settings["themes"]) ) 
        {
            $scriptSrc .= "&themes=" . ((is_array($settings["themes"]) ? implode(",", $settings["themes"]) : $settings["themes"]));
        }

        if( isset($settings["languages"]) ) 
        {
            $scriptSrc .= "&languages=" . ((is_array($settings["languages"]) ? implode(",", $settings["languages"]) : $settings["languages"]));
        }

        if( isset($settings["disk_cache"]) ) 
        {
            $scriptSrc .= "&diskcache=" . (($settings["disk_cache"] === true ? "true" : "false"));
        }

        if( isset($tagSettings["files"]) ) 
        {
            $scriptSrc .= "&files=" . ((is_array($settings["files"]) ? implode(",", $settings["files"]) : $settings["files"]));
        }

        if( isset($settings["source"]) ) 
        {
            $scriptSrc .= "&src=" . (($settings["source"] === true ? "true" : "false"));
        }

        $scriptTag = "<script type=\"text/javascript\" src=\"" . htmlspecialchars($scriptSrc) . "\"></script>";
        if( $return ) 
        {
            return $scriptTag;
        }

        echo $scriptTag;
    }

    public static function getParam($name, $default = "")
    {
        if( !isset($_GET[$name]) ) 
        {
            return $default;
        }

        return preg_replace("/[^0-9a-z\\-_,]+/i", "", $_GET[$name]);
    }

    private function parseTime($time)
    {
        $multipel = 1;
        if( 0 < strpos($time, "h") ) 
        {
            $multipel = 3600;
        }

        if( 0 < strpos($time, "d") ) 
        {
            $multipel = 86400;
        }

        if( 0 < strpos($time, "m") ) 
        {
            $multipel = 2592000;
        }

        return intval($time) * $multipel;
    }

    private function getFileContents($file)
    {
        $content = file_get_contents($file);
        if( substr($content, 0, 3) === pack("CCC", 239, 187, 191) ) 
        {
            $content = substr($content, 3);
        }

        return $content;
    }

}



<?php 

class TSUE_Security
{
    protected $_xss_hash = "";
    protected $charset = "UTF-8";
    protected $_never_allowed_str = array( "document.cookie" => "[removed]", "document.write" => "[removed]", ".parentNode" => "[removed]", ".innerHTML" => "[removed]", "window.location" => "[removed]", "-moz-binding" => "[removed]", "<!--" => "&lt;!--", "-->" => "--&gt;", "<![CDATA[" => "&lt;![CDATA[" );
    protected $_never_allowed_regex = array( "javascript\\s*:" => "[removed]", "expression\\s*(\\(|&\\#40;)" => "[removed]", "vbscript\\s*:" => "[removed]", "Redirect\\s+302" => "[removed]" );

    public function xss_clean($str, $is_image = false)
    {
        if( is_array($str) ) 
        {
            while( list($key) = each($str) ) 
            {
                $str[$key] = $this->xss_clean($str[$key]);
            }
            return $str;
        }

        $str = $this->remove_invisible_characters($str);
        $str = $this->_validate_entities($str);
        $str = rawurldecode($str);
        $str = preg_replace_callback("/[a-z]+=([\\'\"]).*?\\1/si", array( $this, "_convert_attribute" ), $str);
        $str = preg_replace_callback("/<\\w+.*?(?=>|<|\$)/si", array( $this, "_decode_entity" ), $str);
        $str = $this->remove_invisible_characters($str);
        if( strpos($str, "\t") !== false ) 
        {
            $str = str_replace("\t", " ", $str);
        }

        $converted_string = $str;
        $str = $this->_do_never_allowed($str);
        if( $is_image === true ) 
        {
            $str = preg_replace("/<\\?(php)/i", "&lt;?\\1", $str);
        }
        else
        {
            $str = str_replace(array( "<?", "?" . ">" ), array( "&lt;?", "?&gt;" ), $str);
        }

        $words = array( "javascript", "expression", "vbscript", "script", "applet", "alert", "document", "write", "cookie", "window" );
        foreach( $words as $word ) 
        {
            $temp = "";
            $i = 0;
            for( $wordlen = strlen($word); $i < $wordlen; $i++ ) 
            {
                $temp .= substr($word, $i, 1) . "\\s*";
            }
            $str = preg_replace_callback("#(" . substr($temp, 0, -3) . ")(\\W)#is", array( $this, "_compact_exploded_words" ), $str);
        }
        do
        {
            $original = $str;
            if( preg_match("/<a/i", $str) ) 
            {
                $str = preg_replace_callback("#<a\\s+([^>]*?)(>|\$)#si", array( $this, "_js_link_removal" ), $str);
            }

            if( preg_match("/<img/i", $str) ) 
            {
                $str = preg_replace_callback("#<img\\s+([^>]*?)(\\s?/?>|\$)#si", array( $this, "_js_img_removal" ), $str);
            }

            if( preg_match("/script/i", $str) || preg_match("/xss/i", $str) ) 
            {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\\>#si", "[removed]", $str);
            }

        }
        while( $original != $str );
        unset($original);
        $str = $this->_remove_evil_attributes($str, $is_image);
        $naughty = "alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss";
        $str = preg_replace_callback("#<(/*\\s*)(" . $naughty . ")([^><]*)([><]*)#is", array( $this, "_sanitize_naughty_html" ), $str);
        $str = preg_replace("#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\\s*)\\((.*?)\\)#si", "\\1\\2&#40;\\3&#41;", $str);
        $str = $this->_do_never_allowed($str);
        if( $is_image === true ) 
        {
            return ($str == $converted_string ? true : false);
        }

        return $str;
    }

    public function xss_hash()
    {
        if( $this->_xss_hash == "" ) 
        {
            mt_srand();
            $this->_xss_hash = md5(time() + mt_rand(0, 1999999999));
        }

        return $this->_xss_hash;
    }

    public function entity_decode($str, $charset = "UTF-8")
    {
        if( stristr($str, "&") === false ) 
        {
            return $str;
        }

        if( function_exists("html_entity_decode") && strtolower($charset) != "utf-8" ) 
        {
            $str = html_entity_decode($str, ENT_COMPAT, $charset);
            $str = preg_replace("~&#x(0*[0-9a-f]{2,5})~ei", "chr(hexdec(\"\\1\"))", $str);
            return preg_replace("~&#([0-9]{2,4})~e", "chr(\\1)", $str);
        }

        $str = preg_replace("~&#x(0*[0-9a-f]{2,5});{0,1}~ei", "chr(hexdec(\"\\1\"))", $str);
        $str = preg_replace("~&#([0-9]{2,4});{0,1}~e", "chr(\\1)", $str);
        if( stristr($str, "&") === false ) 
        {
            $str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
        }

        return $str;
    }

    public function sanitize_filename($str, $relative_path = false)
    {
        $bad = array( "../", "<!--", "-->", "<", ">", "'", "\"", "&", "\$", "#", "{", "}", "[", "]", "=", ";", "?", "%20", "%22", "%3c", "%253c", "%3e", "%0e", "%28", "%29", "%2528", "%26", "%24", "%3f", "%3b", "%3d" );
        if( !$relative_path ) 
        {
            $bad[] = "./";
            $bad[] = "/";
        }

        $str = remove_invisible_characters($str, false);
        return stripslashes(str_replace($bad, "", $str));
    }

    protected function _compact_exploded_words($matches)
    {
        return preg_replace("/\\s+/s", "", $matches[1]) . $matches[2];
    }

    protected function _remove_evil_attributes($str, $is_image)
    {
        $evil_attributes = array( "on\\w*", "xmlns" );
        if( $is_image === true ) 
        {
            unset($evil_attributes[array_search("xmlns", $evil_attributes)]);
        }

        do
        {
            $str = preg_replace("#<(/?[^><]+?)([^A-Za-z\\-])(" . implode("|", $evil_attributes) . ")(\\s*=\\s*)([\"][^>]*?[\"]|[\\'][^>]*?[\\']|[^>]*?)([\\s><])([><]*)#i", "<\$1\$6", $str, -1, $count);
        }
        while( $count );
        return $str;
    }

    protected function _sanitize_naughty_html($matches)
    {
        $str = "&lt;" . $matches[1] . $matches[2] . $matches[3];
        $str .= str_replace(array( ">", "<" ), array( "&gt;", "&lt;" ), $matches[4]);
        return $str;
    }

    protected function _js_link_removal($match)
    {
        $attributes = $this->_filter_attributes(str_replace(array( "<", ">" ), "", $match[1]));
        return str_replace($match[1], preg_replace("#href=.*?(alert\\(|alert&\\#40;|javascript\\:|livescript\\:|mocha\\:|charset\\=|window\\.|document\\.|\\.cookie|<script|<xss|base64\\s*,)#si", "", $attributes), $match[0]);
    }

    protected function _js_img_removal($match)
    {
        $attributes = $this->_filter_attributes(str_replace(array( "<", ">" ), "", $match[1]));
        return str_replace($match[1], preg_replace("#src=.*?(alert\\(|alert&\\#40;|javascript\\:|livescript\\:|mocha\\:|charset\\=|window\\.|document\\.|\\.cookie|<script|<xss|base64\\s*,)#si", "", $attributes), $match[0]);
    }

    protected function _convert_attribute($match)
    {
        return str_replace(array( ">", "<", "\\" ), array( "&gt;", "&lt;", "\\\\" ), $match[0]);
    }

    protected function _filter_attributes($str)
    {
        $out = "";
        if( preg_match_all("#\\s*[a-z\\-]+\\s*=\\s*(\\042|\\047)([^\\1]*?)\\1#is", $str, $matches) ) 
        {
            foreach( $matches[0] as $match ) 
            {
                $out .= preg_replace("#/\\*.*?\\*/#s", "", $match);
            }
        }

        return $out;
    }

    protected function _decode_entity($match)
    {
        return $this->entity_decode($match[0], strtoupper($this->charset));
    }

    protected function _validate_entities($str)
    {
        $str = preg_replace("|\\&([a-z\\_0-9\\-]+)\\=([a-z\\_0-9\\-]+)|i", $this->xss_hash() . "\\1=\\2", $str);
        $str = preg_replace("#(&\\#?[0-9a-z]{2,})([\\x00-\\x20])*;?#i", "\\1;\\2", $str);
        $str = preg_replace("#(&\\#x?)([0-9A-F]+);?#i", "\\1\\2;", $str);
        $str = str_replace($this->xss_hash(), "&", $str);
        return $str;
    }

    protected function _do_never_allowed($str)
    {
        foreach( $this->_never_allowed_str as $key => $val ) 
        {
            $str = str_replace($key, $val, $str);
        }
        foreach( $this->_never_allowed_regex as $key => $val ) 
        {
            $str = preg_replace("#" . $key . "#i", $val, $str);
        }
        return $str;
    }

    protected function remove_invisible_characters($str, $url_encoded = true)
    {
        $non_displayables = array(  );
        if( $url_encoded ) 
        {
            $non_displayables[] = "/%0[0-8bcef]/";
            $non_displayables[] = "/%1[0-9a-f]/";
        }

        $non_displayables[] = "/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]+/S";
        do
        {
            $str = preg_replace($non_displayables, "", $str, -1, $count);
        }
        while( $count );
        return $str;
    }

}



<?php 
function fullDebugOutput()
{
    global $TSUE;
    $Version = "1.1";
    $pageTime = microtime(true) - TIMESTART;
    if( 0 < $pageTime ) 
    {
        $dbPercent = $TSUE["TSUE_Database"]->totalQueryRunTime / $pageTime * 100;
    }
    else
    {
        $dbPercent = 0;
    }

    $imagePath = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/buttons/refresh.png";
    $HTML = "\r\n\t<h1>\r\n\t<span style=\"float: right;\" id=\"top\">\r\n\t\t<a href=\"\"><img src=\"" . $imagePath . "\" alt=\"" . get_phrase("button_refresh") . "\" title=\"" . get_phrase("button_refresh") . "\" /></span></a>\r\n\tPage loaded in " . number_format($pageTime, 4) . " seconds</h1>\r\n\t<h2>PHP Total Memory Usage: " . number_format(memory_get_usage() / 1024 / 1024, 4) . "MB (Peak: " . number_format(memory_get_peak_usage() / 1024 / 1024, 4) . "MB)</h2>\r\n\t<h2>Total MySQL Queries: " . $TSUE["TSUE_Database"]->querycount . " (Took " . number_format($TSUE["TSUE_Database"]->totalQueryRunTime, 4) . " seconds, " . number_format($dbPercent, 1) . "%, Shutdown Queries: " . count($TSUE["TSUE_Database"]->shutdown_queries) . ")</h2>";
    $HTML .= "\r\n\t<ol>";
    foreach( $TSUE["TSUE_Database"]->query_cache as $array ) 
    {
        list($queryTime, $query) = $array;
        $queryText = rtrim($query);
        if( preg_match("#(^|\\n)(\\t+)([ ]*)(?=\\S)#", $queryText, $match) ) 
        {
            $queryText = preg_replace("#(^|\\n)\\t{1," . strlen($match[2]) . "}#", "\$1", $queryText);
        }

        $explainOutput = "";
        if( preg_match("#^\\s*SELECT\\s#i", $queryText) ) 
        {
            $explainQuery = $TSUE["TSUE_Database"]->query("EXPLAIN " . $queryText);
            if( $TSUE["TSUE_Database"]->num_rows($explainQuery) ) 
            {
                $explainOutput .= "\r\n\t\t\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t<th>id</th>\r\n\t\t\t\t\t\t<th>Select Type</th>\r\n\t\t\t\t\t\t<th>Table</th>\r\n\t\t\t\t\t\t<th>Type</th>\r\n\t\t\t\t\t\t<th>Possible Keys</th>\r\n\t\t\t\t\t\t<th>Key</th>\r\n\t\t\t\t\t\t<th>Key Len</th>\r\n\t\t\t\t\t\t<th>Ref</th>\r\n\t\t\t\t\t\t<th>Rows</th>\r\n\t\t\t\t\t\t<th>Extra</th>\r\n\t\t\t\t\t</tr>";
                while( $explainRow = $TSUE["TSUE_Database"]->fetch_assoc($explainQuery) ) 
                {
                    foreach( $explainRow as $key => $value ) 
                    {
                        if( trim($value) === "" ) 
                        {
                            $explainRow[$key] = "&nbsp;";
                        }
                        else
                        {
                            $explainRow[$key] = htmlspecialchars($value);
                        }

                    }
                    if( !$explainRow["possible_keys"] || $explainRow["possible_keys"] == "&nbsp;" ) 
                    {
                        $explainRow["possible_keys"] = "<span style=\"color: red;\">NO KEY</span>";
                    }

                    $explainOutput .= "\r\n\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t<td>" . $explainRow["id"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["select_type"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["table"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["type"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["possible_keys"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["key"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["key_len"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["ref"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["rows"] . "</td>\r\n\t\t\t\t\t\t<td>" . $explainRow["Extra"] . "</td>\r\n\t\t\t\t\t</tr>";
                }
                $explainOutput .= "\r\n\t\t\t\t</table>";
            }

        }

        $HTML .= "\r\n\t\t\t<li>\r\n\t\t\t\t<div>Took " . number_format($queryTime, 4) . " seconds.</div>\r\n\t\t\t\t<pre>" . htmlspecialchars($queryText) . "</pre>\t\t\t\r\n\t\t\t\t" . $explainOutput . "\r\n\t\t\t</li>";
    }
    $HTML .= "\r\n\t</ol>\r\n\t<h2>Shutdown Queries</h2>\r\n\t<ol>\r\n\t";
    foreach( $TSUE["TSUE_Database"]->shutdown_queries as $query ) 
    {
        $queryText = rtrim($query);
        if( preg_match("#(^|\\n)(\\t+)([ ]*)(?=\\S)#", $queryText, $match) ) 
        {
            $queryText = preg_replace("#(^|\\n)\\t{1," . strlen($match[2]) . "}#", "\$1", $queryText);
        }

        $HTML .= "\r\n\t\t\t<li>\r\n\t\t\t<pre>" . htmlspecialchars($queryText) . "</pre>\r\n\t\t\t</li>";
    }
    $HTML .= "\r\n\t</ol>";
    $includedFiles = "";
    $fileNames = get_included_files();
    $baseDir = dirname(reset($fileNames));
    foreach( $fileNames as $file ) 
    {
        $file = preg_replace("#^" . preg_quote($baseDir, "#") . "(\\\\|/)#", "", $file);
        $file = htmlspecialchars($file);
        $includedFiles .= "\r\n\t\t<li style=\"line-height: 1.4;\">" . $file . "</li>";
    }
    $HTML .= "\r\n\t<h2>Included Files (" . number_format(count($fileNames)) . ")</h2>\r\n\t<ul class=\"includedFiles\">" . $includedFiles . "</ul>";
    $imagePath = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/buttons/up.png";
    $HTML = "\r\n\t<html>\r\n\t\t<head>\r\n\t\t\t<title>TSUE Debug v" . $Version . "</title>\r\n\t\t\t<style>\r\n\t\t\t*{padding: 0; margin: 0}\r\n\t\t\t#wrapper{width: 900px; margin: 10px auto}\r\n\t\t\tbody{font-family: \"Trebuchet MS\", Helvetica, Arial, sans-serif;color: rgb(20,20,20); font-size: 12px; font-weight: normal;}\r\n\t\t\tol{margin-left: 20px;}\r\n\t\t\tol li{margin-bottom: 20px; margin-top: 20px;}\r\n\t\t\tul.includedFiles li{list-style: none; float: left; border: 1px solid #ddd; padding: 5px;margin: 0 7px 7px 0; width: 280px; font-size: 11px; text-align: left;}\r\n\t\t\th1{font-weight: bold; font-size: 18px; margin-bottom: 5px; border-bottom: 1px solid #ddd;}\r\n\t\t\th2{font-weight: bold; font-size: 12px; margin-bottom: 3px;}\r\n\t\t\tpre\r\n\t\t\t{\r\n\t\t\t\tpadding: 10px;\r\n\t\t\t\tmargin-bottom: 10px;\r\n\t\t\t\tborder-left: 11px solid #ccc;\r\n\t\t\t\tborder-top: 1px solid #ccc;\r\n\t\t\t\tborder-right: 1px solid #ccc;\r\n\t\t\t\tborder-bottom: 1px solid #ccc;\r\n\t\t\t\toverflow: auto;\r\n\t\t\t\twidth: 850px;\r\n\t\t\t\tbackground-color:#eee;\r\n\t\t\t\tmax-height: 250px;\r\n\t\t\t\tfont-size: 11px;\r\n\t\t\t\ttext-align: left;\r\n\t\t\t\tfont-family: Consolas,Menlo,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New,monospace,serif;\r\n\t\t\t\tcolor: #555;\r\n\t\t\t}\r\n\t\t\ttable{width: 880px;table-layout: fixed;}\r\n\t\t\ttable th{text-align: left; font-weight: bold; font-size: 12px; background: #999; padding: 5px; color: #fff}\r\n\t\t\ttable td{word-wrap:break-word;font-weight: normal !important; font-size: 11px; background: #fff; color: #000; padding: 5px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd;}\r\n\t\t\ttable td:first-child{border-left: 1px solid #ddd;}\r\n\t\t\t</style>\r\n\t\t</head>\r\n\t\t<body>\r\n\t\t\t<div id=\"wrapper\">\r\n\t\t\t\t" . $HTML . "\r\n\t\t\t\t<div style=\"float: right; margin: 10px;\"><a href=\"#top\"><img src=\"" . $imagePath . "\" alt=\"\" title=\"\" /></a></div>\r\n\t\t\t</div>\r\n\t\t</body>\r\n\t</html>";
    return $HTML;
}

function basicDebugOutput()
{
    global $TSUE;
    if( isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) ) 
    {
        $queryString = "?" . htmlspecialchars($_SERVER["QUERY_STRING"]);
    }
    else
    {
        $queryString = "?p=" . PAGEFILE . "&amp;pid=" . PAGEID;
    }

    $debugURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . $queryString . "&amp;debug=1";
    $saperator = "&nbsp;-&nbsp;";
    /*$style = "width: 960px; margin: 0 auto; text-align: left; font-size: 11px;";
    /*return "\r\n\t<div style=\"" . $style . "\">\r\n\t\tPage Generation <a href=\"" . $debugURL . "\">" . number_format(microtime(true) - TIMESTART, 4) . " seconds</a>" . $saperator . "\r\n\t\tMemory Usage <a href=\"" . $debugURL . "\">" . number_format(memory_get_usage() / 1024 / 1024, 4) . " MB</a>" . $saperator . "\r\n\t\tQueries Executed <a href=\"" . $debugURL . "\">" . $TSUE["TSUE_Database"]->querycount . "</a>\r\n\t</div>";*/
}



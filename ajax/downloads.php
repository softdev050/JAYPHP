<?php 
define("SCRIPTNAME", "downloads.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

globalize("post", array( "securitytoken" => "TRIM" ));
if( !isValidToken($securitytoken) ) 
{
    ajax_message(get_phrase("invalid_security_token"), "-ERROR-");
}

switch( $TSUE["action"] ) 
{
    case "import":
        $importPath = REALPATH . "data/downloads/import/";
        if( !is_dir($importPath) || !is_readable($importPath) ) 
        {
            ajax_message(get_phrase("import_path_error"), "-ERROR-");
        }

        $Files = scandir($importPath);
        $validFiles = array(  );
        foreach( $Files as $dFile ) 
        {
            if( in_array(file_extension($dFile), tsue_explode(",", getSetting("downloads", "allowed_file_types"))) ) 
            {
                $validFiles[] = $dFile;
            }

        }
        if( !$validFiles ) 
        {
            ajax_message(get_phrase("import_no_file"), "-ERROR-");
        }

        $Output = "\r\n\t\t<b>" . get_phrase("select_a_file_to_import") . "</b>\r\n\t\t<form method=\"post\" name=\"importFileForm\" id=\"importFileForm\">\r\n\t\t\t<select name=\"importFile\" id=\"importFile\" class=\"s\">";
        foreach( $validFiles as $File ) 
        {
            $Output .= "\r\n\t\t\t\t<option value=\"" . $File . "\">" . $File . "</option>";
        }
        $Output .= "\r\n\t\t\t</select>\r\n\t\t\t<input type=\"submit\" class=\"submit\" value=\"" . get_phrase("import") . "\" />\r\n\t\t</form>";
        ajax_message($Output, "", false);
        break;
    case "delete_file":
        globalize("post", array( "did" => "INT" ));
        if( !$did ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $File = $TSUE["TSUE_Database"]->query_result("SELECT cid, title, filename, memberid FROM tsue_downloads WHERE did = " . $TSUE["TSUE_Database"]->escape($did));
        if( !$File ) 
        {
            ajax_message(get_phrase("message_content_error"), "-ERROR-");
        }

        $canDelete = has_permission("candelete_files") || has_permission("candelete_own_files") && $File["memberid"] === $TSUE["TSUE_Member"]->info["memberid"];
        if( !$canDelete ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        if( $TSUE["TSUE_Database"]->delete("tsue_downloads", "did=" . $TSUE["TSUE_Database"]->escape($did)) ) 
        {
            require_once(REALPATH . "library/functions/functions_downloads.php");
            deleteFile($File["filename"]);
            $BuildQuery = array( "total_files" => array( "escape" => 0, "value" => "IF(total_files > 0, total_files-1, 0)" ) );
            $TSUE["TSUE_Database"]->update("tsue_downloads_categories", $BuildQuery, "cid=" . $TSUE["TSUE_Database"]->escape($File["cid"]));
            $Phrase = get_phrase("file_x_has_been_deleted", substr(strip_tags($File["title"]), 0, 85));
            logAction($Phrase);
            ajax_message($Phrase);
        }
        else
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

}


<?php 
define("SCRIPTNAME", "uploaderapplication.php");
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
    case "uploaderapplication":
        globalize("post", array( "computer_running_all_the_time" => "INT", "seedbox" => "INT", "speedtest" => "TRIM", "stuff" => "TRIM" ));
        if( is_member_of("unregistered") || has_permission("canupload_torrents") && !has_permission("canlogin_admincp") ) 
        {
            ajax_message(get_phrase("permission_denied"), "-ERROR-");
        }

        $search = $TSUE["TSUE_Database"]->query_result("SELECT application_state FROM tsue_uploader_applications WHERE memberid = " . $TSUE["TSUE_Member"]->info["memberid"]);
        if( $search ) 
        {
            ajax_message(get_phrase("no_dupe_up_app_form"), "-ERROR-");
        }

        if( !preg_match("#^http:\\/\\/www\\.speedtest\\.net\\/result\\/[0-9]+\\.png\$#", $speedtest) ) 
        {
            ajax_message(get_phrase("invalid_speedtest_url"), "-ERROR-");
        }

        if( !$stuff ) 
        {
            ajax_message(get_phrase("message_required_fields_error"), "-ERROR-");
        }

        $buildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "added" => TIMENOW, "application_state" => "pending", "computer_running_all_the_time" => $computer_running_all_the_time, "seedbox" => $seedbox, "speedtest" => $speedtest, "stuff" => $stuff );
        if( !$TSUE["TSUE_Database"]->insert("tsue_uploader_applications", $buildQuery) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        $searchMembergroups = searchPermissionInMembergroups("canmanage_applications");
        if( $searchMembergroups ) 
        {
            $moderators = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE membergroupid IN (" . implode(",", $searchMembergroups) . ")");
            if( $TSUE["TSUE_Database"]->num_rows($moderators) ) 
            {
                while( $moderator = $TSUE["TSUE_Database"]->fetch_Assoc($moderators) ) 
                {
                    alert_member($moderator["memberid"], $TSUE["TSUE_Member"]->info["memberid"], $TSUE["TSUE_Member"]->info["membername"], "applications", 0, "new-uploader-form");
                }
            }

        }

        ajax_message(get_phrase("uploader_application_has_been_sent"), "-DONE-");
}


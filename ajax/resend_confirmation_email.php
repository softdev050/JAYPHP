<?php 
define("SCRIPTNAME", "resend_confirmation_email.php");
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

if( $TSUE["action"] == "resend_confirmation_email" ) 
{
    if( !is_member_of("awaitingemailconfirmation") ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    $Check = $TSUE["TSUE_Database"]->query_result("SELECT membergroupid FROM tsue_member_confirmation WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND type = 'confirm_account'");
    if( $Check ) 
    {
        $OLDmembergroupid = $Check["membergroupid"];
    }
    else
    {
        $OLDmembergroupid = is_member_of("awaitingemailconfirmation", true);
    }

    $hash = generate_random_string(16);
    $confirm_link = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . "\">" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $TSUE["TSUE_Member"]->info["memberid"] . "</a>";
    $BuildQuery = array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "membergroupid" => $OLDmembergroupid, "type" => "confirm_account", "hash" => $hash, "date" => TIMENOW );
    if( !$TSUE["TSUE_Database"]->replace("tsue_member_confirmation", $BuildQuery) ) 
    {
        ajax_message(get_phrase("database_error"), "-ERROR-");
    }

    $subject = get_phrase("signup_finished_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
    $body = get_phrase("signup_finished_email_body", $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $confirm_link);
    if( sent_mail($TSUE["TSUE_Member"]->info["email"], $subject, $body, $TSUE["TSUE_Member"]->info["membername"]) ) 
    {
        ajax_message(get_phrase("signup_finished_email"), "-DONE-");
        return 1;
    }

    ajax_message(get_phrase("unable_to_send_email"), "-ERROR-");
}



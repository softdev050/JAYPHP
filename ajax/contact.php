<?php 
define("SCRIPTNAME", "contact.php");
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

if( $TSUE["action"] == "contact" ) 
{
    globalize("post", array( "membername" => "TRIM", "email" => "TRIM", "message" => "TRIM" ));
    if( !is_valid_string($membername) ) 
    {
        $Error[] = get_phrase("invalid_membername");
    }

    if( !is_valid_email($email) ) 
    {
        $Error[] = get_phrase("invalid_email");
    }

    $strlenOriginalText = strlenOriginalText($message);
    if( $strlenOriginalText < 3 ) 
    {
        $Error[] = get_phrase("valid_message_error");
    }

    if( !isset($Error) ) 
    {
        check_flood("contact");
        $subject = get_phrase("contact_title") . " - " . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"];
        $message = "Sender: " . $membername . " (" . $email . ")\r\n\t\tMessage:\r\n\t\t-------------------------------------------------------------------\r\n\t\t" . html_clean($message) . "\r\n\t\t-------------------------------------------------------------------\r\n\t\tMember IP: " . MEMBER_IP;
        if( sent_mail(getSetting("global_settings", "website_email"), $subject, $message, getSetting("global_settings", "website_title"), $email, $membername) ) 
        {
            ajax_message(get_phrase("contact_message_sent"), "-DONE-");
        }
        else
        {
            $Error[] = get_phrase("unable_to_send_email");
        }

    }

    ajax_message(implode("<br />", $Error), "-ERROR-");
}



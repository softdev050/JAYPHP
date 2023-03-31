<?php 
define("SCRIPTNAME", "forgot_password.php");
define("IS_AJAX", 1);
define("NO_SESSION_UPDATE", 1);
define("NO_PLUGIN", 1);
require("./../library/init/init.php");
if( !$TSUE["action"] || strtolower($_SERVER["REQUEST_METHOD"]) != "post" ) 
{
    ajax_message(get_phrase("permission_denied"), "-ERROR-");
}

if( $TSUE["action"] == "forgot_password" ) 
{
    if( !is_member_of("unregistered") ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    if( $TSUE["do"] == "form" ) 
    {
        $CAPTCHA = "";
        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["security_enable_captcha"] ) 
        {
            eval("\$CAPTCHA = \"" . $TSUE["TSUE_Template"]->LoadTemplate("captcha") . "\";");
        }

        eval("\$ForgotPassword = \"" . $TSUE["TSUE_Template"]->LoadTemplate("forgotpassword_ajax") . "\";");
        ajax_message($ForgotPassword, "", false, get_phrase("forgot_password"));
    }

    globalize("post", array( "forgotpassword_email" => "TRIM" ));
    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["security_enable_captcha"] ) 
    {
        globalize("post", array( "recaptcha_challenge_field" => "TRIM", "recaptcha_response_field" => "TRIM" ));
        require_once(REALPATH . "/library/classes/class_captcha.php");
        $verifyCaptcha = new TSUE_captcha();
        if( !$verifyCaptcha->verifyCaptcha($recaptcha_challenge_field, $recaptcha_response_field) ) 
        {
            $Error[] = get_phrase("captcha_invalid");
        }

    }

    if( !is_valid_email($forgotpassword_email) ) 
    {
        $Error[] = get_phrase("invalid_email");
    }

    if( !isset($Error) ) 
    {
        $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid, membername FROM tsue_members WHERE email = " . $TSUE["TSUE_Database"]->escape($forgotpassword_email));
        if( !$Member ) 
        {
            $Error[] = get_phrase("invalid_email");
        }
        else
        {
            $hash = generate_random_string(16);
            $confirm_link = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forgotpassword&hash=" . $hash . "&memberid=" . $Member["memberid"] . "\">" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forgotpassword&hash=" . $hash . "&memberid=" . $Member["memberid"] . "</a>";
            $BuildQuery = array( "memberid" => $Member["memberid"], "type" => "forgot_password", "hash" => $hash, "date" => TIMENOW );
            if( !$TSUE["TSUE_Database"]->replace("tsue_member_confirmation", $BuildQuery) ) 
            {
                $Error[] = get_phrase("database_error");
            }
            else
            {
                $subject = get_phrase("forgot_password_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                $body = get_phrase("forgot_password_email_body", $Member["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $confirm_link);
                if( sent_mail($forgotpassword_email, $subject, $body, $Member["membername"]) ) 
                {
                    ajax_message(get_phrase("forgot_password_email_sent"), "-DONE-");
                }
                else
                {
                    $TSUE["TSUE_Database"]->query("DELETE FROM tsue_member_confirmation WHERE memberid = " . $TSUE["TSUE_Database"]->escape($Member["memberid"]) . " AND type = 'forgot_password'");
                    $Error[] = get_phrase("unable_to_send_email");
                }

            }

        }

    }

    if( isset($Error) ) 
    {
        ajax_message(implode("<br />", $Error), "-ERROR-");
    }

}



<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "forgotpassword.php");
require("./library/init/init.php");
if( !is_member_of("unregistered") ) 
{
    show_error(get_phrase("permission_denied"));
}

globalize("get", array( "hash" => "TRIM", "memberid" => "INT" ));
$Page_Title = get_phrase("forgot_password");
if( strlen($hash) != 16 || !$memberid ) 
{
    $Error[] = get_phrase("forgot_password_could_not_rest");
}
else
{
    $Confirmation = $TSUE["TSUE_Database"]->query_result("SELECT c.memberid, c.hash, m.membername, m.email FROM tsue_member_confirmation c INNER JOIN tsue_members m USING(memberid) WHERE c.memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND c.type = 'forgot_password'");
    if( !$Confirmation ) 
    {
        $Error[] = get_phrase("forgot_password_could_not_rest");
    }
    else
    {
        if( $Confirmation["memberid"] != $memberid || $Confirmation["hash"] != $hash ) 
        {
            $Error[] = get_phrase("forgot_password_could_not_rest");
        }
        else
        {
            $TSUE["TSUE_Database"]->query("DELETE FROM tsue_member_confirmation WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND type = 'forgot_password'");
            $newpassword = generate_random_string(8);
            $subject = get_phrase("forgot_password_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
            $body = get_phrase("forgot_password_email_reset_body", $Confirmation["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $newpassword, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
            if( sent_mail($Confirmation["email"], $subject, $body, $Confirmation["membername"]) ) 
            {
                $BuildQuery = array( "password" => md5($newpassword), "password_date" => TIMENOW );
                if( $TSUE["TSUE_Database"]->update("tsue_members", $BuildQuery, "memberid = " . $TSUE["TSUE_Database"]->escape($memberid)) ) 
                {
                    show_done(get_phrase("forgot_pasword_has_been_reset"), $Page_Title);
                }
                else
                {
                    $Error[] = get_phrase("unable_to_send_email");
                }

            }
            else
            {
                $Error[] = get_phrase("unable_to_send_email");
            }

        }

    }

}

show_error($Error, $Page_Title);


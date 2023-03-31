<?php 
define("SCRIPTNAME", "login.php");
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

if( $TSUE["action"] == "login" ) 
{
    if( !is_member_of("unregistered") ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    $total_strikes = check_strikes();
    $limit_of_login_strikes = getSetting("global_settings", "limit_of_login_strikes", 5);
    if( $limit_of_login_strikes < $total_strikes ) 
    {
        ajax_message(get_phrase("used_login_failed"), "-ERROR-");
    }

    globalize("post", array( "loginbox_membername" => "TRIM", "loginbox_password" => "TRIM", "loginbox_remember" => "TRIM" ));
    if( !$loginbox_membername || !$loginbox_password ) 
    {
        $Error[] = get_phrase("invalid_membername_or_password");
    }
    else
    {
        if( !is_valid_membername($loginbox_membername) ) 
        {
            $Error[] = get_phrase("membername_match_regular_expression_error");
        }
        else
        {
            $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid, password FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($loginbox_membername));
            if( !$Member ) 
            {
                $Error[] = get_phrase("invalid_membername_or_password");
            }
            else
            {
                if( strlen($Member["password"]) === 32 && $Member["memberid"] && $Member["password"] === md5($loginbox_password) ) 
                {
                    setLoginCookie($loginbox_remember == "true", $Member["memberid"], $loginbox_password);
                    $TSUE["TSUE_Database"]->query("DELETE FROM tsue_session WHERE memberid = " . $TSUE["TSUE_Database"]->escape($Member["memberid"]) . " OR (memberid = 0 AND ipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP) . ")");
                    ajax_message(get_phrase("logged_in_successfully"), "-DONE-");
                }
                else
                {
                    log_strike($loginbox_membername, $Member["memberid"], $loginbox_password);
                    $Error[] = get_phrase("invalid_membername_or_password");
                }

            }

        }

    }

    if( isset($Error) ) 
    {
        save_strike();
        $Error[] = get_phrase("used_login_attempts_v2", $total_strikes + 1, $limit_of_login_strikes);
        ajax_message(implode("<br />", $Error), "-ERROR-");
    }

}



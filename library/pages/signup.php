<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "signup.php");
require("./library/init/init.php");
if( !is_member_of("unregistered") ) 
{
    redirect("?p=home&pid=1");
}

$Page_Title = get_phrase("signup_form");
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_signup") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=signup&amp;pid=" . PAGEID ));
$max_members_limit = getSetting("global_settings", "max_members_limit", 0);
if( $max_members_limit ) 
{
    $totalRegistered = $TSUE["TSUE_Database"]->row_count("SELECT memberid FROM tsue_members");
    if( $max_members_limit <= $totalRegistered ) 
    {
        show_error(get_phrase("max_members_limit_notice", $max_members_limit), $Page_Title);
    }

}

$website_allow_signup = getSetting("global_settings", "website_allow_signup");
if( !$website_allow_signup ) 
{
    show_error(get_phrase("signup_closed"), $Page_Title);
}

if( $website_allow_signup == "3" ) 
{
    show_error(get_phrase("free_registration_is_closed", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=26&amp;register=1"));
}

if( $TSUE["TSUE_Settings"]->settings["global_settings"]["prevent_multiple_account"] ) 
{
    $searchIP = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE ipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP));
    if( $searchIP ) 
    {
        show_error(get_phrase("prevent_multiple_account_registration_msg"), $Page_Title);
    }

}

$invite_hash = "";
$signupbox_email = "";
$inviteOnlySignup = $website_allow_signup == 2 || $website_allow_signup == 4;
globalize("get", array( "hash" => "TRIM" ));
if( $inviteOnlySignup || $hash ) 
{
    $countHash = strlen($hash);
    if( $countHash != 32 && $inviteOnlySignup ) 
    {
        show_error(get_phrase("invite_only_signup") . (($website_allow_signup == 4 ? "<br />" . get_phrase("free_registration_is_closed_2", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=26&amp;register=1") : "")), $Page_Title);
    }

    if( $countHash == 32 ) 
    {
        $Invite = $TSUE["TSUE_Database"]->query_result("SELECT status,email FROM tsue_invites WHERE hash = " . $TSUE["TSUE_Database"]->escape($hash));
        if( (!$Invite || $Invite["status"] != "pending") && $inviteOnlySignup ) 
        {
            show_error(get_phrase("signup_invite_invalid"), $Page_Title);
        }
        else
        {
            if( $Invite && $Invite["status"] == "pending" ) 
            {
                $invite_hash = $hash;
                $signupbox_email = ($Invite["email"] ? strip_tags($Invite["email"]) : "");
            }

        }

    }

}

globalize("get", array( "agree_terms_of_service_and_rules" => "TRIM" ));
if( getSetting("global_settings", "force_member_to_agree_to_the_terms_of_service_and_rules") && $agree_terms_of_service_and_rules != "yes" ) 
{
    $TSUE["TSUE_Language"]->phrase["terms_of_service_and_rules"] = nl2br($TSUE["TSUE_Language"]->phrase["terms_of_service_and_rules"]);
    eval("\$terms_of_service_and_rules = \"" . $TSUE["TSUE_Template"]->LoadTemplate("terms_of_service_and_rules") . "\";");
    PrintOutput($terms_of_service_and_rules, $Page_Title);
}

$TSUE["TSUE_Language"]->phrase["membername_tip"] = get_phrase("membername_tip", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"], $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_max_char"]);
$TSUE["TSUE_Language"]->phrase["memberpassword_tip"] = get_phrase("memberpassword_tip", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"]);
$CAPTCHA = "";
if( $TSUE["TSUE_Settings"]->settings["global_settings"]["security_enable_captcha"] ) 
{
    eval("\$CAPTCHA = \"" . $TSUE["TSUE_Template"]->LoadTemplate("captcha") . "\";");
}

$signup_through_facebook = "";
if( $TSUE["TSUE_Settings"]->settings["global_settings"]["facebook_app_id"] && $TSUE["TSUE_Settings"]->settings["global_settings"]["facebook_app_secret"] ) 
{
    eval("\$signup_through_facebook = \"" . $TSUE["TSUE_Template"]->LoadTemplate("signup_through_facebook") . "\";");
}

$signupbox_membername = "";
$genderChecked["n"] = " checked=\"checked\"";
$genderChecked["f"] = "";
$genderChecked["m"] = "";
$signupbox_date_of_birth = "";
$a_hash = "";
$get_timezones = "\r\n<select name=\"signupbox_timezone\" class=\"s\" id=\"signupbox_timezone\">";
foreach( fetch_timezones() as $optionvalue => $timezonephrase ) 
{
    $optionselected = ($optionvalue == getSetting("global_settings", "d_timezone") ? " selected=\"selected\"" : "");
    $get_timezones .= "\r\n\t<option value=\"" . $optionvalue . "\"" . $optionselected . ">" . $timezonephrase . "</option>";
}
$get_timezones .= "\r\n</select>";
if( $TSUE["do"] == "facebook" ) 
{
    require_once(REALPATH . "/library/classes/class_facebook.php");
    $Facebook = new Facebook();
    if( $Facebook->User ) 
    {
        $signupbox_membername = strip_tags((isset($Facebook->User->username) && $Facebook->User->username ? $Facebook->User->username : (isset($Facebook->User->first_name) && $Facebook->User->first_name ? $Facebook->User->first_name : "")));
        $signupbox_email = (isset($Facebook->User->email) && $Facebook->User->email ? strip_tags($Facebook->User->email) : "");
        if( isset($Facebook->User->gender) && $Facebook->User->gender ) 
        {
            switch( $Facebook->User->gender ) 
            {
                case "male":
                    $genderChecked["m"] = " checked=\"checked\"";
                    $genderChecked["n"] = "";
                    break;
                case "female":
                    $genderChecked["f"] = " checked=\"checked\"";
                    $genderChecked["n"] = "";
            }
        }

        if( isset($Facebook->User->birthday) && $Facebook->User->birthday && substr_count($Facebook->User->birthday, "/") == 2 ) 
        {
            list($month, $day, $year) = @tsue_explode("/", $Facebook->User->birthday);
            $signupbox_date_of_birth = $day . "/" . $month . "/" . $year;
        }

        $userName = (isset($Facebook->User->username) && $Facebook->User->username ? $Facebook->User->username : (isset($Facebook->User->id) && $Facebook->User->id ? $Facebook->User->id : ""));
        $profilePictureURL = "http://graph.facebook.com/" . $userName . "/picture?type=large";
        $profilePicture = file_get_contents($profilePictureURL);
        if( $profilePicture ) 
        {
            $imageType = "";
            if( strpos($profilePicture, "JFIF") !== false ) 
            {
                $imageType = ".jpg";
            }
            else
            {
                if( strpos($profilePicture, "GIF") !== false ) 
                {
                    $imageType = ".gif";
                }
                else
                {
                    if( strpos($profilePicture, "PNG") !== false ) 
                    {
                        $imageType = ".png";
                    }

                }

            }

            if( $imageType ) 
            {
                $a_hash = md5(MEMBER_IP . $userName . MEMBER_IP) . $imageType;
                file_put_contents(REALPATH . "data/cache/" . $a_hash, $profilePicture);
            }

        }

    }

}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("signup") . "\";");
PrintOutput($Output, $Page_Title);


<?php 
define("SCRIPTNAME", "signup.php");
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

if( $TSUE["action"] == "signup" ) 
{
    $website_allow_signup = getSetting("global_settings", "website_allow_signup");
    if( !is_member_of("unregistered") ) 
    {
        ajax_message(get_phrase("permission_denied"), "-ERROR-");
    }

    if( !$website_allow_signup ) 
    {
        ajax_message(get_phrase("signup_closed"), "-ERROR-");
    }

    if( $website_allow_signup == "3" ) 
    {
        ajax_message(get_phrase("free_registration_is_closed", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=26&amp;register=1"), "-ERROR-");
    }

    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["prevent_multiple_account"] ) 
    {
        $searchIP = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE ipaddress = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP));
        if( $searchIP ) 
        {
            ajax_message(get_phrase("prevent_multiple_account_registration_msg"), "-ERROR-");
        }

    }

    globalize("post", array( "signupbox_membername" => "TRIM", "signupbox_date_of_birth" => "TRIM", "signupbox_email" => "TRIM", "signupbox_email2" => "TRIM", "signupbox_password" => "TRIM", "signupbox_password2" => "TRIM", "signupbox_gender" => "TRIM", "signupbox_timezone" => "TRIM", "invite_hash" => "TRIM", "a_hash" => "TRIM" ));
    $inviteOnlySignup = $website_allow_signup == 2 || $website_allow_signup == 4;
    if( $inviteOnlySignup || $invite_hash ) 
    {
        $countHash = strlen($invite_hash);
        if( $countHash != 32 && $inviteOnlySignup ) 
        {
            ajax_message(get_phrase("invite_only_signup") . (($website_allow_signup == 4 ? "<br />" . get_phrase("free_registration_is_closed_2", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=upgrade&amp;pid=26&amp;register=1") : "")), "-ERROR-");
        }

        if( $countHash == 32 ) 
        {
            $Invite = $TSUE["TSUE_Database"]->query_result("SELECT status FROM tsue_invites WHERE hash = " . $TSUE["TSUE_Database"]->escape($invite_hash));
            if( (!$Invite || $Invite["status"] != "pending") && $inviteOnlySignup ) 
            {
                ajax_message(get_phrase("signup_invite_invalid"), "-ERROR-");
            }
            else
            {
                if( $Invite && $Invite["status"] == "pending" ) 
                {
                    $UseInviteSystem = true;
                }

            }

        }

    }

    if( !$signupbox_membername ) 
    {
        $Error[] = get_phrase("invalid_membername");
    }
    else
    {
        if( !is_valid_membername($signupbox_membername) ) 
        {
            $Error[] = get_phrase("membername_match_regular_expression_error");
        }
        else
        {
            if( strlen($signupbox_membername) < $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"] ) 
            {
                $Error[] = get_phrase("invalid_membername_min_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_min_char"]);
            }
            else
            {
                if( $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_max_char"] < strlen($signupbox_membername) ) 
                {
                    $Error[] = get_phrase("invalid_membername_max_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_max_char"]);
                }
                else
                {
                    $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($signupbox_membername));
                    if( $Member ) 
                    {
                        $Error[] = get_phrase("invalid_membername_in_use");
                    }
                    else
                    {
                        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_illegal_member_names"] ) 
                        {
                            $member_name_illegal_member_names = preg_split("/\\r?\\n/", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_name_illegal_member_names"], -1, PREG_SPLIT_NO_EMPTY);
                            if( $member_name_illegal_member_names ) 
                            {
                                foreach( $member_name_illegal_member_names as $name ) 
                                {
                                    $name = trim($name);
                                    if( $name === "" ) 
                                    {
                                        continue;
                                    }

                                    if( stripos($signupbox_membername, $name) !== false ) 
                                    {
                                        $Error[] = get_phrase("invalid_membername_in_use");
                                    }

                                }
                            }

                        }

                    }

                }

            }

        }

    }

    if( !$signupbox_date_of_birth || substr_count($signupbox_date_of_birth, "/") != 2 || strlen($signupbox_date_of_birth) != 10 ) 
    {
        $Error[] = get_phrase("invalid_date_of_birth");
    }
    else
    {
        $nowYear = date("Y");
        list($day, $month, $year) = @tsue_explode("/", $signupbox_date_of_birth);
        if( !checkdate($month, $day, $year) || $nowYear <= $year ) 
        {
            $Error[] = get_phrase("invalid_date_of_birth");
        }
        else
        {
            if( $nowYear - $year <= 5 ) 
            {
                ajax_message(get_phrase("you_must_meet_certain_age_requirements", getSetting("global_settings", "website_title")), "-ERROR-");
            }

        }

    }

    if( !is_valid_email($signupbox_email) || !is_valid_email($signupbox_email2) ) 
    {
        $Error[] = get_phrase("invalid_email");
    }
    else
    {
        if( $signupbox_email != $signupbox_email2 ) 
        {
            $Error[] = get_phrase("invalid_email_mismatch");
        }
        else
        {
            $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE email = " . $TSUE["TSUE_Database"]->escape($signupbox_email));
            if( $Member ) 
            {
                $Error[] = get_phrase("invalid_email_in_use");
            }

        }

    }

    if( !$signupbox_password || !$signupbox_password2 ) 
    {
        $Error[] = get_phrase("invalid_memberpassword");
    }
    else
    {
        if( $signupbox_password != $signupbox_password2 ) 
        {
            $Error[] = get_phrase("invalid_memberpassword_mismatch");
        }
        else
        {
            if( strlen($signupbox_password) < $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"] ) 
            {
                $Error[] = get_phrase("invalid_memberpassword_min_char", $TSUE["TSUE_Settings"]->settings["global_settings"]["member_password_min_char"]);
            }
            else
            {
                if( $signupbox_password == $signupbox_membername ) 
                {
                    $Error[] = get_phrase("invalid_memberpassword_same_as_name");
                }

            }

        }

    }

    $signupbox_gender = (!in_array($signupbox_gender, array( "f", "m" )) ? "" : $signupbox_gender);
    $AvailableTimeZones = fetch_timezones();
    $signupbox_timezone = (isset($AvailableTimeZones[$signupbox_timezone]) && !empty($AvailableTimeZones[$signupbox_timezone]) ? $signupbox_timezone : getSetting("global_settings", "d_timezone"));
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

    if( isset($Error) ) 
    {
        ajax_message(implode("<br />", $Error), "-ERROR-");
        return 1;
    }

    switch( strtolower($TSUE["TSUE_Settings"]->settings["global_settings"]["website_new_signup_verification"]) ) 
    {
        case 0:
            $membergroupid = is_member_of("awaitingemailconfirmation", true);
            $Message = get_phrase("signup_finished_email");
            $SendConfirmationEmail = true;
            break;
        case 1:
            $membergroupid = is_member_of("awaitingmoderation", true);
            $Message = get_phrase("signup_finished_admin");
            break;
        case 2:
            $membergroupid = is_member_of("registeredusers", true);
            $Message = get_phrase("signup_finished_automatic");
    }
    $passkey = generatePasskey();
    $BuildQuery = array( "membergroupid" => $membergroupid, "membername" => $signupbox_membername, "password" => md5($signupbox_password), "password_date" => TIMENOW, "passkey" => $passkey, "email" => $signupbox_email, "themeid" => $TSUE["TSUE_Settings"]->settings["global_settings"]["d_themeid"], "languageid" => $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"], "joindate" => TIMENOW, "timezone" => (string) $signupbox_timezone, "ipaddress" => MEMBER_IP, "gender" => $signupbox_gender );
    if( !$TSUE["TSUE_Database"]->insert("tsue_members", $BuildQuery) ) 
    {
        ajax_message(get_phrase("database_error"), "-ERROR-");
        return 1;
    }

    deleteCache("TSUEPlugin_newestMembers_");
    $memberid = $TSUE["TSUE_Database"]->insert_id();
    if( !$memberid ) 
    {
        ajax_message(get_phrase("database_error"), "-ERROR-");
    }

    if( $a_hash && file_exists(REALPATH . "data/cache/" . $a_hash) ) 
    {
        $NewAvatar = REALPATH . "data/cache/" . $a_hash;
        $AvatarPath = REALPATH . "data/avatars/";
        $imageInfo = getimagesize($NewAvatar);
        list($width, $height) = $imageInfo;
        $_AllowedImages = array( "jpg", "jpeg", "gif", "png" );
        if( !(!$imageInfo || !$width || !$height || 2 * $height < $width || 2 * $width < $height) && in_array(file_extension($NewAvatar), $_AllowedImages) ) 
        {
            require_once(REALPATH . "/library/functions/functions_memberAvatar.php");
            prepareAvatar($NewAvatar, $AvatarPath, $memberid, $width, $height);
        }

        @unlink($NewAvatar);
    }

    $BuildQuery = array( "memberid" => $memberid );
    $TSUE["TSUE_Database"]->replace("tsue_member_privacy", $BuildQuery);
    $BuildQuery = array( "memberid" => $memberid, "date_of_birth" => $signupbox_date_of_birth, "signature" => "", "country" => "", "custom_title" => "", "uploaded" => ($TSUE["TSUE_Settings"]->settings["global_settings"]["default_uploaded"] ? $TSUE["TSUE_Settings"]->settings["global_settings"]["default_uploaded"] * 1024 * 1024 * 1024 : 0), "points" => ($TSUE["TSUE_Settings"]->settings["global_settings"]["default_points"] ? 0 + $TSUE["TSUE_Settings"]->settings["global_settings"]["default_points"] : 0), "invites_left" => ($TSUE["TSUE_Settings"]->settings["global_settings"]["default_invites"] ? intval($TSUE["TSUE_Settings"]->settings["global_settings"]["default_invites"]) : 0), "torrent_pass" => substr($passkey, 0, 32) );
    $TSUE["TSUE_Database"]->replace("tsue_member_profile", $BuildQuery);
    setLoginCookie(false, $memberid, $signupbox_password);
    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["send_welcome_pm"] ) 
    {
        sendPM(get_phrase("new_member_welcome_pm_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]), $TSUE["TSUE_Settings"]->settings["global_settings"]["send_welcome_pm_owner"], $memberid, nl2br(get_phrase("new_member_welcome_pm_body", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"])), true);
    }

    if( isset($UseInviteSystem) && $UseInviteSystem === true && strlen($invite_hash) === 32 ) 
    {
        $BuildQuery = array( "receiver_memberid" => $memberid, "receive_date" => TIMENOW, "email" => $signupbox_email, "name" => $signupbox_membername, "status" => "completed" );
        $TSUE["TSUE_Database"]->update("tsue_invites", $BuildQuery, "hash=" . $TSUE["TSUE_Database"]->escape($invite_hash));
    }

    if( isset($SendConfirmationEmail) ) 
    {
        $hash = generate_random_string(16);
        $confirm_link = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $memberid . "\">" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=confirmaccount&hash=" . $hash . "&memberid=" . $memberid . "</a>";
        $BuildQuery = array( "memberid" => $memberid, "type" => "confirm_account", "hash" => $hash, "date" => TIMENOW );
        if( !$TSUE["TSUE_Database"]->replace("tsue_member_confirmation", $BuildQuery) ) 
        {
            ajax_message(get_phrase("database_error"), "-ERROR-");
        }

        $subject = get_phrase("signup_finished_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
        $body = get_phrase("signup_finished_email_body", $signupbox_membername, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $confirm_link);
        if( sent_mail($signupbox_email, $subject, $body, $signupbox_membername) ) 
        {
            ajax_message($Message, "-DONE-");
        }
        else
        {
            ajax_message(get_phrase("unable_to_send_email"), "-ERROR-");
        }

    }

    shoutboxAnnouncement(array( "new_member", $memberid, $signupbox_membername ));
    ircAnnouncement("new_member", $memberid, $signupbox_membername);
    ajax_message($Message, "-DONE-");
}



<?php 
define("SCRIPTNAME", "payment_gateway.php");
define("NO_PLUGIN", 1);
define("IN_INDEX", 1);
require("./library/init/init.php");
globalize("get", array( "method" => "TRIM" ));
if( $method && ctype_alpha($method) ) 
{
    $api = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_member_upgrades_api WHERE active = 1 AND classname = " . $TSUE["TSUE_Database"]->escape($method));
    if( $api && file_exists(REALPATH . "library/classes/class_" . $api["classname"] . ".php") ) 
    {
        $apiSettings = ($api["settings"] ? unserialize($api["settings"]) : array(  ));
        require_once(REALPATH . "library/classes/class_" . $api["classname"] . ".php");
        $apiClass = "TSUE_" . $api["classname"];
        $apiObj = new $apiClass($apiSettings);
        if( $apiObj->verifyPayment() ) 
        {
            $transaction = $TSUE["TSUE_Database"]->query_result("\r\n\t\t\t\tSELECT *\r\n\t\t\t\tFROM tsue_member_upgrades_transaction\r\n\t\t\t\tWHERE\r\n\t\t\t\tapi_id = " . $api["api_id"] . "\r\n\t\t\t\t\tAND\r\n\t\t\t\ttxn_id  = " . $TSUE["TSUE_Database"]->escape($apiObj->txn_id) . "\t\t\t\t\r\n\t\t\t");
            if( ($apiObj->type == 2 || !$transaction && $apiObj->type == 1) && $apiSettings["payment_inform_emails"] ) 
            {
                $emails = tsue_explode(",", $apiSettings["payment_inform_emails"]);
                if( $emails ) 
                {
                    if( !$apiObj->paymentinfo["memberid"] ) 
                    {
                        $membername = $memberlink = $apiObj->paymentinfo["payer_email"];
                    }
                    else
                    {
                        $membername = $apiObj->paymentinfo["membername"];
                        $memberlink = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $apiObj->paymentinfo["memberid"] . "\">" . $membername . "</a>";
                    }

                    $website_title = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"];
                    $item = $apiObj->paymentinfo["item_name"];
                    $amount = $apiObj->paymentinfo["amount"] . " " . strtoupper($apiObj->paymentinfo["currency"]);
                    $processor = $api["title"];
                    $transactionid = $apiObj->txn_id;
                    $payment_status = $apiObj->paymentinfo["payment_status"];
                    if( $apiObj->Upgrade["upgrade_length_type"] == "lifetime" ) 
                    {
                        $upgrade_length = "";
                        $upgrade_length_type = get_phrase("upgrade_lifetime");
                    }
                    else
                    {
                        $upgrade_length = $apiObj->Upgrade["upgrade_length"];
                        $upgrade_length_type = get_phrase("upgrade_" . $apiObj->Upgrade["upgrade_length_type"] . ((1 < $apiObj->Upgrade["upgrade_length"] ? "s" : "")));
                    }

                    $upradePrice = get_phrase("upgrade_price", $apiObj->Upgrade["upgrade_price"], strtoupper($apiObj->Upgrade["upgrade_currency"]), $upgrade_length, $upgrade_length_type);
                    $subject = get_phrase("upgrade_inform_admin_subject", $website_title);
                    $message = get_phrase("upgrade_inform_admin_message", $membername, $website_title, $memberlink, $item, $amount, $processor, $transactionid, $payment_status, $apiObj->Upgrade["upgrade_title"], $upradePrice);
                    foreach( $emails as $toEmail ) 
                    {
                        if( trim($toEmail) ) 
                        {
                            sent_mail($toEmail, $subject, $message, NULL);
                        }

                    }
                }

            }

            if( !$transaction ) 
            {
                $BuildQuery = array( "txn_id" => $apiObj->txn_id, "history_id" => $apiObj->paymentinfo["history_id"], "amount" => $apiObj->paymentinfo["amount"], "currency" => $apiObj->paymentinfo["currency"], "state" => $apiObj->type, "dateline" => TIMENOW, "api_id" => $api["api_id"], "full_log" => serialize($_POST) );
                $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_transaction", $BuildQuery);
                deleteCache(array( "TSUEPlugin_donate_", "TSUEPlugin_topDonors_" ));
                if( $apiObj->type == 1 ) 
                {
                    $TSUE["TSUE_Database"]->update("tsue_member_upgrades_purchases", array( "completed" => 1 ), "history_id=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["history_id"]));
                    $buildPromotion = array(  );
                    $buildPromotion["upgrade_id"] = $apiObj->paymentinfo["upgrade_id"];
                    $buildPromotion["history_id"] = $apiObj->paymentinfo["history_id"];
                    $buildPromotion["txn_id"] = $apiObj->txn_id;
                    $buildPromotion["start_date"] = TIMENOW;
                    if( $apiObj->Upgrade["upgrade_length"] && $apiObj->Upgrade["upgrade_length_type"] != "lifetime" ) 
                    {
                        $buildPromotion["expiry_date"] = strtotime("+" . $apiObj->Upgrade["upgrade_length"] . " " . $apiObj->Upgrade["upgrade_length_type"] . ((1 < $apiObj->Upgrade["upgrade_length"] ? "s" : "")));
                    }
                    else
                    {
                        $buildPromotion["expiry_date"] = 0;
                    }

                    if( !$apiObj->paymentinfo["memberid"] ) 
                    {
                        $passkey = generatePasskey();
                        $newpassword = generate_random_string(8);
                        $membername = preg_replace("#[^a-zA-Z0-9]#", "", ($apiObj->paymentinfo["last_name"] ? $apiObj->paymentinfo["last_name"] : $apiObj->paymentinfo["first_name"]));
                        $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($membername));
                        if( $Member ) 
                        {
                            $Count = 1;
                        }

                        while( $Member ) 
                        {
                            $membername .= $Count;
                            $Count++;
                            $Member = $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_members WHERE membername = " . $TSUE["TSUE_Database"]->escape($membername));
                        }
                        $BuildQuery = array( "membergroupid" => is_member_of("registeredusers", true), "membername" => $membername, "password" => md5($newpassword), "password_date" => TIMENOW, "passkey" => $passkey, "email" => strip_tags($apiObj->paymentinfo["payer_email"]), "themeid" => $TSUE["TSUE_Settings"]->settings["global_settings"]["d_themeid"], "languageid" => $TSUE["TSUE_Settings"]->settings["global_settings"]["d_languageid"], "joindate" => TIMENOW, "timezone" => (string) $TSUE["TSUE_Settings"]->settings["global_settings"]["d_timezone"] );
                        $TSUE["TSUE_Database"]->insert("tsue_members", $BuildQuery);
                        $apiObj->paymentinfo["memberid"] = $TSUE["TSUE_Database"]->insert_id();
                        $apiObj->paymentinfo["membergroupid"] = is_member_of("registeredusers", true);
                        $TSUE["TSUE_Database"]->update("tsue_member_upgrades_purchases", array( "memberid" => $apiObj->paymentinfo["memberid"] ), "history_id=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["history_id"]));
                        $TSUE["TSUE_Database"]->replace("tsue_member_privacy", array( "memberid" => $apiObj->paymentinfo["memberid"] ));
                        $BuildQuery = array( "memberid" => $apiObj->paymentinfo["memberid"], "date_of_birth" => "", "signature" => "", "country" => "", "custom_title" => "", "uploaded" => 0, "points" => 0, "invites_left" => 0, "torrent_pass" => substr($passkey, 0, 32) );
                        $TSUE["TSUE_Database"]->replace("tsue_member_profile", $BuildQuery);
                        $subject = get_phrase("donate_to_signup_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                        $body = get_phrase("donate_to_signup_email_body", $apiObj->paymentinfo["first_name"] . " " . $apiObj->paymentinfo["last_name"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $membername, $newpassword, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&pid=1");
                        sent_mail($apiObj->paymentinfo["payer_email"], $subject, $body, $membername);
                    }

                    $buildPromotion["memberid"] = $apiObj->paymentinfo["memberid"];
                    if( $apiObj->Upgrade["upgrade_membergroupid"] ) 
                    {
                        $previousUpgrade = $TSUE["TSUE_Database"]->query_result("SELECT old_membergroupid FROM tsue_member_upgrades_promotions WHERE memberid = " . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["memberid"]) . " AND (expiry_date = 0 OR expiry_date > " . TIMENOW . ") AND active = 1 ORDER BY start_date ASC LIMIT 1");
                        if( $previousUpgrade ) 
                        {
                            $buildPromotion["old_membergroupid"] = $previousUpgrade["old_membergroupid"];
                        }
                        else
                        {
                            $buildPromotion["old_membergroupid"] = $apiObj->paymentinfo["membergroupid"];
                        }

                    }

                    $buildPromotion["active"] = 1;
                    $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_promotions", $buildPromotion);
                    $updateAccount = array(  );
                    $profileUpdate = array(  );
                    $updateAccount["inactivitytag"] = "0";
                    $profileUpdate["muted"] = "0";
                    $profileUpdate["hitRuns"] = "0";
                    $TSUE["TSUE_Database"]->delete("tsue_auto_warning", "memberid=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["memberid"]));
                    $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 0, "total_uploaded" => array( "escape" => 0, "value" => "IF(total_uploaded<total_downloaded,total_downloaded,total_uploaded)" ) ), "memberid=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["memberid"]));
                    $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 0, "uploaded" => array( "escape" => 0, "value" => "IF(uploaded<downloaded,downloaded,uploaded)" ) ), "uid=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["memberid"]));
                    if( $apiObj->Upgrade["upgrade_membergroupid"] ) 
                    {
                        $updateAccount["membergroupid"] = $apiObj->Upgrade["upgrade_membergroupid"];
                    }

                    if( $apiObj->Upgrade["upgrade_points"] ) 
                    {
                        $profileUpdate["points"] = array( "escape" => 0, "value" => "points+" . $apiObj->Upgrade["upgrade_points"] );
                    }

                    if( $apiObj->Upgrade["upgrade_invites"] ) 
                    {
                        $profileUpdate["invites_left"] = array( "escape" => 0, "value" => "invites_left+" . $apiObj->Upgrade["upgrade_invites"] );
                    }

                    if( $apiObj->Upgrade["upgrade_upload"] ) 
                    {
                        $profileUpdate["uploaded"] = array( "escape" => 0, "value" => "uploaded+" . $apiObj->Upgrade["upgrade_upload"] * 1024 * 1024 * 1024 );
                    }

                    if( $updateAccount ) 
                    {
                        $TSUE["TSUE_Database"]->update("tsue_members", $updateAccount, "memberid=" . $TSUE["TSUE_Database"]->escape($apiObj->paymentinfo["memberid"]));
                    }

                    if( $profileUpdate ) 
                    {
                        profileUpdate($apiObj->paymentinfo["memberid"], $profileUpdate);
                    }

                    $subject = get_phrase("upgrade_thank_you_subject");
                    $message = nl2br(get_phrase("upgrade_thank_you_message", $membername, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]));
                    sendPM($subject, $apiSettings["payment_pm_owner"], $apiObj->paymentinfo["memberid"], $message);
                    return 1;
                }

            }

        }
        else
        {
            $BuildQuery = array( "api_id" => $api["api_id"], "state" => 0, "dateline" => TIMENOW, "full_log" => $apiObj->error . " " . $apiObj->error_code );
            $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_transaction", $BuildQuery);
        }

    }

}



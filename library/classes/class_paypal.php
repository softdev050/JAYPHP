<?php 

class TSUE_paypal
{
    public $paymentinfo = NULL;
    public $Upgrade = NULL;
    public $txn_id = NULL;
    public $error_code = NULL;
    public $error = NULL;
    public $type = 0;
    public $paypalURL = "";
    public $apiSettings = "";

    public function TSUE_paypal($apiSettings)
    {
        $this->apiSettings = $apiSettings;
        $this->paypalURL = "https://" . $this->getPaypalHost() . "/cgi-bin/webscr";
    }

    public function verifyPayment()
    {
        global $TSUE;
        $query = array(  );
        $query[] = "cmd=_notify-validate";
        foreach( $_POST as $key => $val ) 
        {
            $query[] = $key . "=" . urlencode($val);
        }
        $query = implode("&", $query);
        if( function_exists("curl_init") && ($ch = curl_init()) ) 
        {
            curl_setopt($ch, CURLOPT_URL, $this->paypalURL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, "TSUE via cURL/PHP");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            curl_close($ch);
        }

        $test_ipn = $this->getPostValue("test_ipn");
        $item_name = $this->getPostValue("item_name");
        $item_number = $this->getPostValue("item_number");
        $payment_status = $this->getPostValue("payment_status");
        $payment_amount = $this->getPostValue("mc_gross");
        $payment_currency = $this->getPostValue("mc_currency");
        $txn_id = $this->getPostValue("txn_id");
        $txn_type = $this->getPostValue("txn_type");
        $business = strtolower($this->getPostValue("business"));
        $receiver_email = strtolower($this->getPostValue("receiver_email"));
        $tax = $this->getPostValue("tax");
        $custom = $this->getPostValue("custom");
        $payer_email = $this->getPostValue("payer_email");
        $first_name = $this->getPostValue("first_name");
        $last_name = $this->getPostValue("last_name");
        if( !isset($result) ) 
        {
            $this->error_code = "connection_failure";
            $this->error = "Connection to PayPal failed";
        }
        else
        {
            if( $business != strtolower($this->apiSettings["business"]) && $receiver_email != strtolower($this->apiSettings["business"]) ) 
            {
                $this->error_code = "business_failure";
                $this->error = "Invalid business (" . $business . " - " . $this->apiSettings["business"] . ") (" . $receiver_email . " - " . $this->apiSettings["business"] . ")";
            }
            else
            {
                if( !$txn_id ) 
                {
                    $this->error_code = "txnid_failure";
                    $this->error = "No txn_id";
                }
                else
                {
                    if( $result != "VERIFIED" ) 
                    {
                        $this->error_code = "authentication_failure";
                        $this->error = "Request not validated: (" . $result . ") (" . $this->paypalURL . ") -- " . $this->buildLog();
                    }
                    else
                    {
                        $this->txn_id = $txn_id;
                        $this->paymentinfo = $TSUE["TSUE_Database"]->query_result("\r\n\t\t\t\tSELECT p.*, m.membername, m.membergroupid\r\n\t\t\t\tFROM tsue_member_upgrades_purchases p\r\n\t\t\t\tLEFT JOIN tsue_members m USING(memberid)\r\n\t\t\t\tWHERE p.hash = " . $TSUE["TSUE_Database"]->escape($item_number) . "\r\n\t\t\t");
                        if( $this->paymentinfo ) 
                        {
                            $this->paymentinfo["currency"] = strtoupper($payment_currency);
                            $this->paymentinfo["amount"] = $payment_amount;
                            $this->paymentinfo["payment_status"] = $payment_status;
                            $this->paymentinfo["item_name"] = $item_name;
                            $this->paymentinfo["payer_email"] = $payer_email;
                            $this->paymentinfo["first_name"] = $first_name;
                            $this->paymentinfo["last_name"] = $last_name;
                            $this->Upgrade = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_member_upgrades WHERE upgrade_id = " . $this->paymentinfo["upgrade_id"]);
                            if( !$this->Upgrade ) 
                            {
                                $this->error_code = "invalid_item_name";
                                $this->error = "Item name does not exists in our database!";
                                $this->setResponseHeader("503 Service Unavailable");
                                return false;
                            }

                            $this->Upgrade["upgrade_currency"] = strtoupper($this->Upgrade["upgrade_currency"]);
                            if( 0 < $tax ) 
                            {
                                $this->paymentinfo["amount"] -= $tax;
                            }

                            if( in_array($txn_type, array( "web_accept", "subscr_payment" )) && $payment_status == "Completed" ) 
                            {
                                if( $this->paymentinfo["amount"] == $this->Upgrade["upgrade_price"] && $this->paymentinfo["currency"] == $this->Upgrade["upgrade_currency"] ) 
                                {
                                    $this->type = 1;
                                }
                                else
                                {
                                    $this->error_code = "invalid_payment_amount";
                                    $this->error = "Payment Amount or Currency does not match! Amount: " . $this->paymentinfo["amount"] . " " . $this->paymentinfo["currency"] . " -- Amount: " . $this->Upgrade["upgrade_price"] . " " . $this->Upgrade["upgrade_currency"] . " -- " . $this->buildLog();
                                }

                            }
                            else
                            {
                                if( $payment_status == "Reversed" || $payment_status == "Refunded" ) 
                                {
                                    $this->type = 2;
                                }
                                else
                                {
                                    $this->error_code = "unhandled_payment_status_or_type: " . $payment_status;
                                    $this->error = "Unknown Payment Status -- " . $this->buildLog();
                                }

                            }

                        }
                        else
                        {
                            $this->error_code = "invalid_item_number";
                            $this->error = "There is no purchase record for this item: " . $item_number;
                        }

                        $this->setResponseHeader("200 OK");
                        return 0 < $this->type;
                    }

                }

            }

        }

        $this->setResponseHeader("503 Service Unavailable");
        return false;
    }

    public function getPaypalHost()
    {
        if( $this->apiSettings["demo_mode"] ) 
        {
            return "www.sandbox.paypal.com";
        }

        return "www.paypal.com";
    }

    public function getPostValue($name)
    {
        return (isset($_POST[$name]) ? $_POST[$name] : NULL);
    }

    public function setResponseHeader($status_code)
    {
        header("HTTP/1.1 " . $status_code);
    }

    public function genareteForm($Upgrade, $hash, $custom)
    {
        global $TSUE;
        $Upgrade["upgrade_currency"] = strtoupper($Upgrade["upgrade_currency"]);
        $upgrade_pay_with = get_phrase("upgrade_pay_with", "PayPal");
        $upgrade_connecting_x = get_phrase("upgrade_connecting_x", "PayPal");
        if( !isset($TSUE["TSUE_Member"]->info["country"]) || !$TSUE["TSUE_Member"]->info["country"] ) 
        {
            $TSUE["TSUE_Member"]->info["country"] = "";
        }
        else
        {
            $TSUE["TSUE_Member"]->info["country"] = strtoupper($TSUE["TSUE_Member"]->info["country"]);
        }

        return "\r\n\t\t<form action=\"" . $this->paypalURL . "\" method=\"post\">\r\n\t\t\t<input type=\"hidden\" name=\"cmd\" value=\"_xclick\" />\r\n\t\t\t<input type=\"hidden\" name=\"business\" value=\"" . $this->apiSettings["business"] . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"item_name\" value=\"" . $Upgrade["upgrade_title"] . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"item_number\" value=\"" . $hash . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"currency_code\" value=\"" . $Upgrade["upgrade_currency"] . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"amount\" value=\"" . $Upgrade["upgrade_price"] . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"no_shipping\" value=\"1\" />\r\n\t\t\t<input type=\"hidden\" name=\"shipping\" value=\"0.00\" />\r\n\t\t\t<input type=\"hidden\" name=\"rm\" value=\"2\" />\r\n\t\t\t<input type=\"hidden\" name=\"cbt\" value=\"" . get_phrase("return_to_x", getSetting("global_settings", "website_title")) . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"return\" value=\"" . getSetting("global_settings", "website_url") . "/?p=upgrade&pid=26&thanks=1\" />\r\n\t\t\t<input type=\"hidden\" name=\"cancel_return\" value=\"" . getSetting("global_settings", "website_url") . "/?p=upgrade&pid=26\" />\r\n\t\t\t<input type=\"hidden\" name=\"notify_url\" value=\"" . getSetting("global_settings", "website_url") . "/payment_gateway.php?method=paypal\" />\r\n\t\t\t<input type=\"hidden\" name=\"custom\" value=\"" . $custom . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"no_note\" value=\"1\" />\r\n\t\t\t<input type=\"hidden\" name=\"tax\" value=\"0.00\" />\r\n\t\t\t<input type=\"hidden\" name=\"country\" value=\"" . $TSUE["TSUE_Member"]->info["country"] . "\" />\r\n\t\t\t<input type=\"hidden\" name=\"charset\" value=\"" . $TSUE["TSUE_Language"]->charset . "\" />\r\n\t\t\t<img src=\"" . getSetting("global_settings", "website_url") . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/payment_api/paypal.png\" alt=\"" . $upgrade_pay_with . "\" title=\"" . $upgrade_pay_with . "\" class=\"middle\" /> \r\n\t\t\t<input type=\"submit\" value=\"" . $upgrade_pay_with . "\" class=\"submit\" onclick=\"this.value='" . $upgrade_connecting_x . "'\" />\r\n\t\t</form>";
    }

    public function buildLog()
    {
        $Log = "";
        foreach( $_POST as $left => $right ) 
        {
            $Log .= htmlspecialchars($left) . " => " . htmlspecialchars($right) . "\n";
        }
        return $Log;
    }

}



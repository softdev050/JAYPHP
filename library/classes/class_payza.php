<?php 

class TSUE_payza
{
    public $paymentinfo = NULL;
    public $Upgrade = NULL;
    public $txn_id = NULL;
    public $error_code = NULL;
    public $error = NULL;
    public $type = 0;
    public $info = array(  );
    public $payzaURL = "";
    public $apiSettings = "";

    public function TSUE_payza($apiSettings)
    {
        $this->apiSettings = $apiSettings;
        $this->payzaURL = "https://secure.payza.com/ipn2.ashx";
    }

    public function verifyPayment()
    {
        global $TSUE;
        $query = "token=" . urlencode($this->getPostValue("token"));
        if( function_exists("curl_init") && ($ch = curl_init()) ) 
        {
            curl_setopt($ch, CURLOPT_URL, $this->payzaURL);
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

        if( isset($result) && 0 < strlen($result) && urldecode($result) != "INVALID TOKEN" ) 
        {
            $result = urldecode($result);
            $aps = explode("&", $result);
            foreach( $aps as $ap ) 
            {
                $ele = explode("=", $ap);
                $this->info[$ele["0"]] = $ele["1"];
            }
            $test_ipn = $this->info["ap_test"];
            $item_name = $this->info["ap_itemname"];
            $item_number = $this->info["ap_itemcode"];
            $payment_status = $this->info["ap_status"];
            $payment_amount = $this->info["ap_amount"];
            $payment_currency = $this->info["ap_currency"];
            $txn_id = $this->info["ap_referencenumber"];
            $txn_type = $this->info["ap_purchasetype"];
            $business = strtolower($this->info["ap_merchant"]);
            $tax = $this->info["ap_taxamount"];
            $custom = $this->info["apc_1"];
            $payer_email = $this->info["ap_custemailaddress"];
            $first_name = $this->info["ap_custfirstname"];
            $last_name = $this->info["ap_custlastname"];
        }
        else
        {
            $business = $txn_id = "";
        }

        if( !isset($result) ) 
        {
            $this->error_code = "connection_failure";
            $this->error = "Connection to Payza failed";
        }
        else
        {
            if( strlen($result) < 1 ) 
            {
                $this->error_code = "empty_result";
                $this->error = "something is wrong, no response is received from Payza";
            }
            else
            {
                if( urldecode($result) == "INVALID TOKEN" ) 
                {
                    $this->error_code = "invalid_token";
                    $this->error = "the token is not valid (" . $result . ")";
                }
                else
                {
                    if( $business != strtolower($this->apiSettings["business"]) ) 
                    {
                        $this->error_code = "business_failure";
                        $this->error = "Invalid business (" . $business . " - " . $this->apiSettings["business"] . ") -- " . $this->buildLog();
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
                            if( $payment_status != "Success" ) 
                            {
                                $this->error_code = "authentication_failure";
                                $this->error = "Request not validated: (" . $this->payzaURL . ") -- " . $this->buildLog();
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

                                    if( in_array($txn_type, array( "item", "service", "item-goods", "item-auction", "subscription" )) && $payment_status == "Success" ) 
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
                                        $this->error_code = "unhandled_payment_status_or_type: " . $payment_status;
                                        $this->error = "Unknown Payment Status -- " . $this->buildLog();
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

            }

        }

        $this->setResponseHeader("503 Service Unavailable");
        return false;
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
        $upgrade_pay_with = get_phrase("upgrade_pay_with", "Payza");
        $upgrade_connecting_x = get_phrase("upgrade_connecting_x", "Payza");
        $Upgrade["upgrade_description"] = substr(strip_tags($Upgrade["upgrade_description"]), 0, 150);
        eval("\$payza_form = \"" . $TSUE["TSUE_Template"]->LoadTemplate("payza_form") . "\";");
        return $payza_form;
    }

    public function buildLog()
    {
        $Log = "";
        foreach( $this->info as $left => $right ) 
        {
            $Log .= htmlspecialchars($left) . " => " . htmlspecialchars($right) . "\n";
        }
        return $Log;
    }

}



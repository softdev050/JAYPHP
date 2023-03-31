<?php 

class TSUE_coinbase
{
    public $paymentinfo = NULL;
    public $Upgrade = NULL;
    public $txn_id = NULL;
    public $error_code = NULL;
    public $error = NULL;
    public $type = 0;
    public $apiSettings = "";

    public function TSUE_coinbase($apiSettings)
    {
        $this->apiSettings = $apiSettings;
    }

    public function verifyPayment()
    {
        global $TSUE;
        $provided_secret = (isset($_GET["secret_key"]) ? trim($_GET["secret_key"]) : "");
        if( $provided_secret != $this->apiSettings["secret_key"] ) 
        {
            $this->error_code = "invalid_secret_key";
            $this->error = "The provieded secret key doesnt match.";
            header("HTTP/1.0 403 Forbidden");
            exit();
        }

        $jsonData = file_get_contents("php://input");
        $json = json_decode($jsonData, true);
        $order = $json["order"];
        $payment_status = $order["status"];
        $total_btc_cents = $order["total_btc"]["cents"];
        $total_btc_currency = $order["total_btc"]["currency_iso"];
        $total_native_cents = $order["total_native"]["cents"];
        $payment_currency = $order["total_native"]["currency_iso"];
        $hash = $order["custom"];
        $transaction_id = $order["transaction"]["id"];
        $transaction_hash = $order["transaction"]["hash"];
        $payment_amount = number_format($total_native_cents / 100, 2, ".", "");
        if( $payment_status == "completed" ) 
        {
            $this->txn_id = $transaction_id;
            $this->paymentinfo = $TSUE["TSUE_Database"]->query_result("\r\n\t\t\t\tSELECT p.*, m.membername, m.email, m.membergroupid\r\n\t\t\t\tFROM tsue_member_upgrades_purchases p\r\n\t\t\t\tLEFT JOIN tsue_members m USING(memberid)\r\n\t\t\t\tWHERE p.hash = " . $TSUE["TSUE_Database"]->escape($hash) . "\r\n\t\t\t");
            if( $this->paymentinfo ) 
            {
                $this->paymentinfo["currency"] = strtoupper($payment_currency);
                $this->paymentinfo["amount"] = $payment_amount;
                $this->paymentinfo["payment_status"] = $payment_status;
                $this->paymentinfo["payer_email"] = $this->paymentinfo["email"];
                $this->paymentinfo["first_name"] = $this->paymentinfo["membername"];
                $this->paymentinfo["last_name"] = "";
                $this->Upgrade = $TSUE["TSUE_Database"]->query_result("SELECT * FROM tsue_member_upgrades WHERE upgrade_id = " . $this->paymentinfo["upgrade_id"]);
                if( !$this->Upgrade ) 
                {
                    $this->error_code = "invalid_item_name";
                    $this->error = "Item name does not exists in our database!";
                    $this->setResponseHeader("503 Service Unavailable");
                    return false;
                }

                $this->paymentinfo["item_name"] = $this->Upgrade["upgrade_title"];
                $this->Upgrade["upgrade_currency"] = strtoupper($this->Upgrade["upgrade_currency"]);
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
                $this->error_code = "invalid_item_number";
                $this->error = "There is no purchase record for this item: " . $hash;
            }

            $this->setResponseHeader("200 OK");
            return 0 < $this->type;
        }

        $this->error_code = "invalid_status";
        $this->error = "Invalid payment status: " . $payment_status;
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
        $params = array( "api_key" => $this->apiSettings["api_key"], "name" => $Upgrade["upgrade_title"], "price_string" => $Upgrade["upgrade_price"], "price_currency_iso" => "USD", "type" => "buy_now", "description" => $Upgrade["upgrade_description"], "custom" => $hash, "callback_url" => getSetting("global_settings", "website_url") . "/payment_gateway.php?method=coinbase&secret_key=" . $this->apiSettings["secret_key"], "success_url" => getSetting("global_settings", "website_url") . "/?p=upgrade&pid=26&thanks=1", "cancel_url" => getSetting("global_settings", "website_url") . "/?p=upgrade&pid=26", "info_url" => "", "variable_price" => 0, "choose_price" => 0, "include_address" => 0, "include_email" => 0 );
        return $this->coinbase_button_request($params);
    }

    public function coinbase_button_request($params)
    {
        global $TSUE;
        $url = "https://coinbase.com/api/v1/buttons?api_key=" . $params["api_key"];
        $button_data = array( "button" => array( "name" => $params["name"], "price_string" => $params["price_string"], "price_currency_iso" => $params["price_currency_iso"], "type" => $params["type"], "description" => $params["description"], "custom" => $params["custom"], "callback_url" => $params["callback_url"], "success_url" => $params["success_url"], "cancel_url" => $params["cancel_url"], "info_url" => $params["info_url"], "variable_price" => $params["variable_price"], "choose_price" => $params["choose_price"], "include_address" => $params["include_address"], "include_email" => $params["include_email"] ) );
        $button_response = $this->coinbase_post_json($url, $button_data);
        $button = $button_response["button"];
        $upgrade_pay_with = get_phrase("upgrade_pay_with", "Coinbase");
        $code = "<img src=\"" . getSetting("global_settings", "website_url") . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/payment_api/coinbase.png\" alt=\"" . $upgrade_pay_with . "\" title=\"" . $upgrade_pay_with . "\" class=\"middle\" /> <a href=\"https://coinbase.com/checkouts/" . $button["code"] . "?c=" . urlencode($button["custom"]) . "\" target=\"_blank\" class=\"submit\">" . $upgrade_pay_with . "</a>";
        return $code;
    }

    public function coinbase_post_json($url, $button_data = array(  ))
    {
        global $TSUE;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($button_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "TSUE via cURL/PHP");
        $response_data = curl_exec($ch);
        if( curl_error($ch) ) 
        {
            exit( "Connection Error: " . curl_errno($ch) . " - " . curl_error($ch) );
        }

        curl_close($ch);
        $button_response = json_decode($response_data, true);
        if( isset($button_response["success"]) && $button_response["success"] == "true" ) 
        {
            return $button_response;
        }

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



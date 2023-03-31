<?php 

class TSUE_captcha
{
    private $apiURL = "http://www.google.com/recaptcha/api/verify";
    private $privatekey = "6LdWBMgSAAAAAO4IocJ07e2s4F5Sejpd0MZk2xrO";
    public $status = false;
    public $error = "";

    public function verifyCaptcha($recaptcha_challenge_field = "", $recaptcha_response_field = "")
    {
        $Query = http_build_query(array( "privatekey" => $this->privatekey, "remoteip" => MEMBER_IP, "challenge" => $recaptcha_challenge_field, "response" => $recaptcha_response_field ));
        if( function_exists("curl_init") && ($ch = curl_init()) ) 
        {
            curl_setopt($ch, CURLOPT_URL, $this->apiURL);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "TSUE via cURL/PHP");
            $result = curl_exec($ch);
            curl_close($ch);
            if( $result ) 
            {
                $result = preg_split("/\\r\\n|\\r|\\n/", $result);
                if( trim($result["0"]) == "true" ) 
                {
                    $this->status = true;
                }
                else
                {
                    $this->error = trim($result["1"]);
                }

            }

        }

        return $this->status;
    }

}



<?php 

class Facebook
{
    private $siteURL = "";
    private $accessToken = "";
    public $User = false;

    public function __construct()
    {
        global $TSUE;
        $this->siteURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=signup&pid=16&do=facebook&agree_terms_of_service_and_rules=yes";
        if( isset($_GET["state"]) && isset($_GET["code"]) ) 
        {
            if( !isValidToken($_GET["state"], false) ) 
            {
                exit( "Invalid State!" );
            }

            $this->checkState();
        }

        if( !$this->accessToken ) 
        {
            $this->Login();
        }

        if( $this->accessToken ) 
        {
            $this->userData();
        }

    }

    public function checkState()
    {
        global $TSUE;
        $redirect_uri = "https://graph.facebook.com/oauth/access_token?client_id=" . $TSUE["TSUE_Settings"]->settings["global_settings"]["facebook_app_id"] . "&redirect_uri=" . urlencode($this->siteURL) . "&client_secret=" . $TSUE["TSUE_Settings"]->settings["global_settings"]["facebook_app_secret"] . "&code=" . $_GET["code"];
        $this->accessToken = $this->connect($redirect_uri);
    }

    public function Login()
    {
        global $TSUE;
        header("Location: https://www.facebook.com/dialog/oauth?client_id=" . $TSUE["TSUE_Settings"]->settings["global_settings"]["facebook_app_id"] . "&redirect_uri=" . urlencode($this->siteURL) . "&state=" . $TSUE["TSUE_Member"]->info["csrf_token_page"] . "&scope=email,publish_stream,user_birthday,user_status,user_website,user_location");
        exit();
    }

    public function userData()
    {
        $graph_url = "https://graph.facebook.com/me?" . $this->accessToken;
        $this->User = json_decode($this->connect($graph_url));
    }

    public function showUser()
    {
        $picture = "http://graph.facebook.com/" . ((isset($this->User->username) && $this->User->username ? $this->User->username : $this->User->id)) . "/picture?type=large";
        echo "\r\n\t\t<table cellpadding=\"3\" cellspacing=\"0\">";
        foreach( $this->User as $item => $value ) 
        {
            if( !is_object($value) ) 
            {
                echo "\r\n\t\t\t\t<tr>\r\n\t\t\t\t\t<td>" . $item . "</td>\r\n\t\t\t\t\t<td>" . $value . "</td>\r\n\t\t\t\t</tr>";
            }

        }
        echo "\r\n\t\t<tr>\r\n\t\t\t<td colspan=\"2\">\r\n\t\t\t\t<img src=\"" . $picture . "\" alt=\"\" title=\"\" />\r\n\t\t\t</td>\r\n\t\t</tr>\r\n\t\t</table>";
    }

    public function connect($URL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}



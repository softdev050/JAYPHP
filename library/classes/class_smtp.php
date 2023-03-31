<?php 

class TSUESMTP
{
    public $smtpSocket = NULL;
    public $smtpReturn = 0;
    public $secure = "";
    public $toemail = "";
    public $toname = "";
    public $subject = "";
    public $message = "";
    public $headers = "";
    public $fromemail = "";
    public $delimiter = "\r\n";
    public $Settings = array(  );

    public function TSUESMTP()
    {
        global $TSUE;
        $this->secure = ($TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_secure"] == 1 ? "tls" : "none");
    }

    public function start($toemail, $toname = "", $subject, $message, $from, $fromname)
    {
        global $TSUE;
        $toemail = $this->fetch_first_line($toemail);
        if( empty($toemail) ) 
        {
            return false;
        }

        $delimiter =& $this->delimiter;
        $toemail = $this->dounhtmlspecialchars($toemail);
        $subject = $this->fetch_first_line($subject);
        $message = preg_replace("#(\r\n|\r|\n)#s", $delimiter, trim($message));
        if( (strtolower($TSUE["TSUE_Language"]->charset) == "iso-8859-1" || $TSUE["TSUE_Language"]->charset == "") && preg_match("/&[a-z0-9#]+;/i", $message) ) 
        {
            $message = utf8_encode($message);
            $subject = utf8_encode($subject);
            $encoding = "UTF-8";
            $unicode_decode = true;
        }
        else
        {
            $encoding = $TSUE["TSUE_Language"]->charset;
            $unicode_decode = false;
        }

        $message = $this->dounhtmlspecialchars($message, $unicode_decode);
        $subject = $this->encode_email_header($this->dounhtmlspecialchars($subject, $unicode_decode), $encoding, false, false);
        $from = $this->fetch_first_line($from);
        $mailfromname = ($fromname ? $this->fetch_first_line($fromname) : $from);
        if( $unicode_decode == true ) 
        {
            $mailfromname = utf8_encode($mailfromname);
        }

        $mailfromname = $this->encode_email_header($this->dounhtmlspecialchars($mailfromname, $unicode_decode), $encoding);
        if( !isset($headers) ) 
        {
            $headers = "";
        }

        $headers .= "From: " . $mailfromname . " <" . $from . ">" . $delimiter;
        $headers .= "Return-Path: " . $from . $delimiter;
        $headers .= "Message-ID: <" . gmdate("YmdHis") . "." . substr(md5($message . microtime()), 0, 12) . "@" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . ">" . $delimiter;
        $headers .= "MIME-Version: 1.0" . $delimiter;
        $headers .= "Content-Type: text/html" . (($encoding ? "; charset=\"" . $encoding . "\"" : "")) . $delimiter;
        $headers .= "Content-Transfer-Encoding: 8bit" . $delimiter;
        $headers .= "X-Priority: 3" . $delimiter;
        $headers .= "X-Mailer: TSUE Mail via PHP" . $delimiter;
        $headers .= "X-Sender: TSUE PHP-Mailer" . $delimiter;
        $headers .= "Date: " . date("r") . $delimiter;
        $this->toemail = $toemail;
        $this->toname = $toname;
        $this->subject = $subject;
        $this->message = $message;
        $this->headers = $headers;
        $this->fromemail = $from;
    }

    public function sendMessage($msg, $expectedResult = false)
    {
        if( $msg !== false && !empty($msg) ) 
        {
            fputs($this->smtpSocket, $msg . "\r\n");
        }

        if( $expectedResult !== false ) 
        {
            $result = "";
            while( $line = @fgets($this->smtpSocket, 1024) ) 
            {
                $result .= $line;
                if( preg_match("#^(\\d{3}) #", $line, $matches) ) 
                {
                    break;
                }

            }
            $this->smtpReturn = intval((isset($matches["1"]) ? $matches["1"] : 0));
            return $this->smtpReturn == $expectedResult;
        }

        return true;
    }

    public function errorMessage($msg)
    {
        global $TSUE;
        if( $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_debug"] == "1" ) 
        {
            trigger_error($msg, 512);
        }

        return false;
    }

    public function sendHello()
    {
        global $TSUE;
        if( !$this->smtpSocket ) 
        {
            return false;
        }

        if( !$this->sendMessage("EHLO " . $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_host"], 250) && !$this->sendMessage("HELO " . $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_host"], 250) ) 
        {
            return false;
        }

        return true;
    }

    public function send()
    {
        global $TSUE;
        if( !$this->toemail ) 
        {
            return false;
        }

        $this->smtpSocket = fsockopen((($this->secure == "ssl" ? "ssl://" : "tcp://")) . $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_host"], $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_port"], $errno, $errstr, 30);
        if( $this->smtpSocket ) 
        {
            if( !$this->sendMessage(false, 220) ) 
            {
                return $this->errorMessage($this->smtpReturn . " Unexpected response when connecting to SMTP server");
            }

            if( !$this->sendHello() ) 
            {
                return $this->errorMessage($this->smtpReturn . " Unexpected response from SMTP server during handshake");
            }

            if( $this->secure == "tls" && function_exists("stream_socket_enable_crypto") ) 
            {
                if( $this->sendMessage("STARTTLS", 220) && !stream_socket_enable_crypto($this->smtpSocket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) ) 
                {
                    return $this->errorMessage("Unable to negotitate TLS handshake.");
                }

                $this->sendHello();
            }

            if( $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_user"] && $TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_pass"] && $this->sendMessage("AUTH LOGIN", 334) && (!$this->sendMessage(base64_encode($TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_user"]), 334) || !$this->sendMessage(base64_encode($TSUE["TSUE_Settings"]->settings["global_settings"]["smtp_pass"]), 235)) ) 
            {
                return $this->errorMessage($this->smtpReturn . " Authorization to the SMTP server failed");
            }

            if( !$this->sendMessage("MAIL FROM:<" . $this->fromemail . ">", 250) ) 
            {
                return $this->errorMessage($this->smtpReturn . " Unexpected response from SMTP server during FROM address transmission");
            }

            $addresses = tsue_explode(",", $this->toemail);
            foreach( $addresses as $address ) 
            {
                if( !$this->sendMessage("RCPT TO:<" . trim($address) . ">", 250) ) 
                {
                    return $this->errorMessage($this->smtpReturn . " Unexpected response from SMTP server during TO address transmission");
                }

            }
            if( $this->sendMessage("DATA", 354) ) 
            {
                $this->sendMessage("Date: " . gmdate("r"), false);
                $this->sendMessage("To: " . $this->toemail, false);
                $this->sendMessage(trim($this->headers), false);
                $this->sendMessage("Subject: " . $this->subject, false);
                $this->sendMessage("\r\n", false);
                $this->message = preg_replace("#^\\." . $this->delimiter . "#m", ".." . $this->delimiter, $this->message);
                $this->sendMessage($this->message, false);
                if( !$this->sendMessage(".", 250) ) 
                {
                    return $this->errorMessage($this->smtpReturn . " Unexpected response from SMTP server when ending transmission");
                }

                $this->sendMessage("QUIT", 221);
                fclose($this->smtpSocket);
                return true;
            }

            return $this->errorMessage($this->smtpReturn . " Unexpected response from SMTP server during data transmission");
        }
        else
        {
            return $this->errorMessage("Unable to connect to SMTP server");
        }

    }

    public function fetch_first_line($text)
    {
        $text = preg_replace("/(\r\n|\r|\n)/s", "\r\n", trim($text));
        $pos = strpos($text, "\r\n");
        if( $pos !== false ) 
        {
            return substr($text, 0, $pos);
        }

        return $text;
    }

    public function dounhtmlspecialchars($text, $doUniCode = false)
    {
        if( $doUniCode ) 
        {
            $text = preg_replace("/&#([0-9]+);/esiU", "convert_int_to_utf8('\\1')", $text);
        }

        return str_replace(array( "&lt;", "&gt;", "&quot;", "&amp;" ), array( "<", ">", "\"", "&" ), $text);
    }

    public function encode_email_header($text, $charset = "utf-8", $force_encode = false, $quoted_string = true)
    {
        $text = trim($text);
        if( !$charset ) 
        {
            return $text;
        }

        if( $force_encode == true ) 
        {
            $qp_encode = true;
        }
        else
        {
            $qp_encode = false;
            for( $i = 0; $i < strlen($text); $i++ ) 
            {
                if( 127 < ord($text[$i]) ) 
                {
                    $qp_encode = true;
                    break;
                }

            }
        }

        if( $qp_encode == true ) 
        {
            $outtext = preg_replace("#([^a-zA-Z0-9!*+\\-/ ])#e", "'=' . strtoupper(dechex(ord(str_replace('\\\"', '\"', '\\1'))))", $text);
            $outtext = str_replace(" ", "_", $outtext);
            $outtext = "=?" . $charset . "?q?" . $outtext . "?=";
            return $outtext;
        }

        if( $quoted_string ) 
        {
            $text = str_replace(array( "\"", "(", ")" ), array( "\\\"", "\\(", "\\)" ), $text);
            return "\"" . $text . "\"";
        }

        return preg_replace("#(\\r\\n|\\n|\\r)+#", " ", $text);
    }

}



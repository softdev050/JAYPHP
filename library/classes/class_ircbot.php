<?php 

class TSUE_IRCBot
{
    public $fp = NULL;
    public $debug = array(  );
    public $connected = false;
    public $font = array( "[c]" => "\x03", "[n]" => "\x0F", "[b]" => "\x02", "[u]" => "\x1F", "[r]" => "\x16" );

    public function TSUE_IRCBot($sendMessage = "")
    {
        global $TSUE;
        $this->fp = fsockopen($TSUE["TSUE_Settings"]->settings["ircbot"]["ircServer"], $TSUE["TSUE_Settings"]->settings["ircbot"]["ircPort"], $errno, $errstr, 5);
        if( !$this->fp ) 
        {
            $this->debug[] = "Unable to connect to IRC Server at " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircServer"] . ":" . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircPort"] . " (" . $errno . ") " . $errstr;
            return false;
        }

        if( $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"][0] != "#" ) 
        {
            $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"] = "#" . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"];
        }

        $this->debug[] = "Connected to: " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircServer"];
        $this->connected = true;
        $this->sendCMD("PASS " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircPass"]);
        $this->sendCMD("NICK " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircNick"]);
        $this->sendCMD("USER " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircNick"] . " TSUE IRC BOT");
        while( !feof($this->fp) ) 
        {
            $data = str_replace(array( "\n", "\r" ), "", fgets($this->fp, 1024));
            $this->debug[] = "[RECEIVE] " . $data;
            $exData = tsue_explode(" ", $data);
            if( isset($exData["0"]) && $exData["0"] == "PING" ) 
            {
                $this->sendCMD("PONG " . $exData[1]);
            }
            else
            {
                if( isset($exData["1"]) && $exData["1"] == "376" ) 
                {
                    $this->sendCMD("JOIN " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"]);
                }
                else
                {
                    if( isset($exData["1"]) && $exData["1"] == "366" ) 
                    {
                        $sendMessage = trim($sendMessage);
                        if( $sendMessage ) 
                        {
                            $parsedMessages = preg_split("/\\r?\\n/", $sendMessage, -1, PREG_SPLIT_NO_EMPTY);
                            if( $parsedMessages ) 
                            {
                                foreach( $parsedMessages as $Message ) 
                                {
                                    $this->sendCMD("PRIVMSG " . $TSUE["TSUE_Settings"]->settings["ircbot"]["ircChannel"] . " [c]4,0" . $Message);
                                }
                            }

                        }

                        $this->Disconnect();
                        return NULL;
                    }

                }

            }

        }
        if( $this->connected ) 
        {
            $this->Disconnect();
        }

    }

    public function sendCMD($cmd)
    {
        @fputs($this->fp, @strtr($cmd, $this->font) . "\n\r");
        $this->debug[] = "[SEND] " . $cmd;
    }

    public function Disconnect()
    {
        $this->sendCMD("QUIT :USING TSUE IRC BOT");
        @fclose($this->fp);
    }

    public function Debug()
    {
        return ($this->debug ? implode("<br />", $this->debug) : false);
    }

}



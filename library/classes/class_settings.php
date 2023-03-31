<?php 

class TSUE_Settings
{
    public $settings = array(  );
//public function TSUE_Settings($loadSettings = "")
    function __construct($loadSettings = "")
    {
        global $TSUE;
        if( !$loadSettings ) 
        {
            $loadSettings = "active_announcements_cache active_news_cache auto_warning banned_emails_cache banned_ips_cache censor_cache dialog_smilies_cache downloads forums_permissions_cache gallery global_settings happy_hours ircbot shoutbox tsue_plugins_cache xbt xbt_happy_hours tsue_torrents_genres_cache banned_countries_cache magnet_links auto_alert search_system";
        }

        $this->loadSettings($loadSettings);
    }

    public function loadSettings($loadSettings = "")
    {
        global $TSUE;
        $loadSettings = $this->handleLoadSettings($loadSettings);
        $fetchSettings = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE settingname, settingvalues\r\n\t\tFROM tsue_settings \r\n\t\tWHERE settingname IN (" . $loadSettings . ")");
        if( $TSUE["TSUE_Database"]->num_rows($fetchSettings) ) 
        {
            while( $Setting = $TSUE["TSUE_Database"]->fetch_assoc($fetchSettings) ) 
            {
                if( !isset($this->settings[$Setting["settingname"]]) ) 
                {
                    $this->settings[$Setting["settingname"]] = unserialize($Setting["settingvalues"]);
                }

            }
            $TSUE["TSUE_Database"]->free($fetchSettings);
        }

    }

    public function loadSetting($loadSetting = "")
    {
        global $TSUE;
        if( isset($this->settings[$loadSetting]) ) 
        {
            return NULL;
        }

        $fetchSetting = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE settingvalues\r\n\t\tFROM tsue_settings \r\n\t\tWHERE settingname = " . $TSUE["TSUE_Database"]->escape($loadSetting));
        if( $fetchSetting ) 
        {
            $this->settings[$loadSetting] = unserialize($fetchSetting["settingvalues"]);
        }

    }

    public function handleLoadSettings($loadSettings)
    {
        $sql = array(  );
        $loadSettings = explode(" ", $loadSettings);
        foreach( $loadSettings as $s ) 
        {
            $sql[] = "'" . $s . "'";
        }
        return implode(", ", $sql);
    }

}



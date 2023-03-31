<?php 

class TSUE_Plugin
{
    private $Cache = array(  );
    private $PluginPath = NULL;
    public $hasSideBarPlugins = false;

    public function TSUE_Plugin()
    {
        global $TSUE;
        $this->PluginPath = REALPATH . "library/plugins/";
        if( !empty($TSUE["TSUE_Settings"]->settings["tsue_plugins_cache"]) ) 
        {
            foreach( $TSUE["TSUE_Settings"]->settings["tsue_plugins_cache"] as $Plugin ) 
            {
                $this->Cache[$Plugin["pluginid"]] = $Plugin;
            }
        }

    }

    public function loadPlugin($pluginid, $pluginPosition = "")
    {
        if( isset($this->Cache[$pluginid]) && hasViewPermission($this->Cache[$pluginid]["viewpermissions"]) ) 
        {
            $Plugin = $this->Cache[$pluginid];
            if( $Plugin["filename"] && is_file($this->PluginPath . $Plugin["filename"]) ) 
            {
                $pluginFunction = str_replace(".php", "", $Plugin["filename"]);
                require_once($this->PluginPath . $Plugin["filename"]);
                return $pluginFunction($pluginPosition, ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  )));
            }

            if( $Plugin["contents"] ) 
            {
                return $Plugin["contents"];
            }

            return "<b>Fatal Plugin Error</b>: " . $Plugin["name"];
        }

        return "";
    }

    public function loadPlugins($pluginids, $pluginPosition = "")
    {
        $contents = "";
        foreach( $pluginids as $pluginid ) 
        {
            $contents .= $this->loadPlugin($pluginid, $pluginPosition);
        }
        return $contents;
    }

}



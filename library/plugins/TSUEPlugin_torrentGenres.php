<?php 
function TSUEPlugin_torrentGenres($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $isToggled = isToggled("recentTorrentGenres");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] ) 
    {
        $genreImagesFullURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_genres/";
        $genreClickFullURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;genre=";
        $genreIcons = "";
        foreach( $TSUE["TSUE_Settings"]->settings["tsue_torrents_genres_cache"] as $Genre ) 
        {
            eval("\$genreIcons .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_torrent_genres_genreIcon") . "\";");
        }
    }

    eval("\$TSUEPlugin_torrentGenres = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_torrent_genres") . "\";");
    return $TSUEPlugin_torrentGenres;
}



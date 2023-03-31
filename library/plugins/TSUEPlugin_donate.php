<?php 
function TSUEPlugin_donate($pluginPosition = "", $pluginOptions = array(  ))
{
    global $TSUE;
    $TSUE["TSUE_Template"]->loadJavascripts("donate");
    $cacheName = "TSUEPlugin_donate_" . $TSUE["TSUE_Member"]->info["languageid"];
    $isToggled = isToggled("donateUs");
    $class = (!$isToggled ? "" : "hidden");
    $toggleSrc = (!$isToggled ? "bullet_toggle_minus" : "bullet_toggle_plus");
    if( !($goal_amount_x_raised_so_far_y = $TSUE["TSUE_Cache"]->readCache($cacheName)) || defined("IS_AJAX") ) 
    {
        $Goal = $TSUE["TSUE_Settings"]->settings["global_settings"]["donate_goal"];
        $Current = 0;
        $Start = mktime(0, 0, 0, date("m"), 1);
        $End = mktime(23, 59, 59, date("m") + 1, 0);
        $Funds = $TSUE["TSUE_Database"]->query_result("SELECT SQL_NO_CACHE SUM(amount) as totalFunds FROM `tsue_member_upgrades_transaction` WHERE dateline BETWEEN " . $Start . " AND " . $End);
        if( $Funds && $Funds["totalFunds"] ) 
        {
            $Current = $Funds["totalFunds"];
        }

        if( $Goal < $Current ) 
        {
            $Percent = 100;
        }
        else
        {
            if( !$Current ) 
            {
                $Percent = 0;
            }
            else
            {
                $Percent = round($Current / $Goal * 100);
            }

        }

        if( getPluginOption($pluginOptions, "show_extra_info") == 1 ) 
        {
            $goal_amount_x_raised_so_far_y = get_phrase("goal_amount_x_raised_so_far_y", number_format($Goal, 2), $Current);
        }
        else
        {
            $goal_amount_x_raised_so_far_y = "";
        }

        if( getPluginOption($pluginOptions, "donate_use_chart") == 1 ) 
        {
            $Current = $Percent;
            if( 100 <= $Current || $Goal <= $Current ) 
            {
                $Goal = 0;
            }

            eval("\$plugin_donate_chart_api = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_donate_chart_api") . "\";");
            $goal_amount_x_raised_so_far_y = $plugin_donate_chart_api . $goal_amount_x_raised_so_far_y;
        }
        else
        {
            eval("\$goal_amount_x_raised_so_far_y .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_donate_api") . "\";");
        }

        $TSUE["TSUE_Cache"]->saveCache($cacheName, $goal_amount_x_raised_so_far_y);
    }

    if( getPluginOption($pluginOptions, "donate_use_chart") == 1 ) 
    {
        eval("\$TSUEPlugin_donate = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_donate_chart") . "\";");
    }
    else
    {
        eval("\$TSUEPlugin_donate = \"" . $TSUE["TSUE_Template"]->LoadTemplate("plugin_donate") . "\";");
    }

    return $TSUEPlugin_donate;
}



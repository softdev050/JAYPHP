<?php 
function printOutput($Output = "", $Page_Title = "")
{
    global $TSUE;
    if (0) // ( substr(getServerURL(), 0, 12) != substr($TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"], 0, 12) && !headers_sent() ) 
    {
        $QUERY_STRING = fetch_server_value("QUERY_STRING");
        if( $QUERY_STRING && substr($QUERY_STRING, 0, 1) != "?" ) 
        {
            $QUERY_STRING = "?" . $QUERY_STRING;
        }

        if( substr($QUERY_STRING, 0, 1) != "/" ) 
        {
            $QUERY_STRING = "/" . $QUERY_STRING;
        }

        header("Location: " . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . $QUERY_STRING);
        exit();
    }

    $newAnnouncement = $Plugins_Right = $sidebar = $warningHTML = $PluginsHTML = $languageSelect = $globalSearchBox = $newStaffMessages = $autoWarning = $member_info_bar = "";
    $Page_Title = strip_tags((($Page_Title ? $Page_Title . " | " : "")) . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
    $isGuest = is_member_of("unregistered");
    if( $isGuest && getSetting("global_settings", "sls_active") == 1 ) 
    {
        if( getSetting("global_settings", "sls_show_signup_link") && defined("SCRIPTNAME") && in_array(SCRIPTNAME, array( "signup.php", "upgrade.php", "confirmaccount.php" )) || getSetting("global_settings", "sls_show_fp_link") && defined("SCRIPTNAME") && in_array(SCRIPTNAME, array( "forgotpassword.php" )) ) 
        {
            if( !defined("NO_PLUGINS") ) 
            {
                define("NO_PLUGINS", true);
            }

            if( !defined("NO_MENU") ) 
            {
                define("NO_MENU", true);
            }

        }
        else
        {
            $TSUE["TSUE_Template"]->loadJavascripts(array( "sls", "signup", "forgot_password", "passwordstrength" ));
            $JSPhrases = $TSUE["TSUE_Template"]->prepareJSPhrases("show_more_torrents");
            $PAGEID = PAGEID;
            $PAGEFILE = PAGEFILE;
            eval("\$main_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("main_javascript") . "\";");
            $loadjavascriptsCache = $TSUE["TSUE_Template"]->prepareJS();
            $TIMENOW = TIMENOW;
            $VERSION = V;
            $Signup = "";
            $forgotPassword = "";
            if( getSetting("global_settings", "sls_show_signup_link") == 1 ) 
            {
                $Signup = "<a href=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=signup&pid=16\" id=\"signup\">" . $TSUE["TSUE_Language"]->phrase["signup"] . "</a>";
            }

            if( getSetting("global_settings", "sls_show_fp_link") == 1 ) 
            {
                $forgotPassword = (($Signup ? " - " : "")) . "<a href=\"#\" rel=\"forgot-password\">" . $TSUE["TSUE_Language"]->phrase["forgot_password"] . "</a>";
            }

            $Output = "";
            eval("\$Output .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("main_sls") . "\";");
            if( getSetting("global_settings", "sls_show_recent_torrents") == 1 ) 
            {
                require(REALPATH . "library/plugins/TSUEPlugin_simpleRecentTorrents.php");
                $Output = str_replace("</body>", TSUEPlugin_simpleRecentTorrents(), $Output);
            }

            _sendHeaders($Output);
            exit();
        }

    }

    if( isset($TSUE["TSUE_Settings"]->settings["active_announcements_cache"]["date"]) && $TSUE["TSUE_Member"]->info["lastvisit"] <= $TSUE["TSUE_Settings"]->settings["active_announcements_cache"]["date"] && !isset($_COOKIE["read_announcement"]) ) 
    {
        eval("\$newAnnouncement .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("new_announcement") . "\";");
    }

    if( has_permission("canreply_staff_messages") ) 
    {
        $Count = $TSUE["TSUE_Database"]->row_count("SELECT rid FROM tsue_staff_messages WHERE rid = 0");
        if( $Count ) 
        {
            $TSUE["TSUE_Language"]->phrase["new_staff_message"] = get_phrase("new_staff_message", $Count);
            eval("\$newStaffMessages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("new_staff_messages") . "\";");
        }

    }

    eval("\$tinymce_init = \"" . $TSUE["TSUE_Template"]->LoadTemplate("tinymce_init") . "\";");
    if( !in_array(SCRIPTNAME, array( "confirmaccount.php", "forgotpassword.php", "signup.php" )) ) 
    {
        if( is_member_of("awaitingemailconfirmation") ) 
        {
            $TSUE["TSUE_Template"]->loadJavascripts("resend_confirmation_email");
            $TSUE["TSUE_Language"]->phrase["confirm_awaiting_confirmation"] = get_phrase("confirm_awaiting_confirmation", $TSUE["TSUE_Member"]->info["email"]);
            eval("\$warningHTML .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("resend_confirmation_email") . "\";");
        }

        if( is_member_of("awaitingmoderation") ) 
        {
            $warningHTML = show_information(get_phrase("confirm_awaiting_moderation"), 0, 0);
        }

    }

    if( $isGuest ) 
    {
        $TSUE["TSUE_Template"]->loadJavascripts(array( "login", "signup", "forgot_password", "passwordstrength" ));
    }
    else
    {
        $TSUE["TSUE_Template"]->loadJavascripts("messages");
        if( $TSUE["TSUE_Member"]->info["autoWarnedDate"] && $TSUE["TSUE_Settings"]->settings["auto_warning"]["active"] && $TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"] ) 
        {
            $memberRatio = member_ratio($TSUE["TSUE_Member"]->info["uploaded"], $TSUE["TSUE_Member"]->info["downloaded"], true);
            if( $memberRatio < $TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"] ) 
            {
                $ratioFixDate = convert_time($TSUE["TSUE_Member"]->info["autoWarnedDate"] + $TSUE["TSUE_Settings"]->settings["auto_warning"]["warn_length"] * 24 * 60 * 60);
                $autoWarningMessage = get_phrase("auto_warning_warn_member", $memberRatio, number_format($TSUE["TSUE_Settings"]->settings["auto_warning"]["min_ratio"], 2), $ratioFixDate);
                eval("\$autoWarning = \"" . $TSUE["TSUE_Template"]->LoadTemplate("autoWarning") . "\";");
            }

        }

        if( $TSUE["TSUE_Member"]->info["expiry_date"] && $TSUE["TSUE_Member"]->info["expiry_date"] - 604800 <= TIMENOW ) 
        {
            $autoWarningMessage = get_phrase("paid_subscription_about_expire", convert_time($TSUE["TSUE_Member"]->info["expiry_date"]));
            eval("\$autoWarning = \"" . $TSUE["TSUE_Template"]->LoadTemplate("autoWarning") . "\";");
        }

        $member_info_bar = member_info_bar();
    }

    if( defined("PASSWORDEXPIRED") ) 
    {
        if( !defined("NO_PLUGINS") ) 
        {
            define("NO_PLUGINS", true);
        }

        $Output = show_error(get_phrase("your_password_has_expired", PASSWORDEXPIRED, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&action=password&pid=7"), $Page_Title, false);
    }

    if( defined("ACCOUNTPARKED") ) 
    {
        if( !defined("NO_PLUGINS") ) 
        {
            define("NO_PLUGINS", true);
        }

        $Output = show_error(get_phrase("your_account_is_parked", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=membercp&action=preferences&pid=5"), $Page_Title, false);
    }

    if( defined("PAGEID") && PAGEID && !defined("NO_PLUGINS") ) 
    {
        $cacheName = "plugins_" . PAGEID;
        if( !($Page = $TSUE["TSUE_Cache"]->readCache($cacheName)) ) 
        {
            $Page = $TSUE["TSUE_Database"]->query_result("SELECT plugins_left, plugins_right, viewpermissions FROM tsue_pages WHERE pageid = " . $TSUE["TSUE_Database"]->escape(PAGEID) . " AND active = 1");
        }
        else
        {
            $Page = unserialize($Page);
            $usedCache = true;
        }

        if( $Page ) 
        {
            if( !hasViewPermission($Page["viewpermissions"]) ) 
            {
                $Output = show_error(get_phrase("permission_denied"), "", false);
            }
            else
            {
                if( trim($Page["plugins_right"]) != "" ) 
                {
                    $plugins_right = tsue_explode(",", $Page["plugins_right"]);
                    if( $plugins_right ) 
                    {
                        $TSUE["TSUE_Plugin"]->hasSideBarPlugins = true;
                        $Plugins_Right = $TSUE["TSUE_Plugin"]->loadPlugins($plugins_right, "right");
                        eval("\$sidebar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("sidebar") . "\";");
                    }

                }

                if( trim($Page["plugins_left"]) != "" ) 
                {
                    $plugins_left = tsue_explode(",", $Page["plugins_left"]);
                    if( $plugins_left ) 
                    {
                        $PluginsHTML = $TSUE["TSUE_Plugin"]->loadPlugins($plugins_left, "left");
                    }

                }

                if( !isset($usedCache) ) 
                {
                    $TSUE["TSUE_Cache"]->saveCache($cacheName, serialize(array( "plugins_left" => $Page["plugins_left"], "plugins_right" => $Page["plugins_right"], "viewpermissions" => $Page["viewpermissions"] )));
                }

            }

        }
        else
        {
            $Output = show_error(get_phrase("message_content_error"), "", false);
        }

    }

    if( defined("LOAD_PLUGIN_ID") ) 
    {
        $loadPlugins = tsue_explode(",", LOAD_PLUGIN_ID);
        $Plugins_Right = $TSUE["TSUE_Plugin"]->loadPlugins($loadPlugins, "right");
        eval("\$sidebar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("sidebar") . "\";");
    }

    if( strstr($TSUE["TSUE_Settings"]->settings["global_settings"]["available_languages"], ",") !== false ) 
    {
        eval("\$languageSelect = \"" . $TSUE["TSUE_Template"]->LoadTemplate("languageSelect") . "\";");
    }

    $SubNavigation = "";
    if( !$isGuest && (has_permission("canview_member_history") || has_permission("canwarn_member") || has_permission("canaward_member") || has_permission("canban_member") || has_permission("canmute_member") || has_permission("canremove_avatar") || has_permission("canreset_passkey") || has_permission("canlogin_admincp") || has_permission("canview_all_content") || has_permission("canreply_staff_messages") || has_permission("canmanage_applications") || has_permission("canmanage_reports") || has_permission("canadd_note") || $TSUE["TSUE_Member"]->testingPermission) ) 
    {
        $TSUE["TSUE_Template"]->loadJavascripts("staff");
    }

    $breadcrumb = CreateBreadcrumb();
    if( !$isGuest ) 
    {
        eval("\$globalSearchBox = \"" . $TSUE["TSUE_Template"]->LoadTemplate("global_search") . "\";");
    }

    $contentWidthClass = ($sidebar ? "semiWidth" : "fullwidth");
    if( !defined("NO_MENU") ) 
    {
        $menu_li = buildMenu($isGuest);
    }
    else
    {
        $menu_li = "";
    }

    eval("\$Menu = \"" . $TSUE["TSUE_Template"]->LoadTemplate("menu") . "\";");
    $JSPhrases = $TSUE["TSUE_Template"]->prepareJSPhrases("show_more_torrents");
    $PAGEID = PAGEID;
    $PAGEFILE = PAGEFILE;
    eval("\$main_javascript = \"" . $TSUE["TSUE_Template"]->LoadTemplate("main_javascript") . "\";");
    $TIMENOW = TIMENOW;
    $VERSION = V;
    $loadjavascriptsCache = $TSUE["TSUE_Template"]->prepareJS();
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("main") . "\";");
    if( canDebug() ) 
    {
        require_once(REALPATH . "library/functions/functions_debug.php");
        if( isset($_GET["debug"]) && $_GET["debug"] == "1" ) 
        {
            exit( fullDebugOutput() );
        }

        $Output .= basicDebugOutput();
    }

    _sendHeaders($Output);
}

function buildAnnounceIntervalTimeout($tolerance = 90)
{
    global $TSUE;
    if( !isset($TSUE["TSUE_Settings"]->settings["xbt"]) ) 
    {
        $TSUE["TSUE_Settings"]->loadSetting("xbt");
    }

    $xbtActive = getSetting("xbt", "active");
    $announceInterval = ($xbtActive ? getSetting("xbt", "announce_interval") : getSetting("global_settings", "announce_interval"));
    $timeOut = TIMENOW - $announceInterval;
    $timeOut -= $tolerance;
    if( $timeOut <= 0 ) 
    {
        $timeOut = 0;
    }

    return $timeOut;
}

function getServerURL()
{
    $port = (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] ? intval($_SERVER["SERVER_PORT"]) : 0);
    $port = (in_array($port, array( 80, 443 )) ? "" : ":" . $port);
    $scheme = (":443" == $port || isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off" ? "https://" : "http://");
    $host = fetch_server_value("HTTP_HOST");
    $name = fetch_server_value("SERVER_NAME");
    $host = (substr_count($name, ".") < substr_count($host, ".") ? $host : $name);
    if( !($scriptpath = fetch_server_value("PATH_INFO")) && !($scriptpath = fetch_server_value("REDIRECT_URL")) && !($scriptpath = fetch_server_value("URL")) && !($scriptpath = fetch_server_value("PHP_SELF")) ) 
    {
        $scriptpath = fetch_server_value("SCRIPT_NAME");
    }

    $url = $scheme . $host . "/" . str_replace("index.php", "", ltrim($scriptpath, "/\\"));
    return $url;
}

function fetch_server_value($name)
{
    if( isset($_SERVER[$name]) && $_SERVER[$name] ) 
    {
        return $_SERVER[$name];
    }

    if( isset($_ENV[$name]) && $_ENV[$name] ) 
    {
        return $_ENV[$name];
    }

    return false;
}

function canDebug()
{
    global $TSUE;
    return $TSUE["TSUE_Settings"]->settings["global_settings"]["tsue_debug_mode"] && has_permission("canview_debug");
}

function _sendHeaders($Content, $ContentType = "text/html", $UseCharset = true, $useCache = false)
{
    global $TSUE;
    ob_start();
    $Content = gzipContentIfSupported($Content);
    if( $UseCharset && SCRIPTNAME != "announce.php" ) 
    {
        $UseCharset = "; charset=" . $TSUE["TSUE_Language"]->charset;
    }
    else
    {
        $UseCharset = "";
    }

    if( !$useCache ) 
    {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    }
    else
    {
        header("Cache-Control: public");
        header("Expires: Wed, 01 Jan 2020 00:00:00 GMT");
    }

    header("X-Powered-By: TSUE " . V);
    header("Content-Length: " . strlen($Content));
    header("Content-Type: " . $ContentType . $UseCharset);
    header("X-UA-Compatible: IE=edge,chrome=1");
    exit( $Content );
}

function gzipContentIfSupported($Content)
{
    if( @ini_get("zlib.output_compression") ) 
    {
        return $Content;
    }

    if( !function_exists("gzencode") || empty($_SERVER["HTTP_ACCEPT_ENCODING"]) ) 
    {
        return $Content;
    }

    if( !is_string($Content) ) 
    {
        return $Content;
    }

    if( strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false ) 
    {
        global $TSUE;
        header("Content-Encoding: gzip");
        header("Vary: Accept-Encoding");
        $Content = gzencode($Content, $TSUE["TSUE_Settings"]->settings["global_settings"]["gzip_compression_level"], FORCE_GZIP);
    }

    return $Content;
}

function buildMenu($isGuest)
{
    global $TSUE;
    $pagesCache = $subPagesCache = array(  );
    if( $Cache = $TSUE["TSUE_Cache"]->readCache("pages") ) 
    {
        $Cache = unserialize($Cache);
        foreach( $Cache as $Page ) 
        {
            if( $Page["parentid"] ) 
            {
                $SubPagesCache[$Page["parentid"]][] = $Page;
            }
            else
            {
                $pagesCache[] = $Page;
            }

        }
        unset($Cache);
    }
    else
    {
        $Cache = array(  );
        $Pages = $TSUE["TSUE_Database"]->query("SELECT pageid, parentid, name, phrase, internal_link, external_link, showinmenu, viewpermissions \r\n\t\tFROM tsue_pages FORCE INDEX (active)\r\n\t\tWHERE active = 1\r\n\t\tORDER BY `sort` ASC");
        while( $Page = $TSUE["TSUE_Database"]->fetch_assoc($Pages) ) 
        {
            if( $Page["showinmenu"] ) 
            {
                $Cache[] = $Page;
                if( $Page["parentid"] ) 
                {
                    $SubPagesCache[$Page["parentid"]][] = $Page;
                }
                else
                {
                    $pagesCache[] = $Page;
                }

            }

        }
        $TSUE["TSUE_Database"]->free($Pages);
        $TSUE["TSUE_Cache"]->saveCache("pages", serialize($Cache));
        unset($Cache);
    }

    $menu_li = "";
    if( isset($TSUE["extraMenuItems"]) && $TSUE["extraMenuItems"] ) 
    {
        $menu_li .= $TSUE["extraMenuItems"];
    }

    foreach( $pagesCache as $Page ) 
    {
        if( hasViewPermission($Page["viewpermissions"]) ) 
        {
            $subLinks = "";
            if( isset($SubPagesCache[$Page["pageid"]]) ) 
            {
                foreach( $SubPagesCache[$Page["pageid"]] as $subPage ) 
                {
                    if( hasViewPermission($subPage["viewpermissions"]) ) 
                    {
                        if( $subPage["internal_link"] == "#" ) 
                        {
                            $Link = "javascript:void(0)";
                        }
                        else
                        {
                            $Link = ($subPage["internal_link"] ? $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . fixInternalLink($subPage["internal_link"]) . "&amp;pid=" . $subPage["pageid"] : fixInternalLink($subPage["external_link"]));
                        }

                        $Phrase = get_phrase($subPage["phrase"]);
                        eval("\$subLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("menu_sublinks") . "\";");
                    }

                }
            }

            if( $Page["internal_link"] == "#" ) 
            {
                $Link = "javascript:void(0)";
            }
            else
            {
                $Link = ($Page["internal_link"] ? $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . fixInternalLink($Page["internal_link"]) . "&amp;pid=" . $Page["pageid"] : fixInternalLink($Page["external_link"]));
            }

            $Phrase = get_phrase($Page["phrase"]);
            $LiClass = (!$subLinks ? "nodrop" : "");
            $aClass = (!$subLinks ? "" : "drop");
            $menu_li_dropdown = "";
            if( $subLinks ) 
            {
                eval("\$menu_li_dropdown = \"" . $TSUE["TSUE_Template"]->LoadTemplate("menu_li_dropdown") . "\";");
            }

            eval("\$menu_li .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("menu_li") . "\";");
        }

    }
    if( !$isGuest ) 
    {
        list($unread_messages, $unread_alerts) = alertBalloons();
        eval("\$menu_li .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("menu_li_alertBalloons") . "\";");
    }

    unset($pagesCache);
    unset($subPagesCache);
    return $menu_li;
}

function getPluginOption($Options = array(  ), $optionFieldName = "", $defaultFieldValue = "")
{
    foreach( $Options as $Option ) 
    {
        if( $Option["fieldName"] == $optionFieldName ) 
        {
            return $Option["fieldValue"];
        }

    }
    return $defaultFieldValue;
}

function alertBalloons()
{
    global $TSUE;
    $Count = $TSUE["TSUE_Member"]->info["unread_messages"];
    $hiddenClass = ($Count ? "" : " hidden");
    eval("\$unread_messages = \"" . $TSUE["TSUE_Template"]->LoadTemplate("alertBalloon") . "\";");
    $Count = $TSUE["TSUE_Member"]->info["unread_alerts"];
    $hiddenClass = ($Count ? "" : " hidden");
    eval("\$unread_alerts = \"" . $TSUE["TSUE_Template"]->LoadTemplate("alertBalloon") . "\";");
    return array( $unread_messages, $unread_alerts );
}

function AddBreadcrumb($breadcrumbs)
{
    global $TSUE;
    foreach( $breadcrumbs as $_title => $_link ) 
    {
        eval("\$TSUE['breadcrumb'][] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("breadcrumb_list") . "\";");
    }
}

function resetBreadcrumbs()
{
    global $TSUE;
    $TSUE["breadcrumb"] = array(  );
}

function CreateBreadcrumb()
{
    global $TSUE;
    $breadcrumbs = implode("\n", $TSUE["breadcrumb"]);
    if( !$breadcrumbs ) 
    {
        return "";
    }

    $adminLinks = "";
    if( $TSUE["TSUE_Member"]->testingPermission ) 
    {
        $TSUE["TSUE_Language"]->phrase["perms_from_x"] = get_phrase("perms_from_x", $TSUE["TSUE_Member"]->testingPermission);
        eval("\$adminLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("reset_perms_link") . "\";");
    }

    if( has_permission("canlogin_admincp") ) 
    {
        eval("\$adminLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("admincp_link") . "\";");
    }

    if( has_permission("canmanage_reports") ) 
    {
        eval("\$adminLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_reports_link") . "\";");
    }

    if( has_permission("canmanage_applications") ) 
    {
        eval("\$adminLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_applications_link") . "\";");
    }

    if( has_permission("canreply_staff_messages") ) 
    {
        eval("\$adminLinks .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("staff_messages_link") . "\";");
    }

    $crumbsLinks = "";
    if( $adminLinks ) 
    {
        eval("\$crumbsLinks = \"" . $TSUE["TSUE_Template"]->LoadTemplate("acp_crumbsLinks") . "\";");
    }

    if( !empty($TSUE["crumbsLinks"]) ) 
    {
        foreach( $TSUE["crumbsLinks"] as $C ) 
        {
            $crumbsLinks .= "<span class=\"admincp_link\">" . $C . "</span>";
        }
        unset($TSUE["crumbsLinks"]);
    }

    eval("\$breadcrumb = \"" . $TSUE["TSUE_Template"]->LoadTemplate("breadcrumb") . "\";");
    return $breadcrumb;
}

function fetchCPOption($option)
{
    global $TSUE;
    return (isset($TSUE["TSUE_Member"]->info["cpOptions"][$option]) ? $TSUE["TSUE_Member"]->info["cpOptions"][$option] : false);
}

function isMuted($muted, $area)
{
    $Areas = array( "comments" => 1, "forums" => 2, "shoutbox" => 3, "pm" => 4 );
    if( !$muted ) 
    {
        return false;
    }

    if( strstr($muted, (string) $Areas[$area]) !== false ) 
    {
        return true;
    }

    return false;
}

function listMutes($muted, $useBR = true)
{
    $mutes = array(  );
    if( strstr($muted, "1") !== false ) 
    {
        $mutes[] = get_phrase("muted_in_comments");
    }

    if( strstr($muted, "2") !== false ) 
    {
        $mutes[] = get_phrase("muted_in_forums");
    }

    if( strstr($muted, "3") !== false ) 
    {
        $mutes[] = get_phrase("muted_in_shoutbox");
    }

    if( strstr($muted, "4") !== false ) 
    {
        $mutes[] = get_phrase("muted_in_pm");
    }

    return (($useBR ? "<br />" : "")) . implode("<br />", $mutes);
}

function fixInternalLink($link = "")
{
    if( $link ) 
    {
        $link = str_replace("&amp;", "&", $link);
        $link = str_replace("&", "&amp;", $link);
    }

    return $link;
}

function show_error($ErrorMessage, $Page_Title = "", $UseTemplate = 1, $ShowTitle = 1, $DivID = "show_error", $skipAJAX = false)
{
    global $TSUE;
    if( is_array($ErrorMessage) ) 
    {
        $ErrorMessage = implode("<br />", $ErrorMessage);
    }

    $loginForm = loginForm($ErrorMessage);
    if( defined("IS_AJAX") && !$skipAJAX ) 
    {
        ajax_message($ErrorMessage . $loginForm, "-ERROR-");
    }

    $ErrorMessage = (($ShowTitle ? "<b>" . get_phrase("an_error_hash_occurded") . "</b><br />" : "")) . $ErrorMessage;
    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("error") . "\";");
    $Output .= $loginForm;
    if( $UseTemplate ) 
    {
        printoutput($Output, $Page_Title);
    }
    else
    {
        return $Output;
    }

}

function show_information($InfoMessage, $Page_Title = "", $UseTemplate = 1, $DivID = "show_information")
{
    global $TSUE;
    if( is_array($InfoMessage) ) 
    {
        $InfoMessage = implode("<br />", $InfoMessage);
    }

    if( defined("IS_AJAX") ) 
    {
        ajax_message($InfoMessage, "-INFORMATION-");
    }

    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("information") . "\";");
    if( $UseTemplate ) 
    {
        printoutput($Output, $Page_Title);
    }
    else
    {
        return $Output;
    }

}

function show_done($DoneMessage, $Page_Title = "", $UseTemplate = 1, $DivID = "show_done")
{
    global $TSUE;
    if( is_array($DoneMessage) ) 
    {
        $DoneMessage = implode("<br />", $DoneMessage);
    }

    if( defined("IS_AJAX") && $UseTemplate ) 
    {
        ajax_message($DoneMessage, "-DONE-");
    }

    eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("done") . "\";");
    if( $UseTemplate ) 
    {
        printoutput($Output, $Page_Title);
    }
    else
    {
        return $Output;
    }

}

function ajax_message($Output = "", $Tag = "", $useFunction = true, $HeadMessage = "", $Class = "")
{
    global $TSUE;
    $Output = str_replace(array( "-ERROR-", "-DONE-", "-INFORMATION-" ), array( "- ERROR -", "- DONE -", "- INFORMATION -" ), $Output);
    $loginForm = loginForm($Output);
    if( $useFunction && $Tag ) 
    {
        $Class = str_replace("-", "", strtolower($Tag));
        if( !$HeadMessage ) 
        {
            switch( $Class ) 
            {
                case "error":
                    $HeadMessage = get_phrase("an_error_hash_occurded");
                    break;
                case "done":
                    $HeadMessage = get_phrase("information_success");
                    break;
                case "information":
                    $HeadMessage = get_phrase("server_response");
                    break;
            }
        }

    }

    if( $HeadMessage ) 
    {
        eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ajax_message") . "\";");
    }

    _sendheaders($Tag . $Output . $loginForm, "text/html");
}

function jsonHeaders($Output = "")
{
    global $TSUE;
    if( is_array($Output) ) 
    {
        foreach( $Output as $Var => $Val ) 
        {
            if( !mb_check_encoding($Val, "UTF-8") ) 
            {
                $Output[$Var] = utf8_encode($Val);
            }

        }
    }
    else
    {
        $Output = (mb_check_encoding($Output, "UTF-8") ? $Output : utf8_encode($Output));
    }

    $Output = json_encode($Output);
    ob_start();
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("X-Powered-By: TSUE " . V);
    header("Content-Length: " . strlen($Output));
    header("Content-type: text/plain; charset=" . $TSUE["TSUE_Language"]->charset);
    header("X-UA-Compatible: IE=edge,chrome=1");
    exit( $Output );
}

function jsonError($msg)
{
    jsonheaders(array( "error" => $msg ));
}

function printSimpleOutput($Output)
{
    _sendheaders($Output);
}

function autoDescription($area, $class = "")
{
    global $TSUE;
    $Fields = $TSUE["TSUE_Database"]->query("SELECT field_id, title, default_value, viewpermissions FROM tsue_auto_description WHERE area = " . $TSUE["TSUE_Database"]->escape($area) . " AND active = 1 ORDER BY display_order ASC");
    if( $TSUE["TSUE_Database"]->num_rows($Fields) ) 
    {
        $Options = "";
        while( $Field = $TSUE["TSUE_Database"]->fetch_assoc($Fields) ) 
        {
            if( $Field["viewpermissions"] ) 
            {
                $Field["viewpermissions"] = unserialize($Field["viewpermissions"]);
                if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Field["viewpermissions"]) ) 
                {
                    continue;
                }

            }

            $Options .= "<option value=\"" . $Field["field_id"] . "\">" . $Field["title"] . "</option>";
        }
        if( $Options ) 
        {
            $Options = "\r\n\t\t\t<optgroup label=\"" . get_phrase("auto_description") . "\">\r\n\t\t\t\t<option value=\"0\"></option>\r\n\t\t\t\t" . $Options . "\r\n\t\t\t</optgroup>";
            eval("\$auto_description = \"" . $TSUE["TSUE_Template"]->LoadTemplate("auto_description") . "\";");
            return $auto_description;
        }

    }

}

function buildAnnounceURL($passkey = "")
{
    global $TSUE;
    if( getSetting("xbt", "active") ) 
    {
        if( substr($TSUE["TSUE_Settings"]->settings["xbt"]["announce_url"], -1) != "/" ) 
        {
            $TSUE["TSUE_Settings"]->settings["xbt"]["announce_url"] = $TSUE["TSUE_Settings"]->settings["xbt"]["announce_url"] . "/";
        }

        $AnnounceURL = $TSUE["TSUE_Settings"]->settings["xbt"]["announce_url"] . substr(($passkey ? $passkey : $TSUE["TSUE_Member"]->info["passkey"]), 0, 32) . "/announce";
    }
    else
    {
        $AnnounceURL = $TSUE["TSUE_Settings"]->settings["global_settings"]["announce_url"] . "?pk=" . (($passkey ? $passkey : $TSUE["TSUE_Member"]->info["passkey"]));
    }

    return $AnnounceURL;
}

function get_permission($What)
{
    global $TSUE;
    return (isset($TSUE["TSUE_Member"]->info["permissions"][$What]) ? $TSUE["TSUE_Member"]->info["permissions"][$What] : false);
}

function has_permission($What, $Permissions = "")
{
    if( $Permissions ) 
    {
        $Permissions = (!is_array($Permissions) ? unserialize($Permissions) : $Permissions);
        return (isset($Permissions[$What]) ? $Permissions[$What] : false);
    }

    return get_permission($What);
}

function hasViewPermission($membergroups, $membergroupid = "")
{
    if( !$membergroupid ) 
    {
        global $TSUE;
        $membergroupid = (isset($TSUE["TSUE_Member"]->info["membergroupid"]) && $TSUE["TSUE_Member"]->info["membergroupid"] ? $TSUE["TSUE_Member"]->info["membergroupid"] : 0);
    }

    return ($membergroups ? $membergroupid && in_array($membergroupid, tsue_explode(",", $membergroups)) : true);
}

function has_forum_permission($Required, $Permissions)
{
    if( is_array($Required) ) 
    {
        foreach( $Required as $What ) 
        {
            if( !isset($Permissions[$What]) || isset($Permissions[$What]) && !$Permissions[$What] ) 
            {
                return false;
            }

        }
        return true;
    }
    else
    {
        return (isset($Permissions[$Required]) ? $Permissions[$Required] : false);
    }

}

function searchPermissionInMembergroups($searchFor)
{
    global $TSUE;
    $Found = false;
    $Membergroups = $TSUE["TSUE_Database"]->query("SELECT membergroupid, permissions FROM tsue_membergroups");
    if( $TSUE["TSUE_Database"]->num_rows($Membergroups) ) 
    {
        while( $Membergroup = $TSUE["TSUE_Database"]->fetch_assoc($Membergroups) ) 
        {
            $perms = unserialize($Membergroup["permissions"]);
            if( isset($perms[$searchFor]) && $perms[$searchFor] ) 
            {
                $Found[] = $Membergroup["membergroupid"];
            }

        }
    }

    return $Found;
}

function is_member_of($groupname, $justGetReturnGroupID = false)
{
    switch( strtolower($groupname) ) 
    {
        case "banned":
            $GroupID = 8;
            break;
        case "awaitingmoderation":
            $GroupID = 4;
            break;
        case "awaitingemailconfirmation":
            $GroupID = 3;
            break;
        case "registeredusers":
            $GroupID = 2;
            break;
        case "unregistered":
            $GroupID = 1;
    }
    if( $justGetReturnGroupID ) 
    {
        return (isset($GroupID) ? $GroupID : 0);
    }

    global $TSUE;
    return (isset($GroupID) && isset($TSUE["TSUE_Member"]->info["membergroupid"]) && $TSUE["TSUE_Member"]->info["membergroupid"] == $GroupID ? true : false);
}

function getMembername($membername, $groupstyle)
{
    return str_replace("{membername}", $membername, $groupstyle);
}

function getImage($Image = array(  ))
{
    global $TSUE;
    eval("\$Image = \"" . $TSUE["TSUE_Template"]->LoadTemplate("getImage") . "\";");
    return $Image;
}

function getImagesFullURL()
{
    global $TSUE;
    return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/";
}

function handlePrune($return = false)
{
    global $TSUE;
    $TSUE["TSUE_Database"]->truncate("tsue_file_caches");
    $cacheFolder = REALPATH . "data/cache/";
    if( !is_dir($cacheFolder) || !is_writable($cacheFolder) ) 
    {
        return get_phrase("file_manager_prune_no_cache_folder");
    }

    $cacheFiles = scandir($cacheFolder);
    if( count($cacheFiles) <= 2 ) 
    {
        return false;
    }

    foreach( $cacheFiles as $cacheFile ) 
    {
        $_ext = file_extension($cacheFile);
        if( in_array($_ext, array( "tsue", "gz", "js", "gif", "jpg", "png", "jpeg", "zip", "srv" )) ) 
        {
            @unlink($cacheFolder . $cacheFile);
        }

    }
    if( $return ) 
    {
        $HTML = get_phrase("file_manager_prune_cache_pruned");
        logAction($HTML, 2);
        return $HTML;
    }

}

function isToggled($id)
{
    return isset($_COOKIE["p_#" . $id]) && $_COOKIE["p_#" . $id] == "true";
}

function getSetting($setting, $value, $defaultValue = "")
{
    global $TSUE;
    return (isset($TSUE["TSUE_Settings"]->settings[$setting][$value]) ? $TSUE["TSUE_Settings"]->settings[$setting][$value] : $defaultValue);
}

function deleteCache($searchFor = "")
{
    global $TSUE;
    $cachePath = REALPATH . "data/cache/";
    $Files = scandir($cachePath);
    foreach( $Files as $File ) 
    {
        if( is_array($searchFor) ) 
        {
            foreach( $searchFor as $SF ) 
            {
                if( substr($File, 0, strlen($SF)) == $SF ) 
                {
                    @unlink($cachePath . $File);
                }

            }
        }
        else
        {
            if( substr($File, 0, strlen($searchFor)) == $searchFor ) 
            {
                @unlink($cachePath . $File);
            }

        }

    }
}

function updateSettings($settingname, $settingvalues)
{
    global $TSUE;
    $BuildQuery = array( "settingname" => $settingname, "settingvalues" => serialize($settingvalues) );
    $TSUE["TSUE_Database"]->delete("tsue_settings", "settingname = " . $TSUE["TSUE_Database"]->escape($settingname));
    return $TSUE["TSUE_Database"]->insert("tsue_settings", $BuildQuery);
}

function canViewProfile($ActiveUser = array(  ), $PassiveUser = array(  ))
{
    if( $ActiveUser["memberid"] != $PassiveUser["memberid"] && !has_permission("canview_all_profiles") && ($PassiveUser["allow_view_profile"] == "none" || $PassiveUser["allow_view_profile"] == "members" && is_member_of("unregistered") || $PassiveUser["allow_view_profile"] == "followed" && !is_following($PassiveUser["memberid"], $ActiveUser["memberid"])) ) 
    {
        return false;
    }

    return true;
}

function highlightSearchTerm($string, $term, $emClass = "highlight")
{
    $term = strval($term);
    if( $term !== "" ) 
    {
        return preg_replace("/(" . preg_quote($term, "/") . ")/si", "<em class=\"" . $emClass . "\">\\1</em>", strip_tags($string));
    }

    return strip_tags($string);
}

function sec2hms($sec, $padHours = false)
{
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= ($padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":");
    $minutes = intval(($sec / 60) % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":";
    $seconds = intval($sec % 60);
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    return $hms;
}

function check_flood($action, $flood_limit = 0)
{
    global $TSUE;
    if( !$TSUE["TSUE_Member"]->info["flood_limit"] && !$flood_limit ) 
    {
        return false;
    }

    if( !$flood_limit ) 
    {
        $flood_limit = $TSUE["TSUE_Member"]->info["flood_limit"];
    }

    $floodLimitTime = TIMENOW - $flood_limit;
    $TSUE["TSUE_Database"]->update("tsue_flood_check", array( "flood_time" => TIMENOW ), "memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND flood_action = " . $TSUE["TSUE_Database"]->escape($action) . " AND flood_time < " . $floodLimitTime);
    if( $TSUE["TSUE_Database"]->affected_rows() ) 
    {
        return false;
    }

    $TSUE["TSUE_Database"]->insert("tsue_flood_check", array( "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "flood_action" => $action, "flood_time" => TIMENOW ), false, NULL, true);
    if( $TSUE["TSUE_Database"]->affected_rows() ) 
    {
        return false;
    }

    $floodTime = $TSUE["TSUE_Database"]->query_result("SELECT flood_time FROM tsue_flood_check WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND flood_action = " . $TSUE["TSUE_Database"]->escape($action));
    $seconds = $floodTime["flood_time"] - $floodLimitTime;
    if( $seconds <= 0 ) 
    {
        return false;
    }

    if( $seconds <= 900 ) 
    {
        $warningPhrase = get_phrase("flood_warning_seconds", $seconds);
    }
    else
    {
        $warningPhrase = get_phrase("flood_warning", sec2hms($seconds));
    }

    if( defined("IS_AJAX") ) 
    {
        ajax_message($warningPhrase, "-ERROR-");
    }
    else
    {
        show_error($warningPhrase);
    }

}

function logAction($log, $type = 1)
{
    global $TSUE;
    $buildQuery = array( "memberid" => (isset($TSUE["TSUE_Member"]->info["memberid"]) ? $TSUE["TSUE_Member"]->info["memberid"] : 0), "membername" => (isset($TSUE["TSUE_Member"]->info["membername"]) ? $TSUE["TSUE_Member"]->info["membername"] : "system"), "date" => TIMENOW, "type" => $type, "log" => $log );
    $TSUE["TSUE_Database"]->insert("tsue_logs", $buildQuery);
}

function TSUE_ErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $TSUE;
    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["tsue_debug_mode"] && has_permission("canview_debug") ) 
    {
        $Message = "<h1>PHP ERROR</h1>[<b>" . $errno . "</b>] " . $errstr . "<br />Fatal error on line <b>" . $errline . "</b> in file <b>" . basename($errfile) . "</b><br />Aborting...";
        if( defined("SCRIPTNAME") && SCRIPTNAME == "announce.php" ) 
        {
            _printError(strip_tags($Message));
        }
        else
        {
            exit( $Message );
        }

    }

}

function autoAlert($text = "", $message_id = 0)
{
    global $TSUE;
    if( $text && $message_id ) 
    {
        $aa_words_strings = preg_split("/\\r?\\n/", trim(getsetting("auto_alert", "words")), -1, PREG_SPLIT_NO_EMPTY);
        $aa_admins = trim(getsetting("auto_alert", "admins"));
        if( $aa_words_strings && $aa_admins ) 
        {
            foreach( $aa_words_strings as $ws ) 
            {
                if( preg_match("#" . $ws . "#", $text) ) 
                {
                    $Found = true;
                    break;
                }

            }
            if( isset($Found) ) 
            {
                $WHERE = "membername IN (" . implode(",", array_map(array( $TSUE["TSUE_Database"], "escape" ), explode(",", trim(preg_replace("/\\s+/", "", $aa_admins))))) . ")";
                $Admins = $TSUE["TSUE_Database"]->query("SELECT memberid FROM tsue_members WHERE " . $WHERE);
                if( $TSUE["TSUE_Database"]->num_rows($Admins) ) 
                {
                    while( $Member = $TSUE["TSUE_Database"]->fetch_assoc($Admins) ) 
                    {
                        alert_member($Member["memberid"], 0, "", "auto_alerts", $message_id, "contains");
                    }
                }

            }

        }

    }

}

function alert_member($alerted_memberid, $memberid = 0, $membername = "", $content_type = "", $content_id = 0, $action = "", $Extra = 0)
{
    global $TSUE;
    $BuildQuery = array( "alerted_memberid" => $alerted_memberid, "memberid" => $memberid, "membername" => $membername, "content_type" => $content_type, "content_id" => $content_id, "action" => $action, "event_date" => TIMENOW, "extra" => $Extra );
    if( $TSUE["TSUE_Database"]->insert("tsue_member_alerts", $BuildQuery) ) 
    {
        $TSUE["TSUE_Database"]->query("UPDATE tsue_members SET unread_alerts = unread_alerts + 1 WHERE memberid = " . $TSUE["TSUE_Database"]->escape($alerted_memberid));
    }

}

function calculateAge($date_of_birth, $expKey = "/")
{
    if( !$date_of_birth || substr_count($date_of_birth, $expKey) != 2 || 10 < strlen($date_of_birth) ) 
    {
        return false;
    }

    list($day, $month, $year) = tsue_explode($expKey, $date_of_birth);
    list($cYear, $cMonth, $cDay) = tsue_explode("-", date("Y-m-d", TIMENOW));
    if( !checkdate($month, $day, $year) || $cYear <= $year ) 
    {
        return false;
    }

    if( $cYear - $year <= 5 ) 
    {
        return false;
    }

    $age = $cYear - $year;
    if( $cMonth < $month || $cMonth == $month && $cDay < $day ) 
    {
        $age--;
    }

    return $age;
}

function get_torrent_category_image($cid = "")
{
    global $TSUE;
    $ValidTorrentCategoryExtensions = array( "jpg", "gif", "png", "jpeg" );
    foreach( $ValidTorrentCategoryExtensions as $TorrentCategoryExtension ) 
    {
        if( is_file(REALPATH . "data/torrents/category_images/" . $cid . "." . $TorrentCategoryExtension) ) 
        {
            return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/category_images/" . $cid . "." . $TorrentCategoryExtension;
        }

    }
    return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_category.png";
}

function get_member_avatar($memberid = 0, $gender = "", $size = "s")
{
    global $TSUE;
    $ValidAvatarExtensions = array( "jpg", "gif", "png", "jpeg" );
    foreach( $ValidAvatarExtensions as $AvatarExtension ) 
    {
        if( is_file(REALPATH . "data/avatars/" . $size . "/" . $memberid . "." . $AvatarExtension) ) 
        {
            return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/avatars/" . $size . "/" . $memberid . "." . $AvatarExtension . "?" . TIMENOW;
        }

    }
    if( $gender && file_exists(REALPATH . "styles/" . $TSUE["TSUE_Template"]->ThemeName . "/avatars/avatar_" . $gender . "_" . $size . ".png") ) 
    {
        return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/avatars/avatar_" . $gender . "_" . $size . ".png?" . TIMENOW;
    }

    return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/avatars/avatar_" . $size . ".png?" . TIMENOW;
}

function get_languages()
{
    global $TSUE;
    $Languages = $TSUE["TSUE_Database"]->query("SELECT languageid, title FROM tsue_languages WHERE active = 1");
    $Selectbox = "\r\n\t<select name=\"languageid\" id=\"cat_content\">";
    while( $Language = $TSUE["TSUE_Database"]->fetch_assoc($Languages) ) 
    {
        $Selectbox .= "\r\n\t\t<option value=\"" . $Language["languageid"] . "\"" . (($Language["languageid"] == $TSUE["TSUE_Member"]->info["languageid"] ? " selected=\"selected\"" : "")) . ">" . ucfirst($Language["title"]) . "</option>";
    }
    $Selectbox .= "\r\n\t</select>";
    return $Selectbox;
}

function get_themes()
{
    global $TSUE;
    $Themes = $TSUE["TSUE_Database"]->query("SELECT themeid, themename, viewpermissions FROM tsue_themes WHERE active = 1");
    $Selectbox = "\r\n\t<select name=\"themeid\" id=\"cat_content\">";
    while( $Theme = $TSUE["TSUE_Database"]->fetch_assoc($Themes) ) 
    {
        if( $Theme["viewpermissions"] ) 
        {
            $Theme["viewpermissions"] = unserialize($Theme["viewpermissions"]);
            if( !in_array($TSUE["TSUE_Member"]->info["membergroupid"], $Theme["viewpermissions"]) ) 
            {
                continue;
            }

        }

        $Selectbox .= "\r\n\t\t<option value=\"" . $Theme["themeid"] . "\"" . (($Theme["themeid"] == $TSUE["TSUE_Member"]->info["themeid"] ? " selected=\"selected\"" : "")) . ">" . ucfirst($Theme["themename"]) . "</option>";
    }
    $Selectbox .= "\r\n\t</select>";
    return $Selectbox;
}

function fetch_timezones($offset = "all")
{
    $timezones = array( "-12" => get_phrase("timezone_gmt_minus_1200"), "-11" => get_phrase("timezone_gmt_minus_1100"), "-10" => get_phrase("timezone_gmt_minus_1000"), "-9" => get_phrase("timezone_gmt_minus_0900"), "-8" => get_phrase("timezone_gmt_minus_0800"), "-7" => get_phrase("timezone_gmt_minus_0700"), "-6" => get_phrase("timezone_gmt_minus_0600"), "-5" => get_phrase("timezone_gmt_minus_0500"), "-4.5" => get_phrase("timezone_gmt_minus_0430"), "-4" => get_phrase("timezone_gmt_minus_0400"), "-3.5" => get_phrase("timezone_gmt_minus_0330"), "-3" => get_phrase("timezone_gmt_minus_0300"), "-2" => get_phrase("timezone_gmt_minus_0200"), "-1" => get_phrase("timezone_gmt_minus_0100"), "0" => get_phrase("timezone_gmt_plus_0000"), "1" => get_phrase("timezone_gmt_plus_0100"), "2" => get_phrase("timezone_gmt_plus_0200"), "3" => get_phrase("timezone_gmt_plus_0300"), "3.5" => get_phrase("timezone_gmt_plus_0330"), "4" => get_phrase("timezone_gmt_plus_0400"), "4.5" => get_phrase("timezone_gmt_plus_0430"), "5" => get_phrase("timezone_gmt_plus_0500"), "5.5" => get_phrase("timezone_gmt_plus_0530"), "5.75" => get_phrase("timezone_gmt_plus_0545"), "6" => get_phrase("timezone_gmt_plus_0600"), "6.5" => get_phrase("timezone_gmt_plus_0630"), "7" => get_phrase("timezone_gmt_plus_0700"), "8" => get_phrase("timezone_gmt_plus_0800"), "9" => get_phrase("timezone_gmt_plus_0900"), "9.5" => get_phrase("timezone_gmt_plus_0930"), "10" => get_phrase("timezone_gmt_plus_1000"), "11" => get_phrase("timezone_gmt_plus_1100"), "12" => get_phrase("timezone_gmt_plus_1200") );
    if( $offset === "all" ) 
    {
        return $timezones;
    }

    return $timezones[(string) $offset];
}

function findPageNumber($Query = "", $perPage = 0)
{
    global $TSUE;
    if( !$Query || !$perPage ) 
    {
        return 0;
    }

    $totalRows = $TSUE["TSUE_Database"]->row_count($Query);
    if( $totalRows <= $perPage ) 
    {
        return 0;
    }

    return ceil($totalRows / $perPage);
}

function Pagination($total_rows, $perpage = 10, $targetpage = "", $adjacents = 3)
{
    global $TSUE;
    if( $total_rows < 1 || $perpage < 1 ) 
    {
        return array( "LIMIT 0, " . $total_rows, "" );
    }

    if( !$targetpage ) 
    {
        $targetpage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?";
    }

    $lastpage = ceil($total_rows / $perpage);
    if( $lastpage <= 1 ) 
    {
        return array( "LIMIT 0, " . $total_rows, "" );
    }

    $page = (isset($_GET["page"]) ? intval($_GET["page"]) : (isset($_POST["page"]) ? intval($_POST["page"]) : 0));
    if( $lastpage < $page ) 
    {
        $page = $lastpage;
    }

    if( $page ) 
    {
        $start = ($page - 1) * $perpage;
    }
    else
    {
        $start = 0;
    }

    if( $page == 0 ) 
    {
        $page = 1;
    }

    $prev = $page - 1;
    $next = $page + 1;
    $lpm1 = $lastpage - 1;
    $pagination = "";
    if( 1 < $lastpage ) 
    {
        $pagination .= "<div class=\"pagination\"><ul>";
        if( 1 < $page ) 
        {
            $pagination .= "<li><a href=\"" . $targetpage . "page=" . $prev . "\">&laquo;</a></li>";
        }

        if( $lastpage < 7 + $adjacents * 2 ) 
        {
            for( $counter = 1; $counter <= $lastpage; $counter++ ) 
            {
                if( $counter == $page ) 
                {
                    $pagination .= "<li class=\"active\"><a href=\"#\">" . $counter . "</a></li>";
                }
                else
                {
                    $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                }

            }
        }
        else
        {
            if( 5 + $adjacents * 2 < $lastpage ) 
            {
                if( $page < 1 + $adjacents * 2 ) 
                {
                    for( $counter = 1; $counter < 4 + $adjacents * 2; $counter++ ) 
                    {
                        if( $counter == $page ) 
                        {
                            $pagination .= "<li class=\"active\"><a href=\"#\">" . $counter . "</a></li>";
                        }
                        else
                        {
                            $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                        }

                    }
                    $goto = true;
                    $pagination .= "<li>...</li>";
                    $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lpm1 . "\">" . $lpm1 . "</a></li>";
                    $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lastpage . "\">" . $lastpage . "</a></li>";
                }
                else
                {
                    if( $page < $lastpage - $adjacents * 2 && $adjacents * 2 < $page ) 
                    {
                        $goto = true;
                        $pagination .= "<li><a href=\"" . $targetpage . "page=1\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=2\">2</a></li>";
                        $pagination .= "<li>...</li>";
                        for( $counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++ ) 
                        {
                            if( $counter == $page ) 
                            {
                                $pagination .= "<li class=\"active\"><a href=\"#\">" . $counter . "</a></li>";
                            }
                            else
                            {
                                $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                            }

                        }
                        $pagination .= "<li>...</li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lpm1 . "\">" . $lpm1 . "</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lastpage . "\">" . $lastpage . "</a></li>";
                    }
                    else
                    {
                        $goto = true;
                        $pagination .= "<li><a href=\"" . $targetpage . "page=1\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=2\">2</a></li>";
                        $pagination .= "<li>...</li>";
                        for( $counter = $lastpage - (2 + $adjacents * 2); $counter <= $lastpage; $counter++ ) 
                        {
                            if( $counter == $page ) 
                            {
                                $pagination .= "<li class=\"active\"><a href=\"#\">" . $counter . "</a></li>";
                            }
                            else
                            {
                                $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                            }

                        }
                    }

                }

            }

        }

        if( $page < $counter - 1 ) 
        {
            $pagination .= "<li><a href=\"" . $targetpage . "page=" . $next . "\">&raquo;</a></li>";
        }

        if( isset($goto) ) 
        {
            $pagination .= "<li><a href=\"" . $targetpage . "\" id=\"gotoLink\">" . get_phrase("goto_page") . "</a></li>";
        }

        $pagination .= "</ul></div>\n";
        return array( "LIMIT " . $start . ", " . $perpage, $pagination );
    }

    return array( "LIMIT 0, " . $total_rows, "" );
}

function PaginationNoMySQL($total_rows, $perpage = 5, $targetpage = "", $adjacents = 3)
{
    global $TSUE;
    if( $total_rows < 1 || $perpage < 1 ) 
    {
        return NULL;
    }

    if( !$targetpage ) 
    {
        $targetpage = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?";
    }

    $lastpage = ceil($total_rows / $perpage);
    if( $lastpage <= 1 ) 
    {
        return NULL;
    }

    $page = (isset($_GET["page"]) ? intval($_GET["page"]) : (isset($_POST["page"]) ? intval($_POST["page"]) : 0));
    if( $lastpage < $page ) 
    {
        $page = $lastpage;
    }

    if( $page ) 
    {
        $start = ($page - 1) * $perpage;
    }
    else
    {
        $start = 0;
    }

    if( $page == 0 ) 
    {
        $page = 1;
    }

    $lpm1 = $lastpage - 1;
    $pagination = "";
    if( 1 < $lastpage ) 
    {
        $pagination .= "<div class=\"paginationNoMySQL\"><ul>";
        if( $lastpage < 7 + $adjacents * 2 ) 
        {
            for( $counter = 1; $counter <= $lastpage; $counter++ ) 
            {
                $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
            }
        }
        else
        {
            if( 5 + $adjacents * 2 < $lastpage ) 
            {
                if( $page < 1 + $adjacents * 2 ) 
                {
                    for( $counter = 1; $counter < 4 + $adjacents * 2; $counter++ ) 
                    {
                        $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                    }
                    $pagination .= "<li>...</li>";
                    $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lpm1 . "\">" . $lpm1 . "</a></li>";
                    $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lastpage . "\">" . $lastpage . "</a></li>";
                }
                else
                {
                    if( $page < $lastpage - $adjacents * 2 && $adjacents * 2 < $page ) 
                    {
                        $pagination .= "<li><a href=\"" . $targetpage . "page=1\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=2\">2</a></li>";
                        $pagination .= "<li>...</li>";
                        for( $counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++ ) 
                        {
                            $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                        }
                        $pagination .= "<li>...</li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lpm1 . "\">" . $lpm1 . "</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=" . $lastpage . "\">" . $lastpage . "</a></li>";
                    }
                    else
                    {
                        $pagination .= "<li><a href=\"" . $targetpage . "page=1\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $targetpage . "page=2\">2</a></li>";
                        $pagination .= "<li>...</li>";
                        for( $counter = $lastpage - (2 + $adjacents * 2); $counter <= $lastpage; $counter++ ) 
                        {
                            $pagination .= "<li><a href=\"" . $targetpage . "page=" . $counter . "\">" . $counter . "</a></li>";
                        }
                    }

                }

            }

        }

        $pagination .= "</ul></div>\n";
        return $pagination;
    }

}

function translate_location($location = "", $http_referer = "", $query_string = "")
{
    global $TSUE;
    $Locations = array( "profile" => get_phrase("navigation_member_profile"), "online" => get_phrase("page_online"), "membercp" => get_phrase("page_membercp"), "admincp" => get_phrase("page_admincp") );
    $location = str_replace(".php", "", $location);
    $LINK = "";
    if( has_permission("canview_special_details") && preg_match("|^p=.*&.*\$|", $query_string) ) 
    {
        $LINK = getsetting("global_settings", "website_url") . "/?" . strip_tags($query_string);
    }

    if( isset($TSUE["TSUE_Language"]->phrase["navigation_" . $location]) ) 
    {
        $Phrase = $TSUE["TSUE_Language"]->phrase["navigation_" . $location];
    }
    else
    {
        $Phrase = (isset($Locations[$location]) ? $Locations[$location] : strip_tags($location));
    }

    $referer = "";
    if( has_permission("canview_special_details") && $http_referer && !preg_match("#admincp#", $http_referer) && $http_referer != "--" ) 
    {
        $referer = " [<a href=\"" . strip_tags($http_referer) . "\" target=\"_blank\">" . get_phrase("referer") . "</a>] ";
    }

    if( $LINK ) 
    {
        $Phrase = "<a href=\"" . $LINK . "\" target=\"_blank\">" . $Phrase . "</a>";
    }

    return $Phrase . $referer;
}

function is_following($memberid = 0, $follow_memberid = 0)
{
    global $TSUE;
    return (!$memberid || !$follow_memberid ? false : $TSUE["TSUE_Database"]->query_result("SELECT memberid FROM tsue_member_follow WHERE memberid = " . $TSUE["TSUE_Database"]->escape($memberid) . " AND follow_memberid = " . $TSUE["TSUE_Database"]->escape($follow_memberid)));
}

function save_strike()
{
    global $TSUE;
    if( !getsetting("global_settings", "website_use_strike_system") ) 
    {
        return NULL;
    }

    $BuildQuery = array( "striketime" => TIMENOW, "strikeip" => MEMBER_IP );
    $TSUE["TSUE_Database"]->insert("tsue_strikes", $BuildQuery);
}

function check_strikes()
{
    global $TSUE;
    $limit_of_login_strikes = getsetting("global_settings", "limit_of_login_strikes", 5);
    if( !getsetting("global_settings", "website_use_strike_system") ) 
    {
        return 0;
    }

    $TSUE["TSUE_Database"]->query("DELETE FROM tsue_strikes WHERE striketime < " . (TIMENOW - 900));
    $strikes = $TSUE["TSUE_Database"]->query_result("\r\n\t\tSELECT COUNT(*) AS strikes, MAX(striketime) AS lasttime\r\n\t\tFROM tsue_strikes\r\n\t\tWHERE strikeip = " . $TSUE["TSUE_Database"]->escape(MEMBER_IP));
    return ($limit_of_login_strikes <= $strikes["strikes"] && TIMENOW - 900 < $strikes["lasttime"] ? $limit_of_login_strikes + 1 : $strikes["strikes"]);
}

function log_strike($receiver_memberid = 0, $membername, $password = "")
{
    global $TSUE;
    if( !$receiver_memberid ) 
    {
        return NULL;
    }

    $message = nl2br(get_phrase("failed_login_message", $membername, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], MEMBER_IP, $password));
    sendPM(get_phrase("failed_login_subject"), $receiver_memberid, $receiver_memberid, $message, 1);
}

function cookie_set($name = "", $value = "", $expires = 0)
{
    global $TSUE;
    setcookie($name, $value, $expires, $TSUE["TSUE_Settings"]->settings["global_settings"]["cookie_path"], $TSUE["TSUE_Settings"]->settings["global_settings"]["cookie_domain"]);
}

function setLoginCookie($remember_me, $memberid, $password, $md5 = true)
{
    global $TSUE;
    $cookieTimeout = TIMENOW + (($remember_me ? 604800 : $TSUE["TSUE_Settings"]->settings["global_settings"]["website_timeout"] * 60));
    cookie_set("tsue_member", base64_encode((($remember_me ? "1" : "0")) . "_" . $memberid . "_" . (($md5 ? md5($password) : $password)) . "_" . md5(MEMBER_IP)), $cookieTimeout);
}

function redirect($page)
{
    global $TSUE;
    header("Location: " . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . $page);
    exit();
}

function logOut()
{
    global $TSUE;
    $TSUE["TSUE_Database"]->query("DELETE FROM tsue_session WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    $timeout = TIMENOW - 365 * 24 * 60 * 60;
    cookie_set("tsue_member", md5(TIMENOW), $timeout);
    cookie_set("testPermissions", NULL, $timeout);
    redirect("?p=home&pid=1");
}

function generatePasskey($length = 30)
{
    $passkey = "";
    for( $i = 0; $i < $length; $i++ ) 
    {
        $passkey .= chr(rand(33, 126));
    }
    return sha1($passkey);
}

function isValidToken($token = "", $dontCheckForGuests = true)
{
    global $TSUE;
    if( !$TSUE["TSUE_Member"]->info["memberid"] && $dontCheckForGuests ) 
    {
        return true;
    }

    $token = strval($token);
    $csrfAttempt = "invalid";
    if( $token === "" ) 
    {
        $csrfAttempt = "missing";
    }

    $tokenParts = tsue_explode("-", $token);
    if( count($tokenParts) == 3 ) 
    {
        list($tokenMemberid, $tokenTime, $tokenValue) = $tokenParts;
        if( strval($tokenMemberid) === strval($TSUE["TSUE_Member"]->info["memberid"]) ) 
        {
            if( $tokenTime + 86400 < TIMENOW ) 
            {
                $csrfAttempt = "expired";
            }
            else
            {
                if( sha1($tokenTime . $TSUE["TSUE_Member"]->info["csrf_token"]) == $tokenValue ) 
                {
                    $csrfAttempt = false;
                }

            }

        }

    }

    if( $csrfAttempt ) 
    {
        return false;
    }

    return true;
}

function getServer($key = NULL, $default = NULL)
{
    if( NULL === $key ) 
    {
        return $_SERVER;
    }

    return (isset($_SERVER[$key]) ? $_SERVER[$key] : $default);
}

function getClientIps()
{
    $ips = preg_split("/,\\s*/", getClientIp(true));
    $ips[] = getClientIp(false);
    return array_unique($ips);
}

function getClientIp($checkProxy = true)
{
    if( $checkProxy && getserver("HTTP_CLIENT_IP") != NULL ) 
    {
        $ip = getserver("HTTP_CLIENT_IP");
    }
    else
    {
        if( $checkProxy && getserver("HTTP_X_FORWARDED_FOR") != NULL ) 
        {
            $ip = getserver("HTTP_X_FORWARDED_FOR");
        }
        else
        {
            $ip = getserver("REMOTE_ADDR");
        }

    }

    return strip_tags($ip);
}

function ipMatch($checkIps, array $ipList)
{
    if( !is_array($checkIps) ) 
    {
        $checkIps = array( $checkIps );
    }

    foreach( $checkIps as $ip ) 
    {
        $ipClassABlock = intval($ip);
        $long = sprintf("%u", ip2long($ip));
        if( isset($ipList[$ipClassABlock]) ) 
        {
            foreach( $ipList[$ipClassABlock] as $range ) 
            {
                if( $range[0] <= $long && $long <= $range[1] ) 
                {
                    return true;
                }

            }
        }

    }
    return false;
}

function getBanReason()
{
    global $TSUE;
    $bannedUser = $TSUE["TSUE_Database"]->query_result("SELECT end_date, reason FROM tsue_member_bans WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    if( !$bannedUser ) 
    {
        return get_phrase("permission_denied_banned");
    }

    if( $bannedUser["reason"] ) 
    {
        $message = get_phrase("permission_denied_banned_reason", $bannedUser["reason"]);
    }
    else
    {
        $message = get_phrase("permission_denied_banned");
    }

    if( TIMENOW < $bannedUser["end_date"] ) 
    {
        $message .= " " . get_phrase("permission_denied_banned_until", convert_time($bannedUser["end_date"]));
    }

    return $message;
}

function isIPBanned()
{
    global $TSUE;
    return !empty($TSUE["TSUE_Settings"]->settings["banned_ips_cache"]) && ipmatch(getclientips(), $TSUE["TSUE_Settings"]->settings["banned_ips_cache"]);
}

function is_valid_email($email)
{
    return !isEmailBanned($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_domain($url)
{
    return preg_match("/^(http|https|ftp):\\/\\/([A-Z0-9][A-Z0-9_-]*(?:\\.[A-Z0-9][A-Z0-9_-]*)+):?(\\d+)?\\/?/i", $url);
}

function isEmailBanned($email)
{
    global $TSUE;
    if( !empty($TSUE["TSUE_Settings"]->settings["banned_emails_cache"]) ) 
    {
        foreach( $TSUE["TSUE_Settings"]->settings["banned_emails_cache"] as $bannedEmail ) 
        {
            $bannedEmail = str_replace("\\*", "(.*)", preg_quote($bannedEmail, "/"));
            if( preg_match("/^" . $bannedEmail . "\$/", $email) ) 
            {
                return true;
            }

        }
    }

    return false;
}

function sendPM($subject, $owner_memberid, $receiver_memberid, $reply, $viaAdminCP = 0)
{
    global $TSUE;
    $BuildQuery = array( "subject" => $subject, "owner_memberid" => $owner_memberid, "receiver_memberid" => $receiver_memberid, "message_date" => TIMENOW, "is_unread" => 1, "viaAdminCP" => intval($viaAdminCP) );
    if( $TSUE["TSUE_Database"]->insert("tsue_messages_master", $BuildQuery) ) 
    {
        $message_id = $TSUE["TSUE_Database"]->insert_id();
        if( $message_id ) 
        {
            $BuildQuery = array( "message_id" => $message_id, "memberid" => $owner_memberid, "reply_date" => TIMENOW, "reply" => $reply );
            if( $TSUE["TSUE_Database"]->insert("tsue_messages_replies", $BuildQuery) ) 
            {
                $TSUE["TSUE_Database"]->update("tsue_members", array( "unread_messages" => array( "escape" => 0, "value" => "unread_messages + 1" ) ), "memberid = " . $TSUE["TSUE_Database"]->escape($receiver_memberid));
                $Member = $TSUE["TSUE_Database"]->query_result("SELECT m.membername, m.email, g.permissions, p.receive_pm_email FROM tsue_members m INNER JOIN tsue_membergroups g USING(membergroupid) INNER JOIN tsue_member_privacy p USING(memberid) WHERE m.memberid = " . $TSUE["TSUE_Database"]->escape($receiver_memberid));
                if( $Member && has_permission("canreceive_pm_email", $Member["permissions"]) && $Member["receive_pm_email"] ) 
                {
                    $readPMLink = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=messages&pid=20&message_id=" . $message_id;
                    $emailSubject = get_phrase("pm_email_subject", $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]);
                    $emailMessage = get_phrase("pm_email_body", $Member["membername"], $subject, $readPMLink, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"]);
                    sent_mail($Member["email"], $Member["membername"], $emailSubject, $emailMessage);
                }

                return $message_id;
            }

        }

    }

    return false;
}

function sent_mail($to, $toname = "", $subject, $message, $from = "", $fromname = "", $nl2br = 1, $useTemplate = 1)
{
    global $TSUE;
    $from = ($from ? $from : getsetting("global_settings", "website_sendmail_from"));
    $fromname = ($fromname ? $fromname : getsetting("global_settings", "website_title"));
    if( $nl2br ) 
    {
        $message = nl2br($message);
    }

    if( $useTemplate ) 
    {
        eval("\$message = \"" . $TSUE["TSUE_Template"]->LoadTemplate("email_template") . "\";");
    }

    if( getsetting("global_settings", "mail_type") == 1 ) 
    {
        require_once(REALPATH . "library/classes/class_smtp.php");
        $TSUEMAIL = new TSUESMTP();
        $TSUEMAIL->start($to, $toname, $subject, $message, $from, $fromname);
        return $TSUEMAIL->send();
    }

    $mid = generate_random_string(32);
    $name = $_SERVER["SERVER_NAME"];
    // $headers = "To: " . $toname . " <" . $to . ">" . PHP_EOL;
    $headers .= "From: " . $fromname . " <" . $from . ">" . PHP_EOL;
    $headers .= "Reply-To: " . $fromname . " <" . $from . ">" . PHP_EOL;
    $headers .= "Return-Path: " . $fromname . " <" . $from . ">" . PHP_EOL;
    $headers .= "Message-ID: <" . $mid . " thesystem@" . $name . ">" . PHP_EOL;
    $headers .= "X-Mailer: PHP v" . phpversion() . PHP_EOL;
    $headers .= "Content-Transfer-Encoding: 8bit" . PHP_EOL;
    $headers .= "X-Sender: TSUE PHP-Mailer" . PHP_EOL;
    $headers .= "MIME-Version: 1.0" . PHP_EOL;
    $headers .= "Content-type: text/html; charset=" . $TSUE["TSUE_Language"]->charset . PHP_EOL;
    @ini_set("sendmail_from", $from);
    return @mail($to, $subject, $message, $headers, (@getsetting("global_settings", "use_f_parameter") ? "-f " . $from : ""));
}

function profileUpdate($memberid, $buildQuery)
{
    global $TSUE;
    return $TSUE["TSUE_Database"]->update("tsue_member_profile", $buildQuery, "memberid=" . $TSUE["TSUE_Database"]->escape($memberid), false);
}

function updateMemberPoints($addPoint, $memberid, $Increase = true)
{
    global $TSUE;
    if( $addPoint ) 
    {
        if( $Increase ) 
        {
            $points = array( "escape" => 0, "value" => "points + " . $addPoint );
        }
        else
        {
            $points = array( "escape" => 0, "value" => "IF(points > " . $addPoint . ", points-" . $addPoint . ", 0)" );
        }

        return profileupdate($memberid, array( "points" => $points ));
    }

}

function memberSignature($signature = "")
{
    global $TSUE;
    if( trim($signature) == "" ) 
    {
        return "";
    }

    $memberSignature = $TSUE["TSUE_Parser"]->parse($signature);
    eval("\$memberSignature = \"" . $TSUE["TSUE_Template"]->LoadTemplate("memberSignature") . "\";");
    return $memberSignature;
}

function countryFlag($flagName = "")
{
    global $TSUE;
    if( $flagName && is_file(REALPATH . "/data/countryFlags/" . $flagName . ".png") ) 
    {
        return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/countryFlags/" . $flagName . ".png";
    }

    return $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/countryFlags/noFlag.png";
}

function countryList($divID = "countrySelect")
{
    global $TSUE;
    if( !($countrySelect = $TSUE["TSUE_Cache"]->readCache($divID)) ) 
    {
        $countryList = "";
        $flagPath = REALPATH . "/data/countryFlags/";
        $Flags = scandir($flagPath);
        foreach( $Flags as $flagName ) 
        {
            if( file_extension($flagName) == "png" && $flagName != "noFlag.png" ) 
            {
                $countryID = str_replace(".png", "", $flagName);
                $countryFlag = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/countryFlags/" . $flagName;
                eval("\$countryList .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("countryList") . "\";");
            }

        }
        unset($Flags);
        eval("\$countrySelect = \"" . $TSUE["TSUE_Template"]->LoadTemplate("countrySelect") . "\";");
        $TSUE["TSUE_Cache"]->saveCache($divID, $countrySelect);
    }

    return $countrySelect;
}

function is_valid_image($filename = "")
{
    return (trim($filename) != "" && in_array(file_extension($filename), array( "gif", "jpg", "jpeg", "png", "bmp" )) ? true : false);
}

function tsue_explode($pattern = ",", $subject = "")
{
    return preg_split("#[" . $pattern . "]+#", $subject, -1, PREG_SPLIT_NO_EMPTY);
}

function shoutboxAnnouncement($announce)
{
    global $TSUE;
    switch( $announce["0"] ) 
    {
        case "new_member":
            if( getsetting("shoutbox", "announce_new_members") ) 
            {
                list($_type, $_memberid, $_membername) = $announce;
                eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                $smessage = get_phrase("shoutbox_announcement_new_member", $member_info_link);
            }

            break;
        case "new_torrent":
            if( getsetting("shoutbox", "announce_new_torrents") ) 
            {
                $announce_new_torrent_categories = tsue_explode(",", getsetting("shoutbox", "announce_new_torrent_categories"));
                list($_type, $_tid, $_name, $_shortDescription, $cid) = $announce;
                if( empty($announce_new_torrent_categories) || $announce_new_torrent_categories && in_array($cid, $announce_new_torrent_categories) ) 
                {
                    $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.name, t.size, t.added, t.owner, t.options, m.membername, g.groupstyle, a.filename, i.content as IMDBContent, c.cname\r\n\t\t\t\t\tFROM tsue_torrents t \r\n\t\t\t\t\tLEFT JOIN tsue_members m ON(t.owner=m.memberid)\r\n\t\t\t\t\tLEFT JOIN tsue_membergroups g USING(membergroupid) \r\n\t\t\t\t\tLEFT JOIN tsue_attachments a ON (a.content_type='torrent_images' AND a.content_id=t.tid) \r\n\t\t\t\t\tLEFT JOIN tsue_imdb i USING(tid) \r\n\t\t\t\t\tLEFT JOIN tsue_torrents_categories c USING(cid)\r\n\t\t\t\t\tWHERE t.tid = " . $TSUE["TSUE_Database"]->escape($_tid));
                    if( $Torrent ) 
                    {
                        if( !is_valid_image($Torrent["filename"]) && $Torrent["IMDBContent"] ) 
                        {
                            $IMDBContent = unserialize($Torrent["IMDBContent"]);
                            if( is_file(REALPATH . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg") ) 
                            {
                                $_preview = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/imdb/" . $IMDBContent["title_id"] . ".jpg";
                            }

                        }
                        else
                        {
                            if( $Torrent["filename"] ) 
                            {
                                $_preview = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/data/torrents/torrent_images/s/" . $Torrent["filename"];
                            }

                        }

                    }

                    if( !isset($_preview) ) 
                    {
                        $_preview = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $TSUE["TSUE_Template"]->ThemeName . "/torrents/torrent_s.png";
                    }

                    require_once(REALPATH . "/library/functions/functions_getTorrents.php");
                    $Torrent["options"] = unserialize($Torrent["options"]);
                    $_memberid = $Torrent["owner"];
                    $_membername = getmembername($Torrent["membername"], $Torrent["groupstyle"]);
                    if( isAnonymouse($Torrent) ) 
                    {
                        $_memberid = 0;
                        $_membername = get_phrase("torrents_anonymouse_uploader");
                    }

                    eval("\$membername= \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    $_added = convert_time($Torrent["added"]);
                    $_size = friendly_size($Torrent["size"]);
                    $_category = $Torrent["cname"];
                    $_shortDescription = "<span class=\"small\">" . $_added . "<br />(" . $_size . ") " . $_category . "</span>";
                    $_phrase = get_phrase("shoutbox_announcement_new_torrent", $_name);
                    eval("\$smessage = \"" . $TSUE["TSUE_Template"]->LoadTemplate("shoutbox_announcement_new_torrent") . "\";");
                }

            }

            break;
        case "new_thread":
            if( getsetting("shoutbox", "announce_new_threads") ) 
            {
                list($_type, $_memberid, $_membername, $_groupstyle, $fid, $tid, $threadTitle) = $announce;
                if( !in_array($fid, explode(",", getsetting("shoutbox", "announce_new_threads_skip_forums"))) ) 
                {
                    $_membername = getmembername($_membername, $_groupstyle);
                    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    $smessage = get_phrase("shoutbox_announcement_new_thread", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $fid . "&amp;tid=" . $tid, $threadTitle);
                }

            }

            break;
        case "new_forum_reply":
            if( getsetting("shoutbox", "announce_new_replies") ) 
            {
                list($_type, $_memberid, $_membername, $_groupstyle, $fid, $tid, $threadTitle, $postid) = $announce;
                if( !in_array($fid, explode(",", getsetting("shoutbox", "announce_new_threads_skip_forums"))) ) 
                {
                    $_membername = getmembername($_membername, $_groupstyle);
                    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    $smessage = get_phrase("shoutbox_announcement_new_reply", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=forums&amp;pid=11&amp;fid=" . $fid . "&amp;tid=" . $tid . "&amp;postid=" . $postid . "#show_post_" . $postid, $threadTitle);
                }

            }

            break;
        case "new_torrent_comment":
            if( getsetting("shoutbox", "announce_new_torrent_comments") ) 
            {
                list($_type, $_memberid, $_membername, $_groupstyle, $cid, $tid, $torrentName) = $announce;
                $announce_new_torrent_categories = tsue_explode(",", getsetting("shoutbox", "announce_new_torrent_categories"));
                if( empty($announce_new_torrent_categories) || $announce_new_torrent_categories && in_array($cid, $announce_new_torrent_categories) ) 
                {
                    $_membername = getmembername($_membername, $_groupstyle);
                    eval("\$member_info_link = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
                    $smessage = get_phrase("shoutbox_announcement_new_comment", $member_info_link, $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&amp;pid=10&amp;action=details&amp;tid=" . $tid . "#torrent_comments", $torrentName);
                }

            }

    }
    if( isset($smessage) ) 
    {
        $TSUE["TSUE_Database"]->insert("tsue_shoutbox", array( "memberid" => 0, "system" => 1, "sdate" => TIMENOW, "smessage" => $smessage, "cid" => 1 ));
    }

}

function ircAnnouncement($content_type, $content_id, $content_name)
{
    global $TSUE;
    if( !getsetting("ircbot", "active") ) 
    {
        return NULL;
    }

    switch( $content_type ) 
    {
        case "new_member":
            $smessage = get_phrase("shoutbox_announcement_new_member", "(" . strip_tags($content_name) . ") " . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=profile&pid=18&memberid=" . $content_id);
            break;
        case "new_torrent":
            $Torrent = $TSUE["TSUE_Database"]->query_result("SELECT t.size, c.cname FROM tsue_torrents t INNER JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = " . $TSUE["TSUE_Database"]->escape($content_id));
            if( $Torrent ) 
            {
                $smessage = get_phrase("shoutbox_announcement_new_torrent_v2", strip_tags($content_name), $Torrent["cname"], friendly_size($Torrent["size"]), $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=torrents&pid=10&action=details&tid=" . $content_id);
            }

            break;
    }
    if( isset($smessage) ) 
    {
        require_once(REALPATH . "library/classes/class_ircbot.php");
        $TSUE_IRCBot = new TSUE_IRCBot($smessage);
    }

}

function globalize($sg, $variables)
{
    $SuperGlobals = array( "post" => "_POST", "get" => "_GET", "cookie" => "_COOKIE", "server" => "_SERVER", "files" => "_FILES" );
    foreach( $variables as $name => $cleantype ) 
    {
        $GLOBALS[$name] = "";
        if( is_array($sg) ) 
        {
            foreach( $sg as $searchSG ) 
            {
                if( isset($GLOBALS[$SuperGlobals[strtolower($searchSG)]][$name]) && empty($GLOBALS[$name]) ) 
                {
                    $GLOBALS[$name] = $GLOBALS[$SuperGlobals[strtolower($searchSG)]][$name];
                }

            }
        }
        else
        {
            if( empty($GLOBALS[$name]) ) 
            {
                $GLOBALS[$name] = (isset($GLOBALS[$SuperGlobals[strtolower($sg)]][$name]) ? $GLOBALS[$SuperGlobals[strtolower($sg)]][$name] : "");
            }

        }

        if( !empty($GLOBALS[$name]) ) 
        {
            $cleantype = strtoupper($cleantype);
            switch( $cleantype ) 
            {
                case "INT":
                    $GLOBALS[$name] = (int)$GLOBALS[$name];
                    break;
                case "HTML":
                    $GLOBALS[$name] = html_clean($GLOBALS[$name]);
                    break;
                case "STRIP":
                    $GLOBALS[$name] = strip_tags($GLOBALS[$name]);
                    break;
                case "TRIM":
                    $GLOBALS[$name] = trim($GLOBALS[$name]);
                    break;
                case "DECODE":
                    $GLOBALS[$name] = html_clean(trim(urldecode($GLOBALS[$name])));
                    break;
                case "ARRAY":
                case "default":
                    $GLOBALS[$name] = $GLOBALS[$name];
                    break;
            }
        }
        else
        {
            if( $cleantype == "INT" ) 
            {
                $GLOBALS[$name] = 0;
            }

        }

    }
    unset($variables);
}

function html_clean($text = "", $allowable_tags = "")
{
    return ($text ? htmlspecialchars($text) : "");
}

function html_declean($text)
{
    return str_replace(array( "&lt;", "&gt;", "&quot;", "&#039;", "&amp;", "&nbsp;" ), array( "<", ">", "\"", "'", "&", " " ), $text);
}

function get_phrase()
{
    global $TSUE;
    $args = func_get_args();
    $args[0] = preg_replace("#\\{([0-9]+)\\}#sU", "%\\1\$s", $TSUE["TSUE_Language"]->phrase[$args[0]]);
    $numargs = sizeof($args);
    if( $numargs == 1 && is_array($args[0]) ) 
    {
        return construct_phrase_from_array($args[0]);
    }

    if( $numargs == 2 && is_string($args[0]) && is_array($args[1]) ) 
    {
        array_unshift($args[1], $args[0]);
        return construct_phrase_from_array($args[1]);
    }

    return construct_phrase_from_array($args);
}

function construct_phrase_from_array($phrase_array)
{
    $numargs = sizeof($phrase_array);
    if( $numargs < 2 ) 
    {
        return $phrase_array[0];
    }

    $phrase = @call_user_func_array("sprintf", $phrase_array);
    if( $phrase !== false ) 
    {
        return $phrase;
    }

    for( $i = $numargs; $i < 10; $i++ ) 
    {
        $phrase_array[(string) $i] = "[ARG:" . $i . " UNDEFINED]";
    }
    if( $phrase = @call_user_func_array("sprintf", $phrase_array) ) 
    {
        return $phrase;
    }

    return $phrase_array[0];
}

function generate_random_string($length = 7, $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz234567890")
{
    $chars_length = strlen($chars) - 1;
    $string = $chars[rand(0, $chars_length)];
    $i = 1;
    while( $i < $length ) 
    {
        $r = $chars[rand(0, $chars_length)];
        if( $r != $string[$i - 1] ) 
        {
            $string .= $r;
        }

        $i = strlen($string);
    }
    return $string;
}

function convert_time($timestamp, $format = "")
{
    global $TSUE;
    if( !$format ) 
    {
        $format = $TSUE["TSUE_Language"]->date_format . " " . $TSUE["TSUE_Language"]->time_format;
    }

    return gmdate($format, $timestamp + 3600 * ($TSUE["TSUE_Member"]->info["timezone"] + $TSUE["TSUE_Member"]->info["dst"]));
}

function convert_relative_time($timestamp = 0, $useTimeago = true)
{
    global $TSUE;
    $interval = TIMENOW - $timestamp;
    if( $interval < 0 ) 
    {
        return convert_time($timestamp);
    }

    if( $interval < 3600 && $useTimeago ) 
    {
        eval("\$timeago = \"" . $TSUE["TSUE_Template"]->LoadTemplate("timeago") . "\";");
        return $timeago;
    }

    $oneWeekAgo = TIMENOW - 86400 * 6;
    $MTimeStamp = convert_time($timestamp, "n-j-Y");
    if( $MTimeStamp == convert_time(TIMENOW, "n-j-Y") ) 
    {
        return get_phrase("relative_today_at_x", convert_time($timestamp, $TSUE["TSUE_Language"]->time_format));
    }

    if( $MTimeStamp == convert_time(TIMENOW - 86400, "n-j-Y") ) 
    {
        return get_phrase("relative_yesterday_at_x", convert_time($timestamp, $TSUE["TSUE_Language"]->time_format));
    }

    if( $oneWeekAgo <= $timestamp ) 
    {
        return get_phrase("relative_day_x_at_time_y", get_phrase("day_" . convert_time($timestamp, "N")), convert_time($timestamp, $TSUE["TSUE_Language"]->time_format));
    }

    return convert_time($timestamp);
}

function convertSeconds($stamp = 0, $showseconds = false)
{
    global $TSUE;
    $lang = tsue_explode(",", get_phrase("all_in_one_pretty_time"));
    if( !$stamp ) 
    {
        return "0 " . $lang["12"];
    }

    $ysecs = 365 * 24 * 60 * 60;
    $mosecs = 31 * 24 * 60 * 60;
    $wsecs = 7 * 24 * 60 * 60;
    $dsecs = 24 * 60 * 60;
    $hsecs = 60 * 60;
    $msecs = 60;
    $years = floor($stamp / $ysecs);
    $stamp %= $ysecs;
    $months = floor($stamp / $mosecs);
    $stamp %= $mosecs;
    $weeks = floor($stamp / $wsecs);
    $stamp %= $wsecs;
    $days = floor($stamp / $dsecs);
    $stamp %= $dsecs;
    $hours = floor($stamp / $hsecs);
    $stamp %= $hsecs;
    $minutes = floor($stamp / $msecs);
    $stamp %= $msecs;
    $seconds = $stamp;
    if( $years == 1 ) 
    {
        $timespent["years"] = "1 " . $lang["0"];
    }
    else
    {
        if( 1 < $years ) 
        {
            $timespent["years"] = $years . " " . $lang["1"];
        }

    }

    if( $months == 1 ) 
    {
        $timespent["months"] = "1 " . $lang["2"];
    }
    else
    {
        if( 1 < $months ) 
        {
            $timespent["months"] = $months . " " . $lang["3"];
        }

    }

    if( $weeks == 1 ) 
    {
        $timespent["weeks"] = "1 " . $lang["4"];
    }
    else
    {
        if( 1 < $weeks ) 
        {
            $timespent["weeks"] = $weeks . " " . $lang["5"];
        }

    }

    if( $days == 1 ) 
    {
        $timespent["days"] = "1 " . $lang["6"];
    }
    else
    {
        if( 1 < $days ) 
        {
            $timespent["days"] = $days . " " . $lang["7"];
        }

    }

    if( $hours == 1 ) 
    {
        $timespent["hours"] = "1 " . $lang["8"];
    }
    else
    {
        if( 1 < $hours ) 
        {
            $timespent["hours"] = $hours . " " . $lang["9"];
        }

    }

    if( $minutes == 1 ) 
    {
        $timespent["minutes"] = "1 " . $lang["10"];
    }
    else
    {
        if( 1 < $minutes ) 
        {
            $timespent["minutes"] = $minutes . " " . $lang["11"];
        }

    }

    if( $seconds == 1 && $showseconds ) 
    {
        $timespent["seconds"] = "1 " . $lang["12"];
    }
    else
    {
        if( 1 < $seconds && $showseconds ) 
        {
            $timespent["seconds"] = $seconds . " " . $lang["13"];
        }

    }

    if( isset($timespent) && is_array($timespent) ) 
    {
        $total = implode(", ", $timespent);
    }
    else
    {
        $total = "0 " . $lang["12"];
    }

    return $total;
}

function secondsToHours($seconds, $showMinutes = false)
{
    $lang = tsue_explode(",", get_phrase("all_in_one_pretty_time"));
    $minutes = $minutesPhrase = "";
    $hours = floor($seconds / 3600);
    $hoursPhrase = trim((1 < $hours ? $lang["9"] : $lang["8"]));
    if( $showMinutes ) 
    {
        $minutes = floor(($seconds / 60) % 60);
        $minutesPhrase = trim((1 < $minutes ? $lang["11"] : $lang["10"]));
    }

    return (string) $hours . " " . $hoursPhrase . " " . $minutes . " " . $minutesPhrase;
}

function strlenOriginalText($text)
{
    return strlen(strip_tags($text));
}

function shutdown()
{
    global $TSUE;
    if( isset($TSUE["TSUE_Cache"]) ) 
    {
        $TSUE["TSUE_Cache"]->shutdown();
    }

    if( isset($TSUE["TSUE_Database"]) ) 
    {
        $TSUE["TSUE_Database"]->exec_shutdown_queries();
    }

}

function friendlyPeerID($peer_id = "")
{
    $peer_id = trim($peer_id, "-");
    $peer_id = preg_replace("#[^a-zA-Z0-9\\-]#", "", $peer_id);
    $peer_id = explode("-", $peer_id);
    return $peer_id["0"];
}

function friendly_short_name($name, $limit)
{
    $length = strlen($name);
    if( $length <= $limit ) 
    {
        return $name;
    }

    $first = substr(strip_tags($name), 0, 10);
    $last = substr(strip_tags($name), $length - 10, $length);
    return $first . "..." . $last;
}

function safe_names($string = "", $delimer = "-")
{
    if( ctype_digit($string) || !$string ) 
    {
        return $string;
    }

    $string = preg_replace("#[^a-zA-Z0-9\\.\\" . $delimer . "]#", $delimer, $string);
    $string = preg_replace("#\\" . $delimer . "\\" . $delimer . "+#", $delimer, $string);
    return trim($string);
}

function file_extension($filename)
{
    return substr(strrchr($filename, "."), 1);
}

function is_valid_string($string)
{
    return (!preg_match("#^[a-zA-Z0-9]+\$#", $string) ? false : true);
}

function is_valid_membername($string)
{
    $matchRegex = getsetting("global_settings", "membername_match_regular_expression");
    if( $matchRegex ) 
    {
        $matchRegex = str_replace("#", "\\#", $matchRegex);
        if( !preg_match("#" . $matchRegex . "#i", $string) ) 
        {
            return false;
        }

    }

    return true;
}

function removeWhiteSpaces($text)
{
    return trim(preg_replace("/\\s+/", " ", $text));
}

function removeAllWhiteSpaces($text)
{
    return trim(preg_replace("/\\s+/", "", $text));
}

function clearTags($tags)
{
    $tags = removewhitespaces($tags);
    $tags = preg_replace("/,+/", ",", $tags);
    $tags = trim($tags, ",");
    $tags = trim($tags);
    return $tags;
}

function friendly_number_format($numbers, $decimals = 0, $dec_point = ".", $thousands_sep = ",")
{
    return number_format($numbers, $decimals, $dec_point, $thousands_sep);
}

function friendly_size($bytes = 0, $useName = true, $precision = 2)
{
    global $TSUE;
    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    $gigabyte = $megabyte * 1024;
    $terabyte = $gigabyte * 1024;
    $filesizename = @tsue_explode(",", $TSUE["TSUE_Language"]->phrase["friendly_sizes"]);
    if( 0 < $bytes && $bytes < $kilobyte ) 
    {
        return $bytes . (($useName ? " " . $filesizename["0"] : ""));
    }

    if( $kilobyte <= $bytes && $bytes < $megabyte ) 
    {
        return round($bytes / $kilobyte, $precision) . (($useName ? " " . $filesizename["1"] : ""));
    }

    if( $megabyte <= $bytes && $bytes < $gigabyte ) 
    {
        return round($bytes / $megabyte, $precision) . (($useName ? " " . $filesizename["2"] : ""));
    }

    if( $gigabyte <= $bytes && $bytes < $terabyte ) 
    {
        return round($bytes / $gigabyte, $precision) . (($useName ? " " . $filesizename["3"] : ""));
    }

    if( $terabyte <= $bytes ) 
    {
        return round($bytes / $terabyte, $precision) . (($useName ? " " . $filesizename["4"] : ""));
    }

    return strip_tags($bytes);
}

function ul_dl_stats($up = 0, $down = 0)
{
    global $TSUE;
    $_uploaded = friendly_size($TSUE["TSUE_Member"]->info["uploaded"]);
    $_downloaded = friendly_size($TSUE["TSUE_Member"]->info["downloaded"]);
    $_buffer = ($TSUE["TSUE_Member"]->info["downloaded"] < $TSUE["TSUE_Member"]->info["uploaded"] ? friendly_size($TSUE["TSUE_Member"]->info["uploaded"] - $TSUE["TSUE_Member"]->info["downloaded"]) : 0);
    $_ratio = member_ratio($TSUE["TSUE_Member"]->info["uploaded"], $TSUE["TSUE_Member"]->info["downloaded"]);
    $_points = friendly_number_format($TSUE["TSUE_Member"]->info["points"], 0, ".", ".");
    $_total_posts = friendly_number_format($TSUE["TSUE_Member"]->info["total_posts"]);
    $_invites_left = friendly_number_format($TSUE["TSUE_Member"]->info["invites_left"]);
    $_total_warns = friendly_number_format($TSUE["TSUE_Member"]->info["total_warns"]) + (($TSUE["TSUE_Member"]->info["autoWarnedDate"] ? 1 : 0));
    $_hitrun_warns = friendly_number_format($TSUE["TSUE_Member"]->info["hitRuns"]);
    $_slots = (get_permission("max_slot_limit") ? get_permission("max_slot_limit") : get_phrase("unlimited"));
    eval("\$ul_dl_stats = \"" . $TSUE["TSUE_Template"]->LoadTemplate("ul_dl_stats") . "\";");
    return $ul_dl_stats;
}

function member_info_bar()
{
    global $TSUE;
    $_uploaded = friendly_size($TSUE["TSUE_Member"]->info["uploaded"]);
    $_downloaded = friendly_size($TSUE["TSUE_Member"]->info["downloaded"]);
    $_buffer = ($TSUE["TSUE_Member"]->info["downloaded"] < $TSUE["TSUE_Member"]->info["uploaded"] ? friendly_size($TSUE["TSUE_Member"]->info["uploaded"] - $TSUE["TSUE_Member"]->info["downloaded"]) : 0);
    $_ratio = member_ratio($TSUE["TSUE_Member"]->info["uploaded"], $TSUE["TSUE_Member"]->info["downloaded"]);
    $_slots = (get_permission("max_slot_limit") ? get_permission("max_slot_limit") : get_phrase("unlimited"));
    $_points = friendly_number_format($TSUE["TSUE_Member"]->info["points"], 0, ".", ".");
    $_total_posts = friendly_number_format($TSUE["TSUE_Member"]->info["total_posts"]);
    $_invites_left = friendly_number_format($TSUE["TSUE_Member"]->info["invites_left"]);
    $_total_warns = friendly_number_format($TSUE["TSUE_Member"]->info["total_warns"]) + (($TSUE["TSUE_Member"]->info["autoWarnedDate"] ? 1 : 0));
    $_hitrun_warns = friendly_number_format($TSUE["TSUE_Member"]->info["hitRuns"]);
    eval("\$member_info_bar = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_bar") . "\";");
    return $member_info_bar;
}

function member_ratio($Uploaded = 0, $Downloaded = 0, $noHTML = false)
{
    $Ratio = 0;
    if( 0 < $Downloaded ) 
    {
        $Ratio = number_format($Uploaded / $Downloaded, 2);
    }

    if( $noHTML ) 
    {
        return $Ratio;
    }

    global $TSUE;
    $ratioColor = ($Ratio ? ($Ratio < 1 ? "ratioBad" : "ratioGood") : "ratioNull");
    eval("\$member_ratio = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_ratio") . "\";");
    return $member_ratio;
}

function explodeSearchKeywords($Match = "", $Against = "", $skipFullTextSearch = false, $useMatchBased = false)
{
    global $TSUE;
    $searchMethod = getsetting("search_system", "method", 1);
    if( $skipFullTextSearch ) 
    {
        $fullTextSearch = false;
    }
    else
    {
        if( $Match == "settingname" || $searchMethod == 1 || $useMatchBased ) 
        {
            $fullTextSearch = true;
        }
        else
        {
            $fullTextSearch = false;
        }

    }

    if( $fullTextSearch ) 
    {
        return "MATCH (" . $Match . ") AGAINST ('" . $TSUE["TSUE_Database"]->escape_no_quotes(cleanSearchText($Against)) . "' IN BOOLEAN MODE)";
    }

    $Match = explode(",", trim($Match));
    $Likes = array(  );
    foreach( $Match as $M ) 
    {
        $M = trim($M);
        $LIKES[] = (string) $M . " LIKE '%" . $TSUE["TSUE_Database"]->escape_no_quotes(cleanSearchText($Against)) . "%'";
    }
    return "(" . implode(" OR ", $LIKES) . ")";
}

function cleanSearchText($text = "")
{
    $text = trim($text);
    if( $text ) 
    {
        $text = removewhitespaces($text);
    }

    return $text;
}

function highlightString($search = "", $subject = "")
{
    $subject = trim($subject);
    $parseWords = tsue_explode(" ", $search);
    if( !empty($parseWords) && $subject ) 
    {
        foreach( $parseWords as $Word ) 
        {
            $subject = str_ireplace($Word, "<span class=\"highlightString\">" . $Word . "</span>", $subject);
        }
        unset($parseWords);
    }

    return $subject;
}

function file_icon($Filename)
{
    global $TSUE;
    $Extension = strtolower(file_extension($Filename));
    $iconPath = "styles/" . $TSUE["TSUE_Template"]->ThemeName . "/extensions/";
    if( file_exists(REALPATH . $iconPath . $Extension . ".png") ) 
    {
        return "<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . $iconPath . $Extension . ".png\" class=\"middle\" border=\"0\" title=\"" . $Extension . "\" />";
    }

    return "<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/" . $iconPath . "default.png\" class=\"middle\" border=\"0\" title=\"" . $Extension . "\" />";
}

function social_media_buttons($type = "")
{
    global $TSUE;
    $social_media_buttons = "";
    if( $TSUE["TSUE_Settings"]->settings["global_settings"]["social_media_buttons"] == 1 ) 
    {
        eval("\$social_media_buttons = \"" . $TSUE["TSUE_Template"]->LoadTemplate("social_media_buttons") . "\";");
    }

    return $social_media_buttons;
}

function buildMembergroupsStyles($showBoxes = true, $implode = " | ", $showOnStaff = false, $skipMembergroups = array( 1 ))
{
    global $TSUE;
    $List = array(  );
    $WHERE = "";
    if( $showOnStaff ) 
    {
        $WHERE = " WHERE showOnStaff = 1";
    }

    $Groups = $TSUE["TSUE_Database"]->query("SELECT membergroupid, groupname, groupstyle FROM tsue_membergroups" . $WHERE . " ORDER BY showOnStaff DESC, `sort` ASC");
    while( $G = $TSUE["TSUE_Database"]->fetch_assoc($Groups) ) 
    {
        if( !in_array($G["membergroupid"], $skipMembergroups) ) 
        {
            if( $showBoxes ) 
            {
                preg_match("#color:(.*);#Ui", $G["groupstyle"], $Color);
                if( isset($Color["1"]) && $Color["1"] ) 
                {
                    $List[] = "<span style=\"padding: 0 7px; border: 1px solid #000; margin: 1px; background: " . $Color["1"] . "\" title=\"" . $G["groupname"] . "\">&nbsp;</span>";
                }

            }
            else
            {
                $List[] = getmembername($G["groupname"], $G["groupstyle"]);
            }

        }

    }
    return implode($implode, $List);
}

function getGroupname($Array)
{
    $groupname = "";
    if( isset($Array["isBanned"]) && $Array["isBanned"] ) 
    {
        $groupname = get_phrase("banned");
    }
    else
    {
        if( isset($Array["custom_title"]) && $Array["custom_title"] ) 
        {
            $groupname = strip_tags($Array["custom_title"]);
        }
        else
        {
            if( isset($Array["groupname"]) && isset($Array["groupstyle"]) ) 
            {
                $groupname = getmembername($Array["groupname"], $Array["groupstyle"]);
            }
            else
            {
                if( isset($Array["groupname"]) ) 
                {
                    $groupname = $Array["groupname"];
                }

            }

        }

    }

    return $groupname;
}

function isMemberOnline($Member, $forceOnline = false, $useSmallImages = false, $useClass = true)
{
    global $TSUE;
    if( !$forceOnline ) 
    {
        $dateCut = TIMENOW - getsetting("global_settings", "website_timeout") * 60;
        if( !$Member["visible"] && !has_permission("canview_invisible_members") && $Member["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
        {
            $offline = true;
        }
        else
        {
            if( $Member["lastactivity"] < $dateCut && $Member["memberid"] != $TSUE["TSUE_Member"]->info["memberid"] ) 
            {
                $offline = true;
            }
            else
            {
                $offline = false;
            }

        }

    }
    else
    {
        $offline = false;
    }

    $phrase = get_phrase(($offline ? "x_is_offline" : "x_is_online"), strip_tags($Member["membername"]));
    $image = getimagesfullurl() . "status/" . (($offline ? "offline" : "online")) . (($useSmallImages ? "_small" : "")) . ".png";
    $Image = array( "src" => $image, "alt" => $phrase, "title" => $phrase, "class" => ($useClass ? ($offline ? "x_is_offline" : "x_is_online") : "middle"), "id" => "", "rel" => "resized_by_tsue" );
    return getimage($Image);
}

function get_user_browser()
{
    $u_agent = fetch_server_value("HTTP_USER_AGENT");
    if( !$u_agent ) 
    {
        return "";
    }

    $ub = "";
    if( preg_match("/MSIE/i", $u_agent) ) 
    {
        $ub = "ie";
    }
    else
    {
        if( preg_match("/Firefox/i", $u_agent) ) 
        {
            $ub = "firefox";
        }
        else
        {
            if( preg_match("/Safari/i", $u_agent) ) 
            {
                $ub = "safari";
            }
            else
            {
                if( preg_match("/Chrome/i", $u_agent) ) 
                {
                    $ub = "chrome";
                }
                else
                {
                    if( preg_match("/Flock/i", $u_agent) ) 
                    {
                        $ub = "flock";
                    }
                    else
                    {
                        if( preg_match("/Opera/i", $u_agent) ) 
                        {
                            $ub = "opera";
                        }

                    }

                }

            }

        }

    }

    return $ub;
}

function loginForm($errorMessage = "")
{
    global $TSUE;
    if( $errorMessage == get_phrase("permission_denied") && is_member_of("unregistered") ) 
    {
        $Plugin = $TSUE["TSUE_Database"]->query_result("SELECT viewpermissions,pluginOptions FROM tsue_plugins WHERE filename = 'TSUEPlugin_loginPanel.php'");
        if( $Plugin && hasviewpermission($Plugin["viewpermissions"]) ) 
        {
            if( !defined("NO_PLUGINS") ) 
            {
                define("NO_PLUGINS", true);
            }

            require_once(REALPATH . "library/plugins/TSUEPlugin_loginPanel.php");
            return TSUEPlugin_loginPanel("left", ($Plugin["pluginOptions"] ? unserialize($Plugin["pluginOptions"]) : array(  )));
        }

    }

}



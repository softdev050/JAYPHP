<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "pobieracz.php");
require("./library/init/init.php");
$Page_Title = "Odblokuj pobieranie";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", $Page_Title => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=pobieracz&amp;pid=" . PAGEID ));
$items = array( "AP.BIK8" => array( "sms" => 75068, "netto" => 5, "brutto" => 6.15, "descr" => "Mo&#380;liwo&#347;&#263; pobierania 5 miesi&#281;cy", "upgrade_id" => 1 ), "AP.TWR5" => array( "sms" => 79068, "netto" => 9, "brutto" => 11.07, "descr" => "Mo&#380;liwo&#347;&#263; pobierania 9 miesi&#281;cy", "upgrade_id" => 2 ), "AP.ZTT5" => array( "sms" => 91058, "netto" => 10, "brutto" => 12.03, "descr" => "Ranga VIP na zawsze, brak limit&oacute;w dziennych.", "upgrade_id" => 3 ) );
$Output = "";
if( strtoupper($_SERVER["REQUEST_METHOD"]) == "POST" && isset($_POST["kod"]) && !empty($_POST["kod"]) ) 
{
    $_POST["kod"] = trim($_POST["kod"]);
    if( $usluga = isvalidsmscode($_POST["kod"], $items) ) 
    {
        $Output = promoteuser($usluga, $_POST["kod"]);
    }
    else
    {
        $Output .= "<div class=\"error\">Wype&#322;nij prawid&#322;owo !</div>";
    }

}

$List = "";
foreach( $items as $code => $data ) 
{
    $List .= "\r\n\t<tr>\r\n\t\t<td style=\"padding: 3px 7px; text-align: left\">" . $code . "</td>\r\n\t\t<td style=\"padding: 3px 7px; text-align: left\">" . $data["sms"] . "</td>\r\n\t\t<td style=\"padding: 3px 7px; text-align: left\">" . $data["brutto"] . " PLN z VAT</td>\r\n\t\t<td style=\"padding: 3px 7px; text-align: left\">" . $data["netto"] . " PLN</td>\r\n\t\t<td style=\"padding: 3px 7px; text-align: left;\">" . $data["descr"] . "</td>\r\n\t</tr>";
}
$Output .= "\r\n<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" style=\"width: 100%; border: 1px solid #ddd; margin-bottom: 12px; background: #f5f5f5;\">\r\n\t<tr>\r\n\t\t<th style=\"text-align: left; font-weight: bold; color: #ffd800; padding: 7px; margin: 0px; background-color: #222222; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\">Tre&#347;&#263; SMS</th>\r\n\t\t<th style=\"text-align: left; font-weight: bold; color: #ffd800; padding: 7px; margin: 0px; background-color: #222222; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\">Numer SMS</th>\r\n\t\t<th style=\"text-align: left; font-weight: bold; color: #ffd800; padding: 7px; margin: 0px; background-color: #222222; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\">Koszt brutto</th>\r\n\t\t<th style=\"text-align: left; font-weight: bold; color: #ffd800; padding: 7px; margin: 0px; background-color: #222222; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\">Koszt netto</th>\r\n\t\t<th style=\"text-align: left; font-weight: bold; color: #ffd800; padding: 7px; margin: 0px; background-color: #222222; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\">Profity</th>\r\n\t</tr>\r\n\t" . $List . "\r\n\r\n\t<tr>\r\n\t\t<th style=\"text-align: left; background: #222222; text-align: center; color: #ffd800; padding: 7px; background-image: url('http://www.immortaltorrent.pl/style/black/header_back.gif');\" colspan=\"5\">\r\n\t\t\t<form method=\"post\">\r\n\t\t\t\tTu wpisz kod z sms-a\r\n\t\t\t\t<input type=\"text\" name=\"kod\" value=\"\" class=\"s\" style=\"width: 90px;\" /> nast&#281;pnie \r\n\t\t\t\t<input type=\"submit\" value=\"Zatwierd&#378;\" onclick=\"this.value='Zatwierd&#378;...'\" class=\"submit\" />\r\n\t\t\t</form>\r\n\t\t</th>\r\n\t</tr>\r\n</table>";
printOutput($Output, $Page_Title);
function isValidSmsCode($kod = "", $items = array(  ))
{
    foreach( $items as $usluga => $data ) 
    {
        $content = file("https://ssl.dotpay.pl/check_code.php?id=71775&code=" . substr($usluga, 3) . "&check=" . $kod . "&del=1&type=sms");
        $usluga = (isset($content["0"]) && (int) $content["0"] == 1 && isset($content["2"]) ? $content["2"] : false);
        if( $usluga ) 
        {
            return $data;
        }

    }
    return false;
}

function generateHash()
{
    global $TSUE;
    $hash = substr(md5(time() . generatePasskey(10) . time()), 0, 30);
    while( $TSUE["TSUE_Database"]->query_result("SELECT history_id FROM tsue_member_upgrades_purchases WHERE hash = " . $TSUE["TSUE_Database"]->escape($hash)) ) 
    {
        $hash = generateHash();
    }
    return $hash;
}

function promoteUser($usluga, $kod)
{
    global $TSUE;
    $hash = generatehash();
    $BuildQuery = array( "hash" => $hash, "upgrade_id" => $usluga["upgrade_id"], "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "completed" => 1, "created" => TIMENOW );
    $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_purchases", $BuildQuery);
    $history_id = $TSUE["TSUE_Database"]->insert_id();
    $BuildQuery = array( "history_id" => $history_id, "api_id" => 3, "txn_id" => $kod, "amount" => $usluga["netto"], "currency" => "PLN", "state" => 1, "dateline" => TIMENOW, "full_log" => serialize($_POST) );
    $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_transaction", $BuildQuery);
    $buildPromotion = array(  );
    $buildPromotion["upgrade_id"] = $usluga["upgrade_id"];
    $buildPromotion["history_id"] = $history_id;
    $buildPromotion["txn_id"] = $kod;
    $buildPromotion["start_date"] = TIMENOW;
    $Upgrade = $TSUE["TSUE_Database"]->query_result("SELECT * FROM  tsue_member_upgrades WHERE upgrade_id = " . $TSUE["TSUE_Database"]->escape($usluga["upgrade_id"]));
    if( $Upgrade["upgrade_length"] && $Upgrade["upgrade_length_type"] != "lifetime" ) 
    {
        $buildPromotion["expiry_date"] = strtotime("+" . $Upgrade["upgrade_length"] . " " . $Upgrade["upgrade_length_type"] . ((1 < $Upgrade["upgrade_length"] ? "s" : "")));
    }
    else
    {
        $buildPromotion["expiry_date"] = 0;
    }

    $buildPromotion["memberid"] = $TSUE["TSUE_Member"]->info["memberid"];
    if( $Upgrade["upgrade_membergroupid"] ) 
    {
        $previousUpgrade = $TSUE["TSUE_Database"]->query_result("SELECT old_membergroupid FROM tsue_member_upgrades_promotions WHERE memberid = " . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]) . " AND (expiry_date = 0 OR expiry_date > " . TIMENOW . ") AND active = 1 ORDER BY start_date ASC LIMIT 1");
        if( $previousUpgrade ) 
        {
            $buildPromotion["old_membergroupid"] = $previousUpgrade["old_membergroupid"];
        }
        else
        {
            $buildPromotion["old_membergroupid"] = $TSUE["TSUE_Member"]->info["membergroupid"];
        }

    }

    $buildPromotion["active"] = 1;
    $TSUE["TSUE_Database"]->insert("tsue_member_upgrades_promotions", $buildPromotion);
    $updateAccount = array(  );
    $profileUpdate = array(  );
    $updateAccount["inactivitytag"] = "0";
    $profileUpdate["muted"] = "0";
    $profileUpdate["hitRuns"] = "0";
    $TSUE["TSUE_Database"]->delete("tsue_auto_warning", "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    $TSUE["TSUE_Database"]->update("tsue_torrents_peers", array( "isWarned" => 0, "total_uploaded" => array( "escape" => 0, "value" => "IF(total_uploaded<total_downloaded,total_downloaded,total_uploaded)" ) ), "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    $TSUE["TSUE_Database"]->update("xbt_files_users", array( "isWarned" => 0, "uploaded" => array( "escape" => 0, "value" => "IF(uploaded<downloaded,downloaded,uploaded)" ) ), "uid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    if( $Upgrade["upgrade_membergroupid"] ) 
    {
        $updateAccount["membergroupid"] = $Upgrade["upgrade_membergroupid"];
    }

    if( $Upgrade["upgrade_points"] ) 
    {
        $profileUpdate["points"] = array( "escape" => 0, "value" => "points+" . $Upgrade["upgrade_points"] );
    }

    if( $Upgrade["upgrade_invites"] ) 
    {
        $profileUpdate["invites_left"] = array( "escape" => 0, "value" => "invites_left+" . $Upgrade["upgrade_invites"] );
    }

    if( $Upgrade["upgrade_upload"] ) 
    {
        $profileUpdate["uploaded"] = array( "escape" => 0, "value" => "uploaded+" . $Upgrade["upgrade_upload"] * 1024 * 1024 * 1024 );
    }

    if( $updateAccount ) 
    {
        $TSUE["TSUE_Database"]->update("tsue_members", $updateAccount, "memberid=" . $TSUE["TSUE_Database"]->escape($TSUE["TSUE_Member"]->info["memberid"]));
    }

    if( $profileUpdate ) 
    {
        profileUpdate($TSUE["TSUE_Member"]->info["memberid"], $profileUpdate);
    }

    $subject = get_phrase("upgrade_thank_you_subject");
    $message = nl2br(get_phrase("upgrade_thank_you_message", $TSUE["TSUE_Member"]->info["membername"], $TSUE["TSUE_Settings"]->settings["global_settings"]["website_title"]));
    sendPM($subject, 0, $TSUE["TSUE_Member"]->info["memberid"], $message);
    return "<div class=\"success\">" . $message . "</div>";
}



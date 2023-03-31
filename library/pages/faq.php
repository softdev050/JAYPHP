<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "faq.php");
require("./library/init/init.php");
$TSUE["TSUE_Template"]->loadJavascripts(array( "faq", "forgot_password" ));
$Page_Title = get_phrase("faq_title");
$WHERE = "active = 1";
$keywords = "";
AddBreadcrumb(array( get_phrase("navigation_home") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=home&amp;pid=1", get_phrase("navigation_faq") => $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/?p=faq&amp;pid=" . PAGEID ));
if( $TSUE["action"] == "search" ) 
{
    globalize("post", array( "keywords" => "DECODE" ));
    if( $keywords ) 
    {
        $WHERE .= " AND " . explodeSearchKeywords("title,content", $keywords);
    }

}

$FAQCache = array(  );
$FAQquery = $TSUE["TSUE_Database"]->query("SELECT SQL_NO_CACHE * FROM tsue_faq WHERE " . $WHERE . " ORDER by `sort` ASC");
if( $TSUE["TSUE_Database"]->num_rows($FAQquery) ) 
{
    while( $FAQ = $TSUE["TSUE_Database"]->fetch_assoc($FAQquery) ) 
    {
        $FAQCache[$FAQ["cid"]][] = $FAQ;
    }
}

globalize("get", array( "fid" => "INT", "cid" => "INT" ));
$faq_categories = "";
$FAQCategories = $TSUE["TSUE_Database"]->query("SELECT cid, name FROM tsue_faq_categories WHERE `active` = 1 ORDER by `sort` ASC");
if( $FAQCategories ) 
{
    while( $FAQCategory = $TSUE["TSUE_Database"]->fetch_assoc($FAQCategories) ) 
    {
        $faq_item_list = "";
        if( isset($FAQCache[$FAQCategory["cid"]]) ) 
        {
            foreach( $FAQCache[$FAQCategory["cid"]] as $_cid => $_item ) 
            {
                if( $fid == $_item["fid"] ) 
                {
                    $itemClass = "";
                }
                else
                {
                    $itemClass = "hidden";
                }

                eval("\$faq_item_list .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("faq_item_list") . "\";");
            }
            if( $cid == $FAQCategory["cid"] ) 
            {
                $categoryClass = "";
            }
            else
            {
                $categoryClass = "hidden";
            }

            eval("\$faq_categories .= \"" . $TSUE["TSUE_Template"]->LoadTemplate("faq_categories_list") . "\";");
        }

    }
}

eval("\$Output = \"" . $TSUE["TSUE_Template"]->LoadTemplate("faq_categories") . "\";");
PrintOutput($Output, $Page_Title);


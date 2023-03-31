<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function getApplications($Application = array(  ), $Count = 0, $checkboxes = "")
{
    global $TSUE;
    $_memberid = $Application["memberid"];
    $_membername = getMembername($Application["application_by_membername"], $Application["application_by_groupstyle"]);
    eval("\$Application['application_by'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
    $Application["added"] = convert_relative_time($Application["added"]);
    if( $Application["last_modified_date"] && $Application["last_modified_memberid"] ) 
    {
        $_memberid = $Application["last_modified_memberid"];
        $_membername = getMembername($Application["last_modified_membername"], $Application["last_modified_groupstyle"]);
        eval("\$Application['last_modified'] = \"" . $TSUE["TSUE_Template"]->LoadTemplate("member_info_link") . "\";");
        $Application["last_modified_date"] = convert_relative_time($Application["last_modified_date"]);
    }
    else
    {
        $Application["last_modified_date"] = "-";
        $Application["last_modified"] = $Application["last_modified_date"];
    }

    $Application["application_state"] = get_phrase("application_state_" . $Application["application_state"]);
    $tdClass = ($Count % 2 == 0 ? "secondRow" : "firstRow");
    eval("\$listApplications = \"" . $TSUE["TSUE_Template"]->LoadTemplate("applications_list_application_rows") . "\";");
    return $listApplications;
}



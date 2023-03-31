<?php 

class TSUE_Awards
{
    public $cachedMemberAwards = array(  );

    public function TSUE_Awards($memberids = "")
    {
        global $TSUE;
        $WHERE = "";
        if( $memberids ) 
        {
            if( is_array($memberids) ) 
            {
                $memberids = implode(",", array_map(array( $TSUE["TSUE_Database"], "escape" ), $memberids));
            }
            else
            {
                $memberids = intval($memberids);
            }

            $WHERE = " WHERE a.memberid IN (" . $memberids . ")";
        }

        $memberAwards = $TSUE["TSUE_Database"]->query("SELECT a.memberid, a.reason, a.date, aw.award_title, aw.award_image, m.membername AS givenbyMembername\r\n\t\tFROM tsue_awards_members a \r\n\t\tINNER JOIN tsue_awards aw USING(award_id)\r\n\t\tLEFT JOIN tsue_members m ON(m.memberid=a.givenby) \r\n\t\t" . $WHERE);
        if( $TSUE["TSUE_Database"]->num_rows($memberAwards) ) 
        {
            $defaultTheme = $TSUE["TSUE_Template"]->ThemeName;
            while( $Award = $TSUE["TSUE_Database"]->fetch_assoc($memberAwards) ) 
            {
                $award_details = get_phrase("award_details", strip_tags($Award["givenbyMembername"]), safe_names(strip_tags($Award["reason"]), " "), convert_relative_time($Award["date"], false));
                $this->cachedMemberAwards[$Award["memberid"]][] = "<img src=\"" . $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"] . "/styles/" . $defaultTheme . "/awards/" . $Award["award_image"] . "\" alt=\"" . $Award["award_title"] . "\" title=\"" . $Award["award_title"] . "<br>" . $award_details . "\" />&nbsp;";
            }
        }

    }

    public function getMemberAwards($memberid)
    {
        if( isset($this->cachedMemberAwards[$memberid]) ) 
        {
            return implode(" ", $this->cachedMemberAwards[$memberid]);
        }

    }

}



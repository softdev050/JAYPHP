<?php 
if( !defined("IN_INDEX") ) 
{
    exit();
}

define("SCRIPTNAME", "notfound.php");
require("./library/init/init.php");
PrintOutput(show_error(get_phrase("404_page_not_found"), "", false));


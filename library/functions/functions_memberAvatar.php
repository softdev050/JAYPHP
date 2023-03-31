<?php 
if( !defined("IN_INDEX") && !defined("IS_AJAX") ) 
{
    exit();
}

function prepareAvatar($NewAvatar, $AvatarPath, $memberid, $width, $height)
{
    $SizeArray = array( "s/", "m/", "l/" );
    $ExtArray = array( ".jpg", ".jpeg", ".gif", ".png" );
    foreach( $SizeArray as $Size ) 
    {
        foreach( $ExtArray as $Ext ) 
        {
            if( file_exists($AvatarPath . $Size . $memberid . $Ext) ) 
            {
                @unlink($AvatarPath . $Size . $memberid . $Ext);
            }

        }
    }
    require_once(REALPATH . "/library/classes/class_upload.php");
    $Small = new Upload($NewAvatar);
    if( $Small->uploaded ) 
    {
        $Small->image_resize = true;
        $Small->image_x = 48;
        $Small->image_y = 48;
        $Small->file_new_name_body = $memberid;
        $Small->Process($AvatarPath . "s/");
    }

    $Medium = new Upload($NewAvatar);
    if( $Medium->uploaded ) 
    {
        $Medium->image_resize = true;
        $Medium->image_x = 96;
        $Medium->image_y = 96;
        $Medium->file_new_name_body = $memberid;
        $Medium->Process($AvatarPath . "m/");
    }

    $Large = new Upload($NewAvatar);
    if( $Large->uploaded ) 
    {
        if( $width != 192 ) 
        {
            $Large->image_x = 192;
            $Large->image_ratio_y = true;
            $Large->image_resize = true;
        }

        $Large->file_new_name_body = $memberid;
        $Large->Process($AvatarPath . "l/");
    }

}



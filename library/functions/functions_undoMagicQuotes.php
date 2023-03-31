<?php 
undomagicquotes($_GET);
undomagicquotes($_POST);
undomagicquotes($_COOKIE);
undomagicquotes($_REQUEST);
function undoMagicQuotes(&$array, $depth = 0)
{
    if( 10 < $depth || !is_array($array) ) 
    {
        return NULL;
    }

    foreach( $array as $key => $value ) 
    {
        if( is_array($value) ) 
        {
            undoMagicQuotes($array[$key], $depth + 1);
        }
        else
        {
            $array[$key] = stripslashes($value);
        }

        if( is_string($key) ) 
        {
            $new_key = stripslashes($key);
            if( $new_key != $key ) 
            {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }

        }

    }
}



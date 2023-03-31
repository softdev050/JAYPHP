<?php 

class TSUE_NFO
{
    public $nfo = "";
    public $font = "terminal";

    public function TSUE_NFO($nfo = "", $font = "terminal")
    {
        $this->nfo = $nfo;
        $this->font = $font;
    }

    public function convertToPNGShowImage()
    {
        global $TSUE;
        $nfolines = explode("\n", $this->nfo);
        $font = imageloadfont(REALPATH . "library/fonts/" . $this->font . ".phpfont");
        $width = 0;
        $height = 0;
        $fontwidth = imagefontwidth($font);
        $fontheight = imagefontheight($font);
        foreach( $nfolines as $line ) 
        {
            if( $width < strlen($line) * $fontwidth ) 
            {
                $width = strlen($line) * $fontwidth;
            }

            $height += $fontheight;
        }
        $width += $fontwidth * 2;
        $height += $fontheight * 3;
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagecolortransparent($image, $white);
        $black = imagecolorallocate($image, 0, 0, 0);
        $i = $fontheight;
        foreach( $nfolines as $line ) 
        {
            imagestring($image, $font, $fontwidth, $i, $line, $black);
            $i += $fontheight;
        }
        $poweredby = $TSUE["TSUE_Settings"]->settings["global_settings"]["website_url"];
        $wid = ($width - $fontwidth * strlen($poweredby)) / 2;
        imagestring($image, $font, $wid, $i, $poweredby, $black);
        imagealphablending($image, true);
        header("Content-Type: image/png");
        imagepng($image);
        imagedestroy($image);
    }

}



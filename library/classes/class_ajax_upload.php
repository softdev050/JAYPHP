<?php 
if( !defined("IS_AJAX") ) 
{
    exit();
}


class qqUploadedFileXhr
{
    public function save($path)
    {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        if( $realSize != $this->getSize() ) 
        {
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        return true;
    }

    public function getName()
    {
        return trim($_GET["qqfile"]);
    }

    public function getSize()
    {
        return (isset($_SERVER["CONTENT_LENGTH"]) ? 0 + $_SERVER["CONTENT_LENGTH"] : 0);
    }

}


class qqUploadedFileForm
{
    public function save($path)
    {
        return move_uploaded_file($_FILES["qqfile"]["tmp_name"], $path);
    }

    public function getName()
    {
        return trim($_FILES["qqfile"]["name"]);
    }

    public function getSize()
    {
        return 0 + $_FILES["qqfile"]["size"];
    }

}


class qqFileUploader
{
    private $allowedExtensions = array(  );
    private $sizeLimit = 10485760;
    private $file = false;

    public function __construct(array $allowedExtensions = array(  ), $sizeLimit = 10485760)
    {
        $this->allowedExtensions = array_map("strtolower", $allowedExtensions);
        $this->sizeLimit = $sizeLimit;
        $this->file = (isset($_GET["qqfile"]) && !isset($_FILES["qqfile"]) ? new qqUploadedFileXhr() : new qqUploadedFileForm());
    }

    private function ini_size_to_bytes($value)
    {
        $value = str_replace(array( "b", "B" ), "", trim($value));
        $retval = intval($value);
        switch( strtolower($value[strlen($value) - 1]) ) 
        {
            case "g":
                $retval *= 1024;
            case "m":
                $retval *= 1024;
            case "k":
                $retval *= 1024;
        }
        return $retval;
    }

    public function handleUpload($content_type, $postid = 0)
    {
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo["filename"];
        $ext = strtolower($pathinfo["extension"]);
        if( $content_type == "torrent_files" ) 
        {
            if( $ext == "torrent" ) 
            {
                $uploadDirectory = REALPATH . "/data/torrents/torrent_files/";
            }
            else
            {
                if( $ext == "nfo" ) 
                {
                    $uploadDirectory = REALPATH . "/data/torrents/nfo/";
                }
                else
                {
                    $content_type = "torrent_images";
                    $uploadDirectory = REALPATH . "/data/torrents/torrent_images/l/";
                }

            }

        }
        else
        {
            if( $content_type == "torrent_screenshots" ) 
            {
                $uploadDirectory = REALPATH . "/data/torrents/torrent_images/l/";
            }
            else
            {
                $uploadDirectory = REALPATH . "/data/" . $content_type . "/";
            }

        }

        if( !is_writable($uploadDirectory) ) 
        {
            return array( "error" => get_phrase("upload_error2") );
        }

        if( !$this->file ) 
        {
            return array( "error" => get_phrase("upload_error3") );
        }

        $size = $this->file->getSize();
        if( $size == 0 ) 
        {
            return array( "error" => get_phrase("upload_error4") );
        }

        if( $this->sizeLimit < $size ) 
        {
            return array( "error" => get_phrase("upload_error5", friendly_size($this->sizeLimit)) );
        }

        if( $this->ini_size_to_bytes(ini_get("post_max_size")) < $size ) 
        {
            return array( "error" => get_phrase("upload_error1") );
        }

        if( $this->ini_size_to_bytes(ini_get("upload_max_filesize")) < $size ) 
        {
            return array( "error" => get_phrase("upload_error1") );
        }

        if( $this->allowedExtensions && !in_array($ext, $this->allowedExtensions) ) 
        {
            return array( "error" => get_phrase("upload_error6", implode(", ", $this->allowedExtensions)) );
        }

        $filename = safe_names($filename);
        while( file_exists($uploadDirectory . $filename . "." . $ext) ) 
        {
            $filename .= rand(10, 99);
        }
        if( $this->file->save($uploadDirectory . $filename . "." . $ext) ) 
        {
            global $TSUE;
            $BuildQuery = array( "content_type" => $content_type, "upload_date" => TIMENOW, "memberid" => $TSUE["TSUE_Member"]->info["memberid"], "filename" => $filename . "." . $ext, "filesize" => $size );
            if( $postid ) 
            {
                $BuildQuery["content_id"] = $postid;
                $BuildQuery["associated"] = 1;
            }

            if( $TSUE["TSUE_Database"]->insert("tsue_attachments", $BuildQuery) ) 
            {
                $attachment_id = $TSUE["TSUE_Database"]->insert_id();
                if( $content_type == "torrent_images" ) 
                {
                    require_once(REALPATH . "/library/classes/class_scaleImages.php");
                    $Image = new scaleImages(array( "uploadPath" => REALPATH . "/data/torrents/torrent_images/l/", "scalePath" => REALPATH . "/data/torrents/torrent_images/s/", "validimages" => $this->allowedExtensions, "maxWidth" => 105, "maxHeight" => 60 ));
                    $Image->uploadedFiles[] = $filename . "." . $ext;
                    $Image->scaleUploadedImages();
                    $Image = new scaleImages(array( "uploadPath" => REALPATH . "/data/torrents/torrent_images/l/", "scalePath" => REALPATH . "/data/torrents/torrent_images/m/", "validimages" => $this->allowedExtensions, "maxWidth" => 256, "maxHeight" => 192 ));
                    $Image->uploadedFiles[] = $filename . "." . $ext;
                    $Image->scaleUploadedImages();
                }
                else
                {
                    if( $content_type == "torrent_screenshots" ) 
                    {
                        require_once(REALPATH . "/library/classes/class_scaleImages.php");
                        $Image = new scaleImages(array( "uploadPath" => REALPATH . "/data/torrents/torrent_images/l/", "scalePath" => REALPATH . "/data/torrents/torrent_images/s/", "validimages" => $this->allowedExtensions, "maxWidth" => 105, "maxHeight" => 60 ));
                        $Image->uploadedFiles[] = $filename . "." . $ext;
                        $Image->scaleUploadedImages();
                    }

                }

                return array( "success" => $attachment_id );
            }

            return array( "error" => get_phrase("database_error") );
        }

        return array( "error" => get_phrase("upload_error8") );
    }

}



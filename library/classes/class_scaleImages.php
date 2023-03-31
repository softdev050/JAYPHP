<?php 

class scaleImages
{
    public $version = "1.0";
    public $uploadConfig = array( "uploadPath" => "", "scalePath" => "", "maxWidth" => 200, "maxHeight" => 200, "maxFilesize" => 3242880, "allowedFileTypes" => array( "jpg", "png", "gif", "jpeg" ) );
    public $errorDetected = false;
    public $uploadedFiles = array(  );

    public function scaleImages($Config = NULL)
    {
        if( $Config && is_array($Config) ) 
        {
            $this->uploadConfig = array_merge($this->uploadConfig, $Config);
        }

    }

    public function uploadImages($uploadedImages = NULL)
    {
        if( is_array($uploadedImages) && count($uploadedImages) ) 
        {
            foreach( $uploadedImages["error"] as $key => $error ) 
            {
                $name = $this->clearString($uploadedImages["name"][$key]);
                $secureName = htmlspecialchars($name);
                $type = $uploadedImages["type"][$key];
                $tmp_name = $uploadedImages["tmp_name"][$key];
                $size = $uploadedImages["size"][$key];
                if( UPLOAD_ERR_OK == $error ) 
                {
                    if( !$this->is_valid_filename($name) ) 
                    {
                        $this->errorDetected[] = "(" . $secureName . ") Invalid file name detected.";
                    }
                    else
                    {
                        if( !in_array($this->get_extension($name), $this->uploadConfig["allowedFileTypes"]) ) 
                        {
                            $this->errorDetected[] = "(" . $secureName . ") Invalid file extension. Allowed extensions are: " . implode(", ", $this->uploadConfig["allowedFileTypes"]) . "";
                        }
                        else
                        {
                            if( $this->uploadConfig["maxFilesize"] < $size ) 
                            {
                                $this->errorDetected[] = "(" . $secureName . ") The uploaded file exceeds the max. upload file size: " . $this->getFriendlySize($this->uploadConfig["maxFilesize"]) . "";
                            }
                            else
                            {
                                if( file_exists($this->uploadConfig["uploadPath"] . $name) ) 
                                {
                                    $name = $this->newName($name);
                                }

                                if( !move_uploaded_file($tmp_name, $this->uploadConfig["uploadPath"] . $name) ) 
                                {
                                    $this->errorDetected[] = "(" . $secureName . ") Can not move the uploaded file! Please check Chmod of upload folder.";
                                }
                                else
                                {
                                    $this->uploadedFiles[] = $name;
                                }

                            }

                        }

                    }

                }
                else
                {
                    $this->errorCodes($error, $secureName);
                }

            }
        }

        $this->printErrors();
    }

    public function printErrors()
    {
        if( $this->errorDetected ) 
        {
            $output = "";
            foreach( $this->errorDetected as $error ) 
            {
                $output .= "<div class=\"scaleImagesError\">" . $error . "</div>";
            }
            return $output;
        }

    }

    public function scaleUploadedImages()
    {
        if( !$this->uploadedFiles ) 
        {
            return false;
        }

        foreach( $this->uploadedFiles as $fileName ) 
        {
            $fullPath = $this->uploadConfig["uploadPath"] . $fileName;
            $scalePath = $this->uploadConfig["scalePath"] . $fileName;
            list($originalWidth, $originalHeight, $image_type, $attr) = getimagesize($fullPath);
            if( in_array($image_type, array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG )) ) 
            {
                if( $this->uploadConfig["maxWidth"] < $originalWidth ) 
                {
                    $newWidth = $this->uploadConfig["maxWidth"];
                    $ratio = $newWidth / $originalWidth;
                    $newHeight = $originalHeight * $ratio;
                    $image = $this->createImage($image_type, $fullPath);
                    $new_image = imagecreatetruecolor($newWidth, $newHeight);
                    if( IMAGETYPE_PNG == $image_type ) 
                    {
                        imagealphablending($new_image, false);
                        imagesavealpha($new_image, true);
                    }

                    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                    $this->saveImage($image_type, $new_image, $scalePath);
                }
                else
                {
                    if( $this->uploadConfig["maxHeight"] < $originalHeight ) 
                    {
                        $newHeight = $this->uploadConfig["maxHeight"];
                        $ratio = $newHeight / $originalHeight;
                        $newWidth = $originalWidth * $ratio;
                        $image = $this->createImage($image_type, $fullPath);
                        $new_image = imagecreatetruecolor($newWidth, $newHeight);
                        if( IMAGETYPE_PNG == $image_type ) 
                        {
                            imagealphablending($new_image, false);
                            imagesavealpha($new_image, true);
                        }

                        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                        $this->saveImage($image_type, $new_image, $scalePath);
                    }
                    else
                    {
                        if( !copy($fullPath, $scalePath) ) 
                        {
                            $this->errorDetected[] = "Failed to copy <b>" . $fileName . "</b>.";
                        }

                    }

                }

            }

        }
    }

    public function newName($name)
    {
        for( $i = 1; $i <= 100; $i++ ) 
        {
            $extension = $this->get_extension($name);
            $newName = substr_replace($name, "", -4);
            $newName = $newName . "_" . $i . "." . $extension;
            if( !file_exists($this->uploadConfig["uploadPath"] . $newName) ) 
            {
                return $newName;
            }

        }
        return $name;
    }

    public function createImage($image_type, $fullPath)
    {
        return ($image_type == IMAGETYPE_JPEG ? imagecreatefromjpeg($fullPath) : ($image_type == IMAGETYPE_GIF ? imagecreatefromgif($fullPath) : imagecreatefrompng($fullPath)));
    }

    public function saveImage($image_type, $new_image, $fullPath)
    {
        return ($image_type == IMAGETYPE_JPEG ? imagejpeg($new_image, $fullPath, 100) : ($image_type == IMAGETYPE_GIF ? imagegif($new_image, $fullPath) : imagepng($new_image, $fullPath)));
    }

    public function clearString($string)
    {
        $string = preg_replace("/\\s+/", "_", $string);
        $string = preg_replace("/[^a-zA-Z\\.0-9_]/i", "", $string);
        return trim($string);
    }

    public function is_valid_filename($filename)
    {
        return preg_match("/^[a-zA-Z\\.0-9_]+\\.[a-zA-Z]{3}\$/", $filename);
    }

    public function errorCodes($code, $secureName)
    {
        switch( $code ) 
        {
            case "1":
                $this->errorDetected[] = "(" . $secureName . ") The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                break;
            case "2":
                $this->errorDetected[] = "(" . $secureName . ") The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form: " . $this->getFriendlySize($this->uploadConfig["maxFilesize"]);
                break;
            case "3":
                $this->errorDetected[] = "(" . $secureName . ") The uploaded file was only partially uploaded.";
                break;
            case "6":
                $this->errorDetected[] = "(" . $secureName . ") Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3. ";
                break;
            case "7":
                $this->errorDetected[] = "(" . $secureName . ") Failed to write file to disk. Introduced in PHP 5.1.0.";
                break;
            case "8":
                $this->errorDetected[] = "(" . $secureName . ") A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0. ";
                break;
        }
    }

    public function get_extension($file = "")
    {
        return strtolower(substr(strrchr($file, "."), 1));
    }

    public function getFriendlySize($bytes = 0)
    {
        if( $bytes < 1000 * 1024 ) 
        {
            return number_format($bytes / 1024, 2) . " KB";
        }

        if( $bytes < 1000 * 1048576 ) 
        {
            return number_format($bytes / 1048576, 2) . " MB";
        }

        if( $bytes < 1000 * 1073741824 ) 
        {
            return number_format($bytes / 1073741824, 2) . " GB";
        }

        return number_format($bytes / 1099511627776, 2) . " TB";
    }

}



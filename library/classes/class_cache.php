<?php 

class TSUE_cache
{
    public $Expirity = 1800;
    public $cachePath = "";

    public function TSUE_cache()
    {
        global $TSUE;
        $this->cachePath = REALPATH . "data/cache/";
    }

    public function readCache($cacheHash = "")
    {
        global $TSUE;
        if( is_file($this->buildCacheFileName($cacheHash)) && TIMENOW < filemtime($this->buildCacheFileName($cacheHash)) + $this->Expirity ) 
        {
            return base64_decode(gzuncompress(file_get_contents($this->buildCacheFileName($cacheHash))));
        }

        return false;
    }

    public function saveCache($cacheHash = "", $Contents = "")
    {
        global $TSUE;
        return file_put_contents($this->buildCacheFileName($cacheHash), gzcompress(base64_encode($Contents), 9));
    }

    public function deleteCache($cacheHash = "")
    {
        global $TSUE;
        if( is_file($this->buildCacheFileName($cacheHash)) ) 
        {
            @unlink(@$this->buildCacheFileName($cacheHash));
        }

    }

    public function buildCacheFileName($cacheHash = "")
    {
        return $this->cachePath . $cacheHash . ".tsue";
    }

    public function shutdown()
    {
    }

}



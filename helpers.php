<?php
if(!function_exists('media_url_file')){
    function media_url_file($path){
        $filesystem  = config('filesystems');
        if($filesystem['default'] == 's3'){
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            return $protocol.$filesystem["disks"]['s3']['bucket'].".s3.amazonaws.com".$path;
        }else{
            // remove public because of conflicting between local and s3
            return url(str_replace("public/","",$path));
        }
    }
}
//http://asgardcms.s3.amazonaws.com/assets/media/143763990911737945-10207544882128486-5121793471703574190-n.jpg
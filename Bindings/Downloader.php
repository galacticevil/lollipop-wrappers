<?php
namespace Lollipop\Wrappers\Bindings {
    /**
     * The Vanilla Stack object is used to set up all our default bindings for our App constructor
     */
    class Downloader implements \Lollipop\Interfaces\Binding {
        
        public static function bindStack(\Lollipop\App $app) {
            // we can trigger a download anytime by calling $app->output('download', [args]);       
            $app->onOutput('download', function($file, $content_type = null, $filepath = null, $callback = null) use ($app) {
                // check whether the file exists if filepath is set
                if(!is_null($filepath) && is_file($filepath . LDS . $file)) {
                    $read = true;
                    $filesize = filesize($filepath . LDS . $file);
                } else {
                    // we need the projected filesize
                    $read = false;
                    $filesize = strlen($app->Response->body);
                }
                // content type?
                if(!is_null($content_type)) {
                    $content_type = $app->Response->content_type;
                }
                
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: public");
                header("Content-Description: File Transfer");
                header("Content-Type: " . $content_type);
                header("Content-Length: " . (string) $filesize);
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header("Content-Transfer-Encoding: binary\n");
                
                if($read) {
                    readfile($filepath . LDS . $file);
                } else {
                    // immediate outputs the response object
                    $app->trigger('render');
                    echo $app->Response;
                }
                if(is_callable($callback)) {
                    call_user_func($callback);
                }
                exit;
            });
        }
    }
}
        

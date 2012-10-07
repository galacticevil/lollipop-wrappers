<?php

namespace Lollipop\Wrappers\Bindings {

    /**
     * The Vanilla Stack object is used to set up all our default bindings for our App constructor
     */
    class Yaml implements \Lollipop\Interfaces\Binding {

        public static function bindStack(\Lollipop\App $app) {

            $app->onOutput('yaml', function() use ($app) {
                        // lets rebuild our response before sending it back
                        $response = array();
                        // always set our status code
                        $response['status_code'] = $app->Response->status;
                        $response['response'] = $app->Response->data;
                        include_once (\Lollipop::path('/libs/Spyc/spyc.php'));
                        $app->Response->content_type = 'text/plain';
                        $app->Response->body = \Spyc::YAMLDump($response);
                    });
        }

    }

}
        

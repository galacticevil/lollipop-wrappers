<?php

namespace Lollipop\Wrappers\Bindings {

    /**
     * The Vanilla Stack object is used to set up all our default bindings for our App constructor
     */
    class Vanilla implements \Lollipop\Interfaces\Binding {

        public static function bindStack(\Lollipop\App $app) {
            \Lollipop::getInstance()->Autoloader['Mustache'] = new \Lollipop\Autoloader('Mustache', '/libs/Mustache');
            // setup our content types
            $app->onOutput('xml', function() use ($app) {
                        // lets rebuild our response before sending it back
                        $response = array();
                        // always set our status code
                        $response['status_code'] = $app->Response->status;
                        $response['response'] = $app->Response->data;
                        $app->Response->body = \Lollipop\xml_encode($response, \Lollipop\Http::statusType($app->Response->status));
                    });

            $app->onOutput('json', function() use ($app) {
                        // lets rebuild our response before sending it back
                        $response = array();
                        // always set our status code
                        $response['status_code'] = $app->Response->status;
                        $response['response'] = $app->Response->data;
                        $app->Response->body = \Lollipop\json_encode($response, \Lollipop\Http::statusType($app->Response->status));
                    });

            $app->onOutput('default', 'html', function() use ($app) {
                        // lets rebuild our response before sending it back
                        $response = array();
                        // always set our status code
                        $response['status_code'] = $app->Response->status;
                        // View related
                        $response['status_message'] = \Lollipop\Http::statusMessage($app->Response->status);
                        $response['content_type'] = $app->Response->content_type;
                        $response['title'] = $app->Response->title;
                        $application['signature'] = \Lollipop::SIGNATURE;
                        $application['version'] = \Lollipop::VERSION;
                        $app->Response->data['View'] = $response;
                        $app->Response->data['App'] = $application;
                        $app->Response->data['Env'] = \Lollipop::env();
                        // grab our main template
                        $output = $app->View->render(\Lollipop::env('mode'), $app->Response->data);
                        // check our mode, and apply if necessary
                        if (\Lollipop\Http::getContentType($app->Response->content_type) == 'text') {
                            // make it all texty
                            $output = \Lollipop\textify($output);
                        }
                        // we want to set our response
                        $app->Response->body = $output;
                    });


            // set up our router event
            $app->onEvent('ready', function() use ($app) {
                        $match = $app->lookup(\Lollipop::env('url'));
                        try {
                            if ($match) {
                                // if $match is an array, we use it 
                                if ($match instanceof \Lollipop\Request) {
                                    $app->Request = $match;
                                } else if ($match instanceof \Lollipop\Error) {
                                    throw $match;
                                } else {
                                    throw new \Lollipop\Error\NotFound();
                                }
                            } else {
                                throw new \Lollipop\Error\NotFound();
                            }
                        } catch (\Lollipop\Error $e) {
                            \Lollipop::halt($e);
                        }
                    });

            $app->onEvent('prerun', function() use ($app) {
                        if ($app->Request->route) {
                            if (\Lollipop::config('APP.https') != \Lollipop::HTTPS_NEVER) {
                                if (\Lollipop::env('https') != 'on' && $app->Request->route->https) {
                                    \Lollipop::redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                                } else if (\Lollipop::env('https') == 'on' && !$app->Request->route->https) {
                                    \Lollipop::redirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                                }
                            }

                            // check for authentication before hand
                            if ($app->Request->route->auth) {
                                foreach ($app->Request->route->auth as $authenticate) {
                                    // Auth event must exist
                                    if (isset($app->__Auth[$authenticate])) {
                                        $app->auth($authenticate);
                                    }
                                }
                            }
                        }
                    });

            // on load we want to trigger our callback for this route
            $app->onEvent('run', function() use ($app) {
                        // we want to catch any output, just in case
                        if ($app->Request->route) {
                            ob_start();
                            if ($app->Request->route->callback instanceof \Closure) {
                                $x = call_user_func_array($app->Request->route->callback, $app->Request->params);
                            } else {
                                if (is_file(\Lollipop::path($app->Request->route->callback))) {
                                    // make our params available in this contexts
                                    if ($this->Request->params) {
                                        foreach ($this->Request->params as $k => $v) {
                                            $$k = $v;
                                        }
                                    }
                                    include(\Lollipop::path($app->Request->route->callback));
                                } else if (is_string($app->Request->route->callback)) {
                                    $split = explode('::', $app->Request->route->callback);
                                    if (count($split) > 1) {
                                        // its a class
                                        $obj = new $split[0]();
                                        call_user_func_array(array($obj, $split[1]), $app->Request->params);
                                        unset($split, $obj);
                                    } else {
                                        // its a function
                                        call_user_func_array($split[0], $app->Request->params);
                                        unset($split);
                                    }
                                }
                            }
                            ob_clean();
                        }
                    });

            $app->onRender(function() use ($app) {
                        // set the content type if it is set, else use the http_accept
                        if (!$app->Response->content_type) {
                            $app->Response->content_type = \Lollipop::env('accept_content_type');
                        }
                        $type = \Lollipop\Http::getContentType($app->Response->content_type);

                        if (isset($app->output_stack[$type])) {
                            $app->output($type);
                        } else {
                            $app->output('default');
                        }
                    });
        }

    }

}

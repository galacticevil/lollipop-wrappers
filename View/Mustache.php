<?php

namespace Lollipop\Wrappers\View {

    class Mustache extends \Mustache_Engine implements \Lollipop\Interfaces\View {

        public function __construct() {
            $options = array(
                'template_class_prefix' => "__Lollipop_",
                'cache' => \Lollipop::path(\Lollipop::config('Mustache.tmp_path')),
                'loader' =>
                new \Mustache_Loader_AliasLoader(
                        \Lollipop::path(\Lollipop::config('Mustache.views_path')),
                        array(),
                        array('extension' => '.html')
                ),
                'partials_loader' =>
                //new Mustache_Loader_FilesystemLoader(
                new \Mustache_Loader_AliasLoader(
                        \Lollipop::path(\Lollipop::config('Mustache.partials_path')),
                        array(),
                        array('extension' => '.html')
                ),
                'charset' => 'UTF-8'
            );
            parent::__construct($options);
        }

        // custom functions built for Mustache
        public function uses() {
            $args = func_get_args();
            $view = $args[0];
            $tag = (isset($args[1])) ? $args[1] : 'content';
            // aliases a partial tag to use a different partial template
            $loader = $this->getPartialsLoader();
            $loader->setAlias($tag, trim($view));
            unset($loader);
        }

    }

}

<?php

namespace Adept\View;

use Jshannon63\Cobalt\ContainerInterface;

class View implements ViewInterface
{
    protected $container;
    protected $config;

    public function __construct(ContainerInterface $container, $config)
    {
        $this->container = $container;
        $this->config = $config;
        $this->container->make('loader', function () {
            return new \Twig_Loader_Filesystem($this->config['template_path']);
        }, true);
        $this->container->make('renderer', function () {
            return new \Twig_Environment($this->container['loader'], ['cache' => $this->config['cache_path']]);
        }, true);
    }

    public function render($template, $variables = null)
    {
        if (!$variables) {
            $variables = [];
        }
        return $this->container['renderer']->render($template, $variables);
    }

    public function renderBlock($template, $variables)
    {
        return $this->container['renderer']->renderBlock($block_name, $variables);
    }
}

<?php

namespace Adept\View;

use Jshannon63\Cobalt\ContainerInterface;

interface ViewInterface
{
    public function __construct(ContainerInterface $container, $config);

    public function render($template, $variables = null);

    public function renderBlock($template, $variables);
}

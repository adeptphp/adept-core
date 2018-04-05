<?php

namespace Adept\Application;

use Jshannon63\Cobalt\ContainerInterface;

interface ApplicationInterface extends ContainerInterface
{
    public function __construct($path,$config);

    public function run();

    public function path();

    public function version();

    public function consoleApp();
}

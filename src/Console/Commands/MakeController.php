<?php

namespace Adept\Console\Commands;

use Adept\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

class MakeController extends Command
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('make:controller')
            ->setDescription('Creates a new controller.')
            ->setHelp('This command allows you to create a new controller...')
            ->setDefinition(
                new InputDefinition(
                    array(
                        new InputOption('name', null, InputOption::VALUE_REQUIRED,'Name of Controller to create')
                    )
                )
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output); // TODO: Change the autogenerated stub
        if($input->hasOption('name')){
            $controller = $input->getOption('name');
            $template = file_get_contents(app()->path().'/vendor/adeptphp/adept/src/Console/Templates/Controller.php');
            $namespace = config('app.controllers_namespace');
            $template = str_replace('templatenamespace', $namespace, $template);
            $template = str_replace('templateclass', $controller, $template);
            file_put_contents(config('app.controllers')[0].'/'.$controller.'.php',$template);
        }
    }
}
<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Console\Command;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class RunAllCommand
 *
 */
class RunAllCommand extends Command
{
    protected function configure()
    {
        $this->setName('all');
        $this->addArgument('plugin',InputArgument::OPTIONAL,'Run all only for this plugin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;
        $dispatcher = $container->get('dispatcher');
        $event = new GenericEvent($container,array('plugin'=>$input->getArgument('plugin')));
        $dispatcher->dispatch(ApplicationEvents::runAllCommands,$event);
    }

}
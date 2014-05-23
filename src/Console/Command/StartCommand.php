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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class StartCommand
 *
 */
class StartCommand extends Command
{
    protected function configure()
    {
        $this->setName('start');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;

        $container->set('ui.input',$input);
        $container->set('ui.output',$output);
        $container->get('logger.handler')->setOutput($output);

        $phpGuard = $container->get('phpguard');
        $phpGuard->start();
        return 0;
    }
}
<?php

namespace PhpGuard\Application\Console\Command;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpGuard\Application\Container;
use PhpGuard\Application\Interfaces\ContainerAwareInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartCommand
 *
 */
class StartCommand extends Command
{
    protected function configure()
    {
        $this->setName('start');
        $this->setDescription('Start to monitor file system changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();

        $container->get('guard')->start();
    }

}
<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Command;


use PhpGuard\Application\Console\Command;
use PhpGuard\Application\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpSpec\Console\Command\DescribeCommand as BaseCommand;

class DescribeCommand extends Command
{
    protected function configure()
    {
        $this->setName('phpspec:describe');
        $base = new BaseCommand();
        $this->setDefinition($base->getDefinition());
        $this->setHelp($base->getHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;
        $runner = new Runner();
        $runner->setContainer($container);
        $runner->setCommand('phpspec');

        $arguments = array(
            'desc',
            $input->getArgument('class')
        );
        if($output->isDecorated()){
            $arguments[] = '--ansi';
        }
        $runner->setArguments($arguments);
        $runner->run();
        return $runner->getProcessor()->getExitCode();// test
    }
}
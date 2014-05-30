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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class DescribeCommand extends Command
{
    protected function configure()
    {
        $this->setName('phpspec:describe');
        $this->addArgument('class',InputArgument::REQUIRED,'class to describe');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $arguments = array(
            'desc',
            $input->getArgument('class')
        );

        /* @var \PhpGuard\Application\Util\Runner $runner */

        $container = $this->container;
        $runner = $container->get('runner');
        $builder = new ProcessBuilder($arguments);
        $builder->setPrefix($runner->findExecutable('phpspec'));

        return $runner->run($builder)->getExitCode();
    }
}
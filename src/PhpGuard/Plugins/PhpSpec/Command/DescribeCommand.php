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
use PhpSpec\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpSpec\Console\Command\DescribeCommand as BaseCommand;

class DescribeCommand extends Command
{
    protected function configure()
    {
        $this->setName('phpspec:describe');
        //$this->addArgument('class',InputArgument::REQUIRED,'Class to describe');
        $app = new Application('PHPGUARD');

        $base = new BaseCommand();
        $this->setDefinition($base->getDefinition());
        $this->setHelp($base->getHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = './vendor/bin/phpspec desc --ansi '.$input->getArgument('class');
        passthru($command);
    }

    private function fromPhpSpec()
    {

    }
}
<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;
use PhpGuard\Application\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestApplication extends Application
{
    public function __construct()
    {
        parent::__construct();
        $this->setCatchExceptions(true);
        $this->setAutoExit(false);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $container->set('ui.input',$input);
        $container->set('ui.output',$output);
        $container->set('ui.shell',new TestShell($this->getContainer()));
        parent::doRun($input,$output);
    }
} 
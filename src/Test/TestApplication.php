<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Test;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Container;
use PhpGuard\Application\Event\GenericEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestApplication
 *
 * @package PhpGuard\Application\Test
 * @covers \PhpGuard\Application\Console\Application
 */
class TestApplication extends Application
{
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container);
        $this->setAutoExit(false);
        $this->setCatchExceptions(true);

    }

    public function setupContainer(ContainerInterface $container)
    {
        parent::setupContainer($container);
        $container->setShared('tester',function($c){
            return new ApplicationTester($c->get('ui.application'));
        });
        $container->setShared('ui.shell',function($c){
            return new TestShell($c);
        });
    }


    public function boot()
    {
        $event = new GenericEvent($this->getContainer());
        $this->getContainer()->get('dispatcher')
            ->dispatch(ApplicationEvents::initialize,$event)
        ;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->setParameter('runner.tty',false);
        return parent::doRun($input,$output);
    }


}
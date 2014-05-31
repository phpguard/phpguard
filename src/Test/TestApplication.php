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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TestApplication
 *
 * @package PhpGuard\Application\Test
 * @codeCoverageIgnore
 */
class TestApplication extends Application implements EventSubscriberInterface
{
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container);
        $this->setAutoExit(false);
        $this->setCatchExceptions(true);
    }

    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::postEvaluate => 'postEvaluate'
        );
    }

    public function postEvaluate()
    {
        $this->getContainer()->get('logger')->addDebug('stopped');
        $this->getContainer()->get('phpguard')->stop();
    }

    public function setupContainer(ContainerInterface $container)
    {
        parent::setupContainer($container);
        $container->setShared('tester',function ($c) {
            return new ApplicationTester($c->get('ui.application'));
        });
        $container->setShared('ui.shell',function ($c) {
            return new TestShell($c);
        });
        $container->setShared('dispatcher.listeners.test_application',function ($c) {
            return $c->get('ui.application');
        });
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->setParameter('runner.tty',false);
        $command = $this->getCommandName($input);
        $output->writeln('Running start: '.$command);

        return parent::doRun($input,$output);
    }

}

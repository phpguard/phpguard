<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Console;

use PhpGuard\Application\Container;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Container\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var PhpGuard
     */
    private $guard;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct()
    {
        parent::__construct('phpguard',PhpGuard::VERSION);
        $this->setupContainer();
    }


    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;
        $container->set('ui.input',$input);
        $container->set('ui.output',$output);
        $container->get('logger.handler')->setOutput($output);
        $container->get('phpguard')->loadConfiguration();

        if($input->hasParameterOption(array('--tags','-t'))){
            $tags = $input->getParameterOption(array('--tags','-t'));
            $tags = explode(',',$tags);
            $container->setParameter('filter.tags',$tags);
            $container->get('logger')->addDebug('Filtered using tags',array('tags'=>$tags));
        }

        $command = $this->getCommandName($input);
        if($command==''){
            /* @var Shell $shell */
            $shell = $container->get('ui.shell');
            if(!$shell->isRunning()){
                $shell->start();
            }
            return 0;
        }
        return parent::doRun($input, $output);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);

        $formatter = $output->getFormatter();
        $formatter->setStyle('fail',new OutputFormatterStyle('red'));
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function renderException($e, $output)
    {
        parent::renderException($e, $output);
        $this->container->get('ui.shell')->installReadlineCallback();
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $options = $definition->getOptions();

        $options['tags'] = new InputOption(
            'tags',
            't',
            InputOption::VALUE_OPTIONAL,
            'Run only for this tags'
        );

        $definition->setOptions($options);

        return $definition;
    }

    private function setupContainer()
    {
        $container = new Container();
        $container->set('ui.application',$this);
        $container->setShared('ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });


        $phpGuard = new PhpGuard();
        $phpGuard->setContainer($container);
        $phpGuard->setupServices();
        $phpGuard->loadPlugins();
        $container->set('phpguard',$phpGuard);
        $this->setDispatcher($container->get('dispatcher'));
        $this->container = $container;
    }
}
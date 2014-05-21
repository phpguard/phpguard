<?php

namespace PhpGuard\Application\Console;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        if($input->hasParameterOption(array('--tags','-t'))){
            $tags = $input->getParameterOption(array('--tags','-t'));
            $tags = explode(',',$tags);
            $container->setParameter('filter.tags',$tags);
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
        $formatter->setStyle('log-error',new OutputFormatterStyle('red'));
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
        $container->setShared('ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });
        $container->set('ui.application',$this);
        $container->setShared('phpguard',function($c){
            $phpGuard = new PhpGuard();
            $phpGuard->setContainer($c);
            return $phpGuard;
        });
        $this->container = $container;
    }

}
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

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Container;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Container\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct('phpguard',PhpGuard::VERSION);

        if(is_null($container)){
            $container = new Container();
        }
        $this->setupContainer($container);
        $this->container = $container;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;
        $container->set('ui.input',$input);
        $container->set('ui.output',$output);
        $container->get('logger.handler')->setOutput($output);

        foreach ($container->getByPrefix('commands') as $command) {
            $this->add($command);
        }

        if($input->hasParameterOption(array('--tags','-t'))){
            $tags = $input->getParameterOption(array('--tags','-t'));
            $tags = explode(',',$tags);

            $container->setParameter('filter.tags',$tags);
            $container->get('logger')->addDebug('Filtered using tags',array('tags'=>$tags));
        }

        if($input->hasParameterOption(array('--coverage','-r'))){
            $container->setParameter('coverage.enabled',true);
        }

        $event = new GenericEvent($container);
        $container->get('dispatcher')->dispatch(ApplicationEvents::initialize,$event);

        $command = $this->getCommandName($input);
        if(trim($command)===''){
            $input = new StringInput('start');
        }

        return parent::doRun($input, $output);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);
        $formatter = $output->getFormatter();
        $formatter->setStyle('fail',new OutputFormatterStyle('red'));
        $formatter->setStyle('highlight',new OutputFormatterStyle('blue',null,array('bold')));
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
        //$this->container->get('ui.shell')->installReadlineCallback();
        $this->container->setParameter('application.exit_code',ResultEvent::ERROR);
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

        $options['coverage'] = new InputOption(
            'coverage',
            'r',
            InputOption::VALUE_OPTIONAL,
            'Run only for this tags'
        );

        $definition->setOptions($options);

        return $definition;
    }

    public function setupContainer(ContainerInterface $container)
    {
        $container->set('ui.application',$this);
        $container->setShared('ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });


        $phpGuard = new PhpGuard();
        $phpGuard->setContainer($container);
        $phpGuard->setupServices($container);
        $phpGuard->setupCommands($container);
        $phpGuard->setupListeners($container);
        $phpGuard->loadPlugins($container);
        $container->set('phpguard',$phpGuard);
        $this->setDispatcher($container->get('dispatcher'));
        $this->container = $container;
    }

    /**
     * @codeCoverageIgnore
     */
    public function exitApplication()
    {
        $exitCode = $this->container->getParameter('application.exit_code',0);
        $type = ResultEvent::$maps[$exitCode];
        $this->container->get('logger')
            ->addCommon('Application exit with code: <highlight>'.$exitCode.' - '.$type.'</highlight>');
        ;
        $this->container->get('ui.output')->writeln('');
        $this->container->get('logger')
            ->addCommon(PhpGuard::EXIT_MESSAGE);
        $this->container->get('ui.output')->writeln('');

        exit($exitCode);
    }
}
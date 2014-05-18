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

use PhpGuard\Application\ContainerAware;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listen;
use Symfony\Component\Console\Input\StringInput;

/**
 * A Shell wraps an PhpGuard to add shell capabilities to it.
 *
 * Support for history and completion only works with a PHP compiled
 * with readline support (either --with-readline or --with-libedit)
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class Shell extends ContainerAware
{
    private $application;
    private $history;
    private $output;
    private $hasReadline;
    private $processIsolation = false;

    private $running = false;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->hasReadline = function_exists('readline');
        $this->application = $container->get('phpguard.ui.application');
        $this->history = getenv('HOME').'/.history_phpguard';
        $this->output = $container->get('phpguard.ui.output');
    }

    /**
     * Runs the shell.
     */
    public function run()
    {
        $container = $this->container;
        $dispatcher = $container->get('phpguard.dispatcher');
        $dispatcher->addListener(
            PhpGuardEvents::PRE_RUN_COMMANDS,
            array($this,'preRunCommand')
        );
        $dispatcher->addListener(
            PhpGuardEvents::POST_RUN_COMMANDS,
            array($this,'postRunCommand')
        );

        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);

        $this->output->writeln($this->getHeader());
        $php = null;

        if ($this->hasReadline) {
            readline_read_history($this->history);
            readline_completion_function(array($this, 'autocompleter'));
            $this->installReadlineCallback();
        }
        $this->running = true;
        stream_set_blocking(STDIN,0);
        while ($this->running) {
            $r = array(STDIN);
            $w = array();
            $e = array();
            $n = @stream_select($r,$w,$e,2);
            if ($n && in_array(STDIN, $r)) {
                // read a character, will call the callback when a newline is entered
                if($this->hasReadline){
                    readline_callback_read_char();
                }else{
                    $this->output->write($this->getPrompt());
                    $line = fgets(STDIN, 1024);
                    $line = (!$line && strlen($line) == 0) ? false : rtrim($line);
                    if(false==$line){
                        $this->doRunCommand($line);
                    }
                }
            }else{
                $this->evaluate();
            }
        }
    }

    public function evaluate()
    {
        /* @var \PhpGuard\Listen\Listener $listener */
        $listener = $this->container->get('phpguard.listen.listener');
        $listener->evaluate();
    }

    public function preRunCommand()
    {
        $this->getOutput()->writeln("");
        $this->container->get('phpguard')
            ->log('<info>Start to run commands</info>');
        $this->unsetStreamBlocking();
    }

    public function postRunCommand()
    {
        $this->setStreamBlocking();
        $this->installReadlineCallback();
    }

    private function unsetStreamBlocking()
    {
        // normalize console behavior first
        stream_set_blocking(STDIN,1);
        if($this->hasReadline){
            readline_callback_handler_remove();
        }
    }

    private function setStreamBlocking()
    {
        // bring back shell behavior
        stream_set_blocking(STDIN,0);
    }

    public function readlineCallback($return)
    {
        $this->doRunCommand($return);
        $this->installReadlineCallback();
    }

    private function installReadlineCallback()
    {
        if($this->hasReadline){
            readline_callback_handler_install($this->getPrompt(),array($this,'readlineCallback'));
        }
    }

    public function isRunning()
    {
        return $this->running;
    }

    private function doRunCommand($command)
    {
        $command = trim($command);
        if($command=='quit'){
            $this->exitShell();
        }

        if($command=='' || $command=='run-all'){
            $this->unsetStreamBlocking();
            /* @var PluginInterface $plugin */
            $plugins = $this->container->getByPrefix('phpguard.plugins');
            foreach($plugins as $plugin){
                $plugin->runAll();
            }
            $this->setStreamBlocking();
        }else{
            $this->unsetStreamBlocking();
            readline_add_history($command);
            readline_write_history($this->history);
            $input = new StringInput($command);
            $retVal = $this->getApplication()->run($input, $this->output);
            $this->setStreamBlocking();
            return $retVal;
        }
    }

    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    protected function getHeader()
    {
        return <<<EOF

Welcome to the <info>PhpGuard</info> (<comment>{$this->application->getVersion()}</comment>).

At the prompt, type <comment>help</comment> for some help,
or <comment>list</comment> to get a list of available commands.

To exit the shell, type <comment>quit</comment>.
To run all commands, type <comment>Control+D</comment> or <comment>run-all</comment>

EOF;
    }

    /**
     * Renders a prompt.
     *
     * @return string The prompt
     */
    protected function getPrompt()
    {
        // using the formatter here is required when using readline
        return $this->output->getFormatter()->format("\n".$this->application->getName().'> ');
    }

    protected function getOutput()
    {
        return $this->output;
    }

    protected function getApplication()
    {
        return $this->application;
    }

    /**
     * Tries to return autocompletion for the current entered text.
     *
     * @param string $text The last segment of the entered text
     *
     * @return bool|array    A list of guessed strings or true
     */
    private function autocompleter($text)
    {
        $info = readline_info();
        $text = substr($info['line_buffer'], 0, $info['end']);

        if ($info['point'] !== $info['end']) {
            return true;
        }
        // task name?
        if (false === strpos($text, ' ') || !$text) {
            $commands = array_keys($this->application->all());
            $commands[] = 'quit';
            $commands[] = 'run-all';
            return $commands;
        }

        // options and arguments?
        try {
            $command = $this->application->find(substr($text, 0, strpos($text, ' ')));
        } catch (\Exception $e) {
            return true;
        }

        $list = array('--help');
        foreach ($command->getDefinition()->getOptions() as $option) {
            $opt = '--'.$option->getName();
            if(!in_array($opt,$list)){
                $list[] = $opt;
            }
        }

        return $list;
    }

    public function getProcessIsolation()
    {
        return $this->processIsolation;
    }

    public function setProcessIsolation($processIsolation)
    {
        $this->processIsolation = (bool) $processIsolation;

        if ($this->processIsolation && !class_exists('Symfony\\Component\\Process\\Process')) {
            throw new \RuntimeException('Unable to isolate processes as the Symfony Process Component is not installed.');
        }
    }

    public function exitShell()
    {
        $this->output->writeln('');
        $this->output->writeln('Exit PhpGuard. <comment>bye... bye...!</comment>');
        exit(0);
    }
}
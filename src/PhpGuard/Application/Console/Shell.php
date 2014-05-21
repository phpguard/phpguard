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

use \PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * A Shell wraps an PhpGuard to add shell capabilities to it.
 *
 * Support for historyFile and completion only works with a PHP compiled
 * with readline support (either --with-readline or --with-libedit)
 *
 */
class Shell
{

    /**
     * @var \PhpGuard\Application\Console\Application
     */
    private $application;

    /**
     * @var string
     */
    protected $historyFile;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $hasReadline;

    /**
     * @var bool
     */
    private $running = false;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var \PhpGuard\Application\Container\ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->hasReadline = function_exists('readline');
        $this->application = $container->get('ui.application');
        $this->historyFile = getenv('HOME').'/.history_phpguard';
        $this->output = $container->get('ui.output');
        $this->container = $container;
        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);
    }

    /**
     * Runs the shell.
     * @codeCoverageIgnore
     */
    public function run()
    {
        stream_set_blocking(STDIN,0);
        while ($this->running) {
            $r = array(STDIN);
            $w = array();
            $e = array();
            $n = @stream_select($r,$w,$e,2);
            try{
                if ($n && in_array(STDIN, $r)) {
                    $this->readline();
                }
                else{
                    $this->evaluate();
                }
            }catch(\Exception $e){
                $this->application->renderException($e,$this->output);
                $this->installReadlineCallback();
            }
        }
    }

    public function start()
    {
        $this->initialize();
        $this->running = true;
        $this->run();
    }

    public function stop()
    {
        $this->running = false;
    }

    public function evaluate()
    {
        try{
            /* @var \PhpGuard\Listen\Listener $listener */
            $listener = $this->container->get('listen.listener');
            $listener->evaluate();
        }catch(\Exception $e){
            $this->application->renderException($e,$this->output);
        }

    }

    /**
     * @codeCoverageIgnore
     */
    public function unsetStreamBlocking()
    {
        // normalize console behavior first
        stream_set_blocking(STDIN,1);
        if($this->hasReadline){
            readline_callback_handler_remove();
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function setStreamBlocking()
    {
        // bring back shell behavior
        stream_set_blocking(STDIN,0);
    }

    /**
     * @codeCoverageIgnore
     */
    public function installReadlineCallback()
    {
        if($this->hasReadline){
            readline_callback_handler_install($this->getPrompt(),array($this, 'runCommand'));
        }
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * @param   false|string $command
     * @return  int
     */
    public function runCommand($command)
    {
        if($command==false){
            $this->doRunAll($command);
            return;
        }

        $command = trim($command);
        if($command=='quit'){
            $this->exitShell();
        }
        elseif(0===strpos($command,'all')){
            $this->doRunAll($command);
        }
        else{
            $this->unsetStreamBlocking();
            $this->readlineWriteHistory($command);
            $input = new StringInput($command);
            $retVal = $this->application->run($input, $this->output);
            $this->installReadlineCallback();
            $this->setStreamBlocking();
            return $retVal;
        }
    }

    /**
     * Exiting shell
     * @codeCoverageIgnore
     */
    public function exitShell()
    {
        $this->output->writeln('');
        $this->output->writeln('Exit PhpGuard. <comment>bye... bye...!</comment>');
        exit(0);
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
        return $this->output->getFormatter()->format("\n".$this->application->getName().'>> ');
    }

    /**
     * Run all plugins command
     * @param false|string $command
     */
    private function doRunAll($command)
    {
        $plugin = null;

        if(false!==$command){
            $command = str_replace('\040',' ',$command);
            $command = trim($command);
            if($command!=''){
                $this->readlineWriteHistory($command);
            }
            $plugin = trim(substr($command,4));
            if($plugin == ''){
                $plugin=null;
            }
        }

        try{
            // dispatch run all events
            $event = new GenericEvent($this,array('plugin' => $plugin));
            $dispatcher = $this->container->get('dispatcher');
            $dispatcher->dispatch(PhpGuardEvents::runAllCommands,$event);
        }catch(\Exception $e){
            $this->application->renderException($e,$this->output);
        }
    }

    /**
     * Tries to return autocompletion for the current entered text.
     *
     *
     * @return bool|array    A list of guessed strings or true
     */
    private function autocompleter()
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
            $commands[] = 'all';
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

    /**
     * Runs on shell first start
     */
    private function initialize()
    {
        if($this->initialized){
            return;
        }
        $container = $this->container;
        $this->output->writeln($this->getHeader());
        $guard = $this->container->get('phpguard');
        $guard->setupServices();
        $guard->loadConfiguration();
        $this->application->setDispatcher($container->get('dispatcher'));


        if ($this->hasReadline) {
            readline_read_history($this->historyFile);
            readline_completion_function(array($this, 'autocompleter'));
            $this->installReadlineCallback();
        }
        $this->initialized = true;
    }

    private function readline()
    {
        if($this->hasReadline){
            // read a character, will call the callback when a newline is entered
            readline_callback_read_char();
        }
        else{
            $this->output->write($this->getPrompt());
            $line = fgets(STDIN, 1024);
            $line = (!$line && strlen($line) == 0) ? false : rtrim($line);
            $this->runCommand($line);
        }
    }

    /**
     * @param string $command
     */
    private function readlineWriteHistory($command)
    {
        if($this->hasReadline){
            readline_add_history($command);
            readline_write_history($this->historyFile);
        }
    }
}
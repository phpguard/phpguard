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

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\GenericEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A Shell wraps an PhpGuard to add shell capabilities to it.
 *
 * Support for historyFile and completion only works with a PHP compiled
 * with readline support (either --with-readline or --with-libedit)
 *
 */
class Shell implements ShellInterface
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

    private $command;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->hasReadline = function_exists('readline');
        $this->application = $container->get('ui.application');

        $file = getenv('HOME').'/.history_phpguard';
        if(is_file($file)){
            unlink($file);
        }
        $this->historyFile = $file;
        $this->output = $container->get('ui.output');
        $this->container = $container;
        $this->initialize();
        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);
    }

    /**
     * Runs the shell.
     * @codeCoverageIgnore
     */
    public function run()
    {
        /*stream_set_blocking(STDIN,0);
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
        }*/

        stream_set_blocking(STDIN,0);

        $r = array(STDIN);
        $w = array();
        $e = array();

        $n = @stream_select($r,$w,$e,1);

        if ($n && in_array(STDIN, $r)) {
            $this->readline();
        }
        return true;
    }

    public function showPrompt()
    {
        $this->installReadlineCallback();
    }

    public function start()
    {
        $this->running = true;
        $this->run();
    }

    public function stop()
    {
        $this->running = false;
    }

    public function setOptions(array $options = array())
    {
        // TODO: Implement setOptions() method.
    }

    public function evaluate()
    {
        try{
            $this->container->get('listen.listener')->evaluate();
        }
        catch(\Exception $e){
            $this->container->get('ui.application')->renderException($e);
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
            $this->runAll(false);
            return;
        }
        $command = trim($command);
        if($command=='quit'){
            $this->container->get('phpguard')->stop();
        }
        elseif(0===strpos($command,'all')){
            $this->runAll($command);
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
    private function runAll($command)
    {
        $plugin = null;

        if(false!==$command){
            $command = trim($command);
            if($command!=''){
                $this->readlineWriteHistory($command);
            }
            $plugin = trim(substr($command,4));
            if($plugin == ''){
                $plugin=null;
            }
        }
        $this->unsetStreamBlocking();
        $this->output->writeln('');
        try{
            // dispatch run all events
            $event = new GenericEvent($this->container,array('plugin' => $plugin));
            $event->setArguments(array('plugin'=>$plugin));
            $dispatcher = $this->container->get('dispatcher');
            $dispatcher->dispatch(ApplicationEvents::runAllCommands,$event);
        }catch(\Exception $e){
            $this->application->renderException($e,$this->output);
        }
        $this->installReadlineCallback();
        $this->setStreamBlocking();

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
    public function initialize()
    {
        if($this->initialized){
            return;
        }
        if ($this->hasReadline) {
            readline_read_history($this->historyFile);
            readline_completion_function(array($this, 'autocompleter'));
        }
        $this->initialized = true;
    }

    public function readline($prompt=true)
    {
        if($this->hasReadline){
            // read a character, will call the callback when a newline is entered
            readline_callback_read_char();
        }
        else{
            if($prompt){
                $this->output->write($this->getPrompt());
            }
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
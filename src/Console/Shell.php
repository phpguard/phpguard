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
    protected $application;

    /**
     * @var string
     */
    protected $historyFile;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var bool
     */
    protected $hasReadline;

    /**
     * @var \PhpGuard\Application\Container\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->hasReadline = function_exists('readline');
        $this->application = $container->get('ui.application');

        $file = getenv('HOME').'/.history_phpguard';
        $this->historyFile = $file;
        $this->output = $container->get('ui.output');
        $this->container = $container;

        // @codeCoverageIgnoreStart
        if ($this->hasReadline) {
            readline_read_history($this->historyFile);
            readline_completion_function(array($this, 'autocompleter'));
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Runs the shell.
     * @codeCoverageIgnore
     */
    public function run()
    {
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
        // @codeCoverageIgnoreStart
        if($this->hasReadline){
            readline_callback_handler_install($this->getPrompt(),array($this, 'runCommand'));
        }
        // @codeCoverageIgnoreEnd
        else {
            $this->output->write($this->getPrompt());
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
     * @param   false|string $command
     * @return  int
     */
    public function runCommand($command)
    {
        if($command==false){
            $command='all';
        }
        $command = trim($command);
        if($command=='quit'){
            $event = new GenericEvent($this->container);
            $this->container->get('dispatcher')
                ->dispatch(ApplicationEvents::terminated,$event)
            ;
        }
        else{
            $this->unsetStreamBlocking();
            $this->readlineWriteHistory($command);
            $input = new StringInput($command);
            $retVal = $this->application->run($input, $this->output);
            $this->setStreamBlocking();
            return $retVal;
        }
    }

    /**
     * Renders a prompt.
     *
     * @return string The prompt
     */
    public function getPrompt()
    {
        // using the formatter here is required when using readline
        return $this->output->getFormatter()->format("\n".$this->application->getName().'>> ');
    }

    /**
     * Tries to return autocompletion for the current entered text.
     *
     *
     * @return bool|array    A list of guessed strings or true
     * @codeCoverageIgnore
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

    public function readline($prompt=true)
    {
        if(!$this->hasReadline){
            // read a character, will call the callback when a newline is entered
            $line = fgets(STDIN, 1024);
            $line = (!$line && strlen($line) == 0) ? false : rtrim($line);
            $this->runCommand($line);
            if($prompt){
                $this->showPrompt();
            }
        }
        // @codeCoverageIgnoreStart
        else{
            readline_callback_read_char();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $command
     * @codeCoverageIgnore
     */
    private function readlineWriteHistory($command)
    {
        if($this->hasReadline){
            readline_add_history($command);
            readline_write_history($this->historyFile);
        }
    }
}
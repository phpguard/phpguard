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
use PhpGuard\Application\Guard;
use PhpGuard\Application\Interfaces\ContainerAwareInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Output\ConsoleOutput;

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

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->hasReadline = function_exists('readline');
        $this->application = $container->get('guard.ui.application');
        $this->history = getenv('HOME').'/.history_phpguard';
        $this->output = $container->get('guard.ui.output');
    }

    private $prompting = true;

    /**
     * Runs the shell.
     */
    public function run()
    {
        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);

        if ($this->hasReadline) {
            readline_read_history($this->history);
            readline_completion_function(array($this, 'autocompleter'));
            readline_callback_handler_install($this->getPrompt(),array($this,'readlineCallback'));
        }

        $this->output->writeln($this->getHeader());
        $php = null;

        while ($this->prompting) {
            $r = array(STDIN);
            $w = array();
            $e = array();
            $n = stream_select($r,$w,$e,1);
            if ($n && in_array(STDIN, $r)) {
                // read a character, will call the callback when a newline is entered
                if($this->hasReadline){
                    readline_callback_read_char();
                }
            }else{
                //$this->getOutput()->writeln('TIMEOUT');
            }
        }
    }

    public function readlineCallback($return)
    {
        if(false===$return){
            readline_callback_handler_remove();
            $this->prompting = false;
        }else{
            $this->doRunCommand($return);
            readline_callback_handler_install($this->getPrompt(),array($this,'readlineCallback'));
        }
    }

    private function doRunCommand($command)
    {
        $input = new StringInput($command);
        return $this->getApplication()->run($input, $this->output);
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

To exit the shell, type <comment>^D</comment>.
To start monitoring, type <comment>Enter</comment>

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
        return $this->output->getFormatter()->format($this->application->getName().' > ');
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
            return array_keys($this->application->all());
        }

        // options and arguments?
        try {
            $command = $this->application->find(substr($text, 0, strpos($text, ' ')));
        } catch (\Exception $e) {
            return true;
        }

        $list = array('--help');
        foreach ($command->getDefinition()->getOptions() as $option) {
            $list[] = '--'.$option->getName();
        }

        return $list;
    }

    private function setupReadline()
    {

    }

    /**
     * Reads a single line from standard input.
     *
     * @return string The single line from standard input
     */
    private function readline()
    {
        if ($this->hasReadline) {
            $line = readline($this->getPrompt());
        } else {
            $this->output->write($this->getPrompt());
            $line = fgets(STDIN, 1024);
            $line = (!$line && strlen($line) == 0) ? false : rtrim($line);
        }

        return $line;
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
}
<?php

namespace PhpGuard\Application;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpGuard\Listen\Exception\InvalidArgumentException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class Runner
 *
 */
class Runner extends ContainerAware
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var Process
     */
    private $process;

    /**
     * @param array $arguments
     * @return Runner
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param string $command
     *
     * @throws \PhpGuard\Listen\Exception\InvalidArgumentException
     * @return Runner
     */
    public function setCommand($command)
    {
        if(is_file($file='./vendor/bin/'.$command)){
            $executable = $file;
        }elseif(is_executable($file='./bin/'.$command)){
            $executable = $file;
        }else{
            $finder = new ExecutableFinder();
            $executable = $finder->find($command);
            if(!is_executable($executable)){
                throw new InvalidArgumentException(sprintf(
                    'Can not find command "%s"',
                    $command
                ));
            }
        }
        $this->command = $executable;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param bool $silent
     *
     * @return bool
     */
    public function run($silent=false)
    {
        $process = $this->buildProcess();
        if($silent){
            $process->run();
        }else{
            $writer = $this->container->get('ui.output');
            $process->run(function($type,$output) use($writer,$silent){
                if(false==$silent){
                    $writer->write($output);
                }
            });
        }
        $this->process = $process;
        if($process->getExitCode()===0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return Process
     */
    public function getProcessor()
    {
        return $this->process;
    }

    /**
     * @return Process
     */
    public function buildProcess()
    {
        $process = new Process($this->getCommandLine());
        $useTty = $this->container->getParameter('phpguard.use_tty',false);
        if($useTty){
            $process->setTty(true);
        }
        $process->setWorkingDirectory(getcwd());
        return $process;
    }

    public function getCommandLine()
    {
        $commandLine = $this->command.' '.implode(' ',$this->arguments);
        return $commandLine;
    }
}
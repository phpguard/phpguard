<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Bridge\Console\Application;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Class Inspector
 *
 */
class Inspector extends ContainerAware implements LoggerAwareInterface
{
    const CACHE_FILE = 'phpguard/cache/plugins/phpspec/inspector.dat';

    /**
     * @var Logger
     */
    protected $logger;

    protected $success = array();

    protected $failed = array();

    /**
     * @var Application
     */
    protected $app;

    protected $options = array();

    protected $commandLine;

    protected $cmdRunAll;
    protected $cmdRun;

    public function __construct()
    {
        $file = $this->getCacheFileName();
        if(is_file($file)){
            unlink($file);
        }
    }

    static public function getCacheFileName()
    {
        $dir = sys_get_temp_dir().'/phpguard/cache/plugins/phpspec';
        @mkdir($dir,0755,true);
        return $dir.DIRECTORY_SEPARATOR.'inspector.dat';
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        $args = $this->buildArguments($options);
        $this->cmdRun = implode(' ',$args);

        $allOptions = $options['run_all'];
        unset($options['run_all']);
        $allOptions = array_merge($options,$allOptions);
        $args = $this->buildArguments($allOptions);
        $this->cmdRunAll = implode(' ',$args);
    }

    public function setResult($success, $failed)
    {
        foreach($success as $name=>$event){
            if(isset($this->failed[$name])){
                unset($this->failed[$name]);
            }
        }
        foreach($failed as $name=>$event){
            if(!isset($this->failed[$name])){
                $this->failed[$name] = $event;
            }
        }
    }

    public function runAll()
    {
        $command = $this->cmdRunAll;
        if($this->options['keep_failed']){
            $files = array();
            foreach($this->failed as $failed){
                $file = getcwd().DIRECTORY_SEPARATOR.$failed;
                if(!is_file($file)){
                    // spec file should be deleted
                    continue;
                }
                $files[] = $failed;
            }
            if(!empty($files)){
                $command = $this->cmdRun;
                $specFiles = implode(',',$files);
                $command = $command.' --spec-files='.$specFiles;
                $this->logger->debug('Keep failed spec run');
            }
        }

        $this->process($command);

        $this->checkResult();
    }

    public function run($specFiles)
    {
        $specFiles = implode(',',$specFiles);

        $command = $this->cmdRun.' --spec-files='.$specFiles;
        $exitCode = $this->process($command);

        if($exitCode===0 && $this->options['all_after_pass']){
            $this->logger->addDebug('Run all after pass');
            $this->runAll();
        }
        return $exitCode;
    }

    private function process($command)
    {
        $container = $this->container;
        $logger = $this->logger;
        $logger->addDebug($command);
        $writer = $container->get('ui.output');

        $process = new Process($command);//
        $process->setTty($container->getParameter('phpguard.use_tty'));
        $process->run(function($type,$output) use($writer){
            $writer->write($output);
        });
        $this->checkResult();

        return $process->getExitCode();
    }

    private function checkResult()
    {
        $file = $this->getCacheFileName();
        if(!is_file($file)){
            return;
        }
        clearstatcache(true,$file);
        $contents = file_get_contents($file);

        $data = unserialize($contents);

        foreach($data['failed'] as $title=>$file){
            if(!isset($this->failed[$title])){
                $this->failed[$title] = $file;
            }
            $this->logger->addFail('Spec failed <comment>'.$title.'</comment>');
        }

        foreach($data['success'] as $title=>$file){
            if(array_key_exists($title,$this->failed)){
                unset($this->failed[$title]);
            }
        }
    }

    private function buildArguments($options)
    {
        $cmd = realpath(__DIR__.'/Resources/bin/phpspec');
        $args = array($cmd.' run');
        if($options['ansi']){
            $args[] = '--ansi';
        }
        if($options['no_interaction']){
            $args[] = '--no-interaction';
        }
        $args[] = '--format='.$options['format'];
        return $args;
    }
}
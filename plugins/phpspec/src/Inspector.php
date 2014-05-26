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
use PhpGuard\Application\Event\CommandEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Bridge\Console\Application;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Class Inspector
 */
class Inspector extends ContainerAware implements LoggerAwareInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    protected $failed = array();

    /**
     * @var Application
     */
    protected $app;

    protected $options = array();

    protected $commandLine;

    protected $cmdRunAll;

    protected $cmdRun;

    protected $results = array();

    public function __construct()
    {
        // always clear serialized result when Inspector object created
        $file = Inspector::getCacheFileName();
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

    static public function getErrorFileName()
    {
        $dir = sys_get_temp_dir().'/phpguard/cache/plugins/phpspec';
        @mkdir($dir,0755,true);
        return $dir.DIRECTORY_SEPARATOR.'error.dat';
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
        $cmd = realpath(__DIR__.'/../bin/phpspec').' run';
        $this->options = $options;
        $this->cmdRun = $cmd.' '.$options['cli'];
        $allOptions = $options['run_all'];
        unset($options['run_all']);
        $allOptions = array_merge($options,$allOptions);
        $this->cmdRunAll = $cmd.' '.$allOptions['cli'];
    }

    public function runAll()
    {
        $command = $this->cmdRunAll;

        if($this->options['keep_failed']){
            $files = array();
            foreach($this->failed as $failed)
            {
                $file = getcwd().DIRECTORY_SEPARATOR.$failed;
                // spec file should be deleted
                if(!is_file($file)){

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
        $this->logger->addCommon('Running all specs');
        $this->process($command);
        $results = $this->renderResult(true);

        if(count($this->failed)===0){
            $plugin = $this->container->get('plugins.phpspec');
            $message = 'Running All success';
            return array(new CommandEvent($plugin,CommandEvent::SUCCEED,$message));
        }else{
            return $results;
        }
    }

    public function run($files)
    {
        $this->results = array();
        $specFiles = implode(',',$files);

        $command = $this->cmdRun.' --spec-files='.$specFiles;
        $this->logger->addCommon('Running for files',$files);

        $exitCode = $this->process($command);
        $results = $this->renderResult();
        if($exitCode===0){
            if($this->options['all_after_pass']){
                $this->logger->addSuccess('Run all specs after pass');
                $allSpecs = $this->runAll();
                $results = array_merge($results,$allSpecs);
            }
        }
        return $results;
    }

    /**
     * @param   bool $runAll
     * @return  array
     */
    private function renderResult($runAll=false)
    {
        $plugin = $this->container->get('plugins.phpspec');
        $prefix = $runAll ? 'Running All':'Running';
        if(is_file($file=static::getErrorFileName())){
            $message = $prefix.' is broken with message: '.file_get_contents($file);
            $event = new CommandEvent($plugin,CommandEvent::BROKEN,$message);
            return array($event);
        }

        $data = $this->checkResult();
        $results = array();
        foreach($data['success'] as $title=>$file)
        {
            if(isset($this->failed[$title])){
                $this->failed[$title] = $file;
                unset($this->failed[$title]);
            }
            if($runAll) continue;
            $format = 'Running Success For: <highlight>%s</highlight>';

            $message = sprintf($format,$title);

            $event = new CommandEvent($plugin,CommandEvent::SUCCEED,$message);
            $results[] = $event;
        }
        foreach($data['failed'] as $title=>$file){
            $this->failed[$title] = $file;
            $message = $prefix.': '.$title.' spec failed';
            $event = new CommandEvent($plugin,CommandEvent::FAILED,$message);
            $results[] = $event;
        }

        return $results;
    }

    private function process($command)
    {
        $container = $this->container;
        $logger = $this->logger;
        $logger->addDebug($command);
        $writer = $container->get('ui.output');

        $process = new Process($command.' -vvv');
        $process->setTty($container->getParameter('phpguard.use_tty'));
        $errors = null;

        $process->run(function($type,$output) use($writer,&$errors){
            $writer->write($output);
        });
        return $process->getExitCode();
    }

    private function checkResult()
    {
        $file = $this->getCacheFileName();
        if(!is_file($file)){
            return array(
                'failed' => array(),
                'success' => array(),
            );
        }
        clearstatcache(true,$file);
        $contents = file_get_contents($file);

        $data = unserialize($contents);

        return $data;
    }
}

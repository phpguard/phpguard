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
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class Inspector
 */
class Inspector extends ContainerAware implements LoggerAwareInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ResultEvent[]
     */
    protected $failed = array();

    protected $options = array();

    protected $commandLine;

    protected $cmdRunAll;

    protected $cmdRun;

    /**
     * @var PhpSpecPlugin
     */
    protected $plugin;

    public function __construct()
    {
        // always clear serialized result when Inspector object created
        $file = Inspector::getCacheFileName();
        if(file_exists($file)){
            unlink($file);
        }
    }

    public function setContainer(ContainerInterface $container)
    {
        parent::setContainer($container);
        $cmd = realpath(__DIR__.'/../bin/phpspec').' run';
        $this->plugin = $plugin = $container->get('plugins.phpspec');
        $this->options = $options = $plugin->getOptions();
        $this->cmdRun = $cmd.' '.$options['cli'];
        $allOptions = $options['run_all'];
        unset($options['run_all']);
        $allOptions = array_merge($options,$allOptions);
        $this->cmdRunAll = $cmd.' '.$allOptions['cli'];
    }

    static public function getCacheFileName()
    {
        $dir = PhpGuard::getPluginCache('phpspec');
        return $dir.DIRECTORY_SEPARATOR.'results.dat';
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

    /**
     * @return ProcessEvent
     */
    public function runAll()
    {
        $results = $this->doRunAll();
        if(count($this->failed) > 0){
            $this->container->setParameter('application.exit_code',ResultEvent::FAILED);
        }
        return new ProcessEvent(
            $this->container->get('plugins.phpspec'),
            $results
        );
    }

    public function run($files)
    {
        $specFiles = implode(',',$files);

        $command = $this->cmdRun.' --spec-files='.$specFiles;
        $this->logger->addCommon('Running for files',$files);

        $runner = $this->getRunner();
        $arguments = explode(' ',$command);
        $builder = new ProcessBuilder($arguments);
        $process = $runner->run($builder);
        $results = $this->renderResult();

        if(0===$process->getExitCode()){
            if($this->options['all_after_pass']){
                $this->logger->addSuccess('Run all specs after pass');
                $allSpecs = $this->doRunAll();
                $results = array_merge($results,$allSpecs);
            }
        }

        $event = new ProcessEvent(
            $this->container->get('plugins.phpspec'),
            $results
        );
        if(count($this->failed) > 0){
            $this->container->setParameter('application.exit_code',ResultEvent::FAILED);
        }
        return $event;
    }

    private function doRunAll()
    {
        $command = $this->cmdRunAll;
        if($this->options['keep_failed']){
            $files = array();
            foreach($this->failed as $key=>$failedEvent)
            {
                $file = $failedEvent->getArgument('file');
                if(file_exists($file)){
                    $file = ltrim(str_replace(getcwd(),'',$file),'\\/');
                    if(!in_array($file,$files)){
                        $files[] = $file;
                    }
                }
            }
            if(!empty($files)){
                $command = $this->cmdRun;
                $specFiles = implode(',',$files);
                $command = $command.' --spec-files='.$specFiles;
                $this->logger->debug('Keep failed spec run');
            }
        }

        // start to run phpspec command
        $arguments = explode(' ',$command);

        $builder = new ProcessBuilder($arguments);
        $runner = $this->getRunner();
        $runner->run($builder);
        // not showing success events for run all
        $results = $this->renderResult(false);

        if(count($this->failed)===0){
            $results['all_after_pass'] = ResultEvent::createSucceed('Run all specs success');
        }
        return $results;
    }

    /**
     * @param   bool $showSuccess
     * @return  array
     */
    private function renderResult($showSuccess = true)
    {
        /* @var ResultEvent $resultEvent */
        $results = array();

        $file = static::getCacheFileName();
        if(!file_exists($file)){
            throw new \RuntimeException(sprintf(
                'Unknown PhpSpec results'
            ));
        }

        $data = Filesystem::unserialize($file);

        foreach($data as $key => $resultEvent){
            if($resultEvent->isSucceed()){
                if($showSuccess){
                    $results[$key] = $resultEvent;
                }
                if(isset($this->failed[$key])){
                    unset($this->failed[$key]);
                }
                //$this->logger->addDebug($key.' '.$resultEvent->getMessage());
            }else{
                // track failed result
                $this->failed[$key] = $resultEvent;
                $results[$key] = $resultEvent;
            }
        }

        return $results;
    }

    /**
     * @return \PhpGuard\Application\Util\Runner
     */
    private function getRunner()
    {
        return $this->container->get('runner');
    }
}

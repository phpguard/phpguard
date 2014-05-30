<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Application\Util\Runner;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class Inspector
 *
 */
class Inspector extends ContainerAware
{
    const MSG_RUN_ALL_SUCCESS       = 'Run All Succeed';
    const CONTAINER_RESULT_ID       = 'phpunit.inspector_result';

    private $runArgs;

    /**
     * @var PHPUnitPlugin
     */
    private $plugin;

    /**
     * @var ResultEvent[]
     */
    private $failed = array();

    private $executable;

    private $options = array();

    public function __construct()
    {
        if(is_file($file = static::getResultFileName())){
            unlink($file);
        }
        $this->executable = realpath(__DIR__.'/../bin/phpunit');
    }

    static public function getResultFileName()
    {
        $dir = PhpGuard::getPluginCache('phpunit');
        return $dir.DIRECTORY_SEPARATOR.'/results.dat';
    }

    public function setContainer(ContainerInterface $container)
    {
        parent::setContainer($container);
        $phpunit = $container->get('plugins.phpunit');
        $this->options = $options = $phpunit->getOptions();
        $this->runArgs = $options['cli'];
        $this->plugin = $phpunit;
    }

    public function run(array $paths = array(),$allAfterPass=true)
    {
        $runner = $this->getRunner();
        $paths = implode(',',$paths);

        // normalize path
        $paths = str_replace(getcwd().DIRECTORY_SEPARATOR,'',$paths);

        $args = explode(' ',$this->runArgs);


        $builder = new ProcessBuilder($args);
        $builder->setPrefix($this->executable);
        $builder->add($paths);

        $exitCode =$runner->run($builder)->getExitCode();
        $results = $this->checkResult(true);
        if(0===$exitCode && $this->options['all_after_pass']){
            $this->getLogger()->addDebug('Run all tests after pass');
            $results = array_merge($results,$this->doRunAll());
        }
        $event = new ProcessEvent($this->plugin,$results);
        return $event;
    }

    public function runAll()
    {
        $results = $this->doRunAll();
        $event = new ProcessEvent($this->plugin,$results);
        return $event;
    }

    private function doRunAll()
    {
        $args = $this->runArgs;
        $args = explode(' ',$args);

        if(count($this->failed) > 0){
            $files = array();
            foreach($this->failed as $resultEvent){
                $file=$resultEvent->getArgument('file');
                $file = ltrim(str_replace(getcwd(),'',$file),'\\/');
                $files[] = $file;
            }
            if(!empty($files)){
                $this->getLogger()->addDebug('keep failed tests run');
                $files = array_unique($files);
                $args[] = implode(',',$files);
            }
        }
        $builder = new ProcessBuilder($args);
        $builder->setPrefix($this->executable);

        $runner = $this->getRunner();
        $runner->run($builder);
        $results = $this->checkResult(false);

        if(empty($this->failed)){
            $results['all_after_pass'] = ResultEvent::createSucceed(static::MSG_RUN_ALL_SUCCESS);
        }
        return $results;
    }

    /**
     * @return Runner
     */
    private function getRunner()
    {
        return $this->container->get('runner');
    }

    private function checkResult($showSuccess=true)
    {
        /* @var ResultEvent $event */
        $file = static::getResultFileName();
        if(!is_file($file)){
            throw new \RuntimeException('Unknown phpunit results.');
        }
        $data = Filesystem::unserialize($file);
        $results = array();
        foreach($data as $key=>$event){
            $file = $event->getArgument('file');
            if($event->isSucceed()){
                foreach($this->failed as $failedKey=>$failedEvent){
                    if($failedEvent->getArgument('file') === $file){
                        $this->getLogger()->addDebug('unset failed tests '.$failedEvent->getMessage());
                        unset($this->failed[$failedKey]);
                    }
                }
                if($showSuccess){
                    $results[$key] = $event;
                }
            }
            else {
                $this->getLogger()->addDebug($key.' '.$event->getMessage());
                $this->failed[$key] = $event;
                $results[$key] = $event;
            }
        }
        return $results;
    }

    /**
     * @return \PhpGuard\Application\Log\Logger
     */
    private function getLogger()
    {
        return $this->container->get('logger');
    }
}
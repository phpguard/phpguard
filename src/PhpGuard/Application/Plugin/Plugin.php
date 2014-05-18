<?php

namespace PhpGuard\Application\Plugin;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PhpGuard\Application\ContainerAware;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\Runner;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Listen\Util\PathUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Plugin
 *
 */
abstract class Plugin extends ContainerAware implements PluginInterface,LoggerAwareInterface
{
    protected $watchers = array();

    protected $options = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function configure(){}

    public function addWatcher(Watcher $watcher)
    {
        $this->watchers[] = $watcher;
    }

    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * @param   EvaluateEvent $event
     * @return  array
     */
    public function getMatchedFiles(EvaluateEvent $event)
    {
        $filtered = array();
        $files = $event->getFiles();
        foreach($files as $file){
            if($matched=$this->matchFile($file)){
                if(!$matched instanceof SplFileInfo){
                    $matched = PathUtil::createSplFileInfo(getcwd(),$matched);
                }
                $filtered[] = $matched;
            }
        }
        return $filtered;
    }

    public function setOptions(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log($message,$context = array(),$level=LogLevel::INFO)
    {
        $this->logger->log($level,$message,$context);
    }

    /**
     * @param   string    $command
     * @param   array     $arguments
     * @return  Runner
     */
    public function createRunner($command,array $arguments = array())
    {
        $runner = new Runner();
        $runner->setCommand($command);
        $runner->setArguments($arguments);
        $runner->setOutput($this->container->get('phpguard.ui.output'));

        return $runner;
    }

    /**
     * @param $file
     * @return bool|SplFileInfo
     * @author Anthonius Munthi <me@itstoni.com>
     */
    private function matchFile($file)
    {
        /* @var Watcher $watcher */
        foreach($this->watchers as $watcher){
            if($matched = $watcher->matchFile($file)){
                return $matched;
            }
        }
        return false;
    }
}
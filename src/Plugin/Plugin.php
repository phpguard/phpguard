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

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Runner;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Listen\Util\PathUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Plugin
 *
 */
abstract class Plugin extends ContainerAware implements PluginInterface
{

    protected $watchers = array();

    protected $options = array();

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var boolean
     */
    protected $active = false;

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

    /**
     * @param boolean $active
     *
     * @return Plugin
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
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
     * @param   string    $command
     * @param   array     $arguments
     * @return  Runner
     */
    public function createRunner($command,array $arguments = array())
    {
        $runner = new Runner();
        $runner->setContainer($this->container);
        $runner->setCommand($command);
        $runner->setArguments($arguments);

        return $runner;
    }

    /**
     * @param $file
     * @return string
     * @author Anthonius Munthi <me@itstoni.com>
     */
    private function matchFile($file)
    {
        $tags = $this->container->getParameter('filter.tags',array());
        /* @var Watcher $watcher */
        foreach($this->watchers as $watcher){
            if(false===$watcher->hasTag($tags)){
                $options = $watcher->getOptions();
                $this->logger->debug('Unmatched tags',array('watcher.tags'=>$options['tags'],'app.tags'=>$tags));
                continue;
            }
            if($matched = $watcher->matchFile($file)){
                if($watcher->lint($file)){
                    return $matched;
                }
            }
        }
        return false;
    }
}
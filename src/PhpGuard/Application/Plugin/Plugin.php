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
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\Watcher;
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
abstract class Plugin implements PluginInterface,LoggerAwareInterface
{
    protected $watchers = array();

    protected $options = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function addWatcher(Watcher $watcher)
    {
        $this->watchers[] = $watcher;
    }

    private function matchFile($file)
    {
        /* @var Watcher $watcher */
        foreach($this->watchers as $watcher){
            if($watcher->matchFile($file)){
                return true;
            }
        }
        return false;
    }

    /**
     * @param $files
     * @return array|bool
     */
    protected function matchFiles($files)
    {
        $filtered = array();
        foreach($files as $file){
            if($this->matchFile($file)){
                if(!$file instanceof SplFileInfo){
                    $file = PathUtil::createSplFileInfo(getcwd(),$file);
                }
                $filtered[] = $file;
            }
        }
        if(!empty($filtered)){
            return $filtered;
        }else{
            return false;
        }
    }

    public function setOptions(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
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
}
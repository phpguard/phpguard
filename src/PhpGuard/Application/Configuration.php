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

use PhpGuard\Application\Exception\ConfigurationException;
use PhpGuard\Application\Interfaces\PluginInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 */
class Configuration extends ContainerAware
{
    public function compileFile($file)
    {
        if(!is_file($file)){
            throw new \RuntimeException(sprintf(
                'Configuration file: "%s" not exist.',
                $file
            ));
        }
        $this->compile($file);
    }

    public function compile($text)
    {
        $parsed = Yaml::parse($text);

        foreach($parsed as $plugin=>$definitions){
            if($plugin=='phpguard'){
                $this->parseGuardSection($definitions);
            }else{
                $this->parsePluginSection($plugin,$definitions);
            }
        }
    }

    private function parseGuardSection($definitions)
    {
        $container = $this->container;
        $guard = $container->get('phpguard');
        $guard->setOptions($definitions);
    }

    private function parsePluginSection($name,$definitions)
    {
        $container = $this->container;
        $id = 'plugins.'.$name;

        if(!$container->has($id)){
            throw new ConfigurationException(sprintf(
                'PhpGuard plugin with name: "%s" is not installed.',
                $name
            ));
        }

        /* @var PluginInterface $plugin */
        $plugin = $container->get($id);
        if(isset($definitions['options'])){
            $plugin->setOptions($definitions['options']);
        }
        if(isset($definitions['watch'])){
            $this->parseWatchSection($plugin,$definitions['watch']);
        }
    }

    private function parseWatchSection(PluginInterface $plugin,$definitions)
    {
        foreach($definitions as $options){
            $watcher = new Watcher();
            $watcher->setOptions($options);
            $plugin->addWatcher($watcher);
        }
    }
}
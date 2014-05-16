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

use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Resource\FileResource;
use PhpGuard\Listen\Util\PathUtil;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Watcher
 *
 */
class Watcher
{
    private $options;

    public function setOptions($options)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function matchFile($file)
    {
        if(!is_file($file)){
            return false;
        }

        if($file instanceof FileResource){
            $file = $file->getResource();
        }

        if($file instanceof SplFileInfo){
            $file = PathUtil::createSplFileInfo(getcwd(),(string)$file);
        }

        $pattern = $this->options['pattern'];

        if(preg_match($pattern,$file)){
            return true;
        }
        if(preg_match($pattern,$file->getRelativePathname())){
            return true;
        }

    }

    public function setDefaultOptions(OptionsResolverInterface  $resolver)
    {
        $resolver->setRequired(array(
            'pattern'
        ));

        $resolver->setDefaults(array(
            'tags' => array(),
        ));
    }

}

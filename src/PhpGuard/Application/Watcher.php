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
    private $options = array(
        'groups' => array(),
        'tags' => array(),
    );

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

    public function hasGroup($group)
    {
        return in_array($group,$this->options['groups']);
    }

    public function hasTag($tags)
    {
        if(empty($tags)){
            return true;
        }
        if(!is_array($tags)){
            $tags = array($tags);
        }

        foreach($tags as $tag){
            if(in_array($tag,$this->options['tags'])){
                return true;
            }
        }
        return false;
    }

    public function matchFile($file)
    {
        if(!is_file($file)){
            return false;
        }

        if($file instanceof FileResource){
            $file = (string)$file->getResource();
        }

        if(!$file instanceof SplFileInfo){
            $file = PathUtil::createSplFileInfo(getcwd(),(string)$file);
        }

        $pattern = $this->options['pattern'];

        $retVal = false;
        if(preg_match($pattern,$file)){
            $retVal = $file;
        }elseif(preg_match($pattern,$file->getRelativePathname())){
            $retVal = $file;
        }

        if($retVal && $this->options['transform']){
            $transformed = preg_replace($pattern,$this->options['transform'],$file->getRelativePathname());
            if(!is_file($transformed)){
                return false;
            }
            $retVal = PathUtil::createSplFileInfo(getcwd(),$transformed);
        }

        return $retVal;
    }

    public function setDefaultOptions(OptionsResolverInterface  $resolver)
    {
        $resolver->setRequired(array(
            'pattern'
        ));

        $resolver->setDefaults(array(
            'tags' => array(),
            'groups' => array(),
            'transform' => null,
        ));

        $arrayNormalizer = function($options,$value){
            if(!is_array($value)){
                $value = array($value);
            }
            return $value;
        };
        $resolver->setNormalizers(array(
            'groups' => $arrayNormalizer,
            'tags' => $arrayNormalizer
        ));
    }
}

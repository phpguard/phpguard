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

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Linter\LinterException;
use PhpGuard\Listen\Exception\InvalidArgumentException;
use PhpGuard\Listen\Resource\FileResource;
use PhpGuard\Listen\Util\PathUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Watcher
 *
 */
class Watcher extends ContainerAware
{
    private $options = array(
        'groups' => array(),
        'tags' => array(),
    );

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

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
            $this->container->get('phpguard')->log('Transform: '.$file->getRelativePathname(). ' To '.$transformed,OutputInterface::VERBOSITY_DEBUG);
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
            'lint' => array()
        ));

        $arrayNormalizer = function($options,$value){
            if(!is_array($value)){
                $value = array($value);
            }
            return $value;
        };

        $container = $this->container;
        $lintChecker = function(Options $options,$value) use($arrayNormalizer,$container){
            $value = $arrayNormalizer($options,$value);
            $linters = array();
            foreach($value as $name){
                $id = 'linters.'.$name;
                if(!$container->has($id)){
                    throw new InvalidArgumentException(sprintf(
                        'Linter "%s" not exists',
                        $name
                    ));
                }
                $linter = $container->get($id);
                $linters[$linter->getName()] = $linter;
            }
            return $linters;
        };
        $resolver->setNormalizers(array(
            'groups' => $arrayNormalizer,
            'tags' => $arrayNormalizer,
            'lint' => $lintChecker
        ));
    }

    public function lint($file)
    {
        /* @var \PhpGuard\Application\Linter\LinterInterface $linter */
        /* @var \PhpGuard\Application\PhpGuard $phpguard */
        $phpguard = $this->container->get('phpguard');
        $retVal = true;

        foreach($this->options['lint'] as $linter){
            try{
                $linter->check($file);
            }catch(LinterException $e){
                $phpguard->log($e->getFormattedOutput());
                $retVal = false;
            }
        }
        return $retVal;
    }
}

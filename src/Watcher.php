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
use PhpGuard\Application\Plugin\TaggableInterface;
use PhpGuard\Listen\Exception\InvalidArgumentException;
use PhpGuard\Listen\Resource\FileResource;
use PhpGuard\Listen\Util\PathUtil;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Watcher
 *
 */
class Watcher extends ContainerAware implements TaggableInterface
{
    private $options = array(
        'groups' => array(),
        'tags' => array(),
    );

    /**
     * @var \PhpGuard\Application\Linter\LinterInterface[]
     */
    private $linters = array();

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    public function setOptions($options)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
        $this->linters = isset($this->options['lint']) ? $this->options['lint']:array();
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function hasGroup($group)
    {
        return in_array($group,$this->options['groups']);
    }

    public function hasTags($tags)
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

    public function getTags()
    {
        return $this->options['tags'];
    }

    public function addTags($tags)
    {
        if(!is_array($tags)){
            $tags = array($tags);
        }

        $cTags = $this->options['tags'];
        foreach($tags as $tag){
            if(!in_array($tag,$cTags)){
                $cTags[] = $tag;
            }
        }
        $this->options['tags'] = $cTags;
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
            $this->container->get('logger')->addDebug('Transform: '.$file->getRelativePathname(). ' To '.$transformed);
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
        $output = array();
        foreach($this->linters as $linter){
            try{
                $linter->check($file);
            }catch(LinterException $e){
                $output[] = $e->getFormattedOutput();
            }
        }
        if(empty($output)){
            return true;
        }else{
            return $output;
        }
    }
}

<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Loader;

use ReflectionClass;
use ReflectionMethod;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Loader\Node\SpecificationNode;
use PhpSpec\Loader\ResourceLoader as BaseResourceLoader;
use PhpSpec\Loader\Suite;
use PhpSpec\Locator\ResourceManager;

/**
 * Class ResourceLoader
 *
 */
class ResourceLoader extends BaseResourceLoader
{
    protected $manager;

    public function __construct(ResourceManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param   string  $file
     * @return  \PhpSpec\Locator\ResourceInterface
     */
    public function createResource($file)
    {

    }

    public function loadSpecFiles(array $files)
    {
        $suite = new Suite();
        foreach($files as $file){
            $specClass = $this->getSpecClass($file);
            if(!$specClass){
                return false;
            }
            if($specClass==='spec\\PhpGuard\\Plugins\\PhpSpec\\PhpSpecPluginSpec'){
                $srcClass = 'PhpGuard\\Plugins\\PhpSpec\\PhpSpecPlugin';
            }else{

            }
            $srcClass = substr($specClass,5);
            $srcClass = substr($specClass,0,strlen($specClass)-4);

            $resource = $this->manager->createResource($srcClass);
            if(!$resource){
                continue;
            }
            $reflection = new \ReflectionClass($specClass);
            if($reflection->isAbstract()){
                continue;
            }
            if(!$reflection->implementsInterface('PhpSpec\SpecificationInterface')){
                continue;
            }

            $spec = new SpecificationNode($resource->getSrcClassname(),$reflection,$resource);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!preg_match('/^(it|its)[^a-zA-Z]/', $method->getName())) {
                    continue;
                }

                $example = new ExampleNode(str_replace('_', ' ', $method->getName()), $method);

                if ($this->methodIsEmpty($method)) {
                    $example->markAsPending();
                }

                $spec->addExample($example);
            }
            $suite->addSpecification($spec);
        }

        return $suite;
    }

    private function getSpecClass($specFile)
    {
        $testFile = $specFile;
        if(!is_file($specFile)){
            $testFile = getcwd().DIRECTORY_SEPARATOR.$specFile;
        }

        if(!is_file($testFile)){
            throw new \Exception('Spec file "'.$specFile.'" not exists');
        }else{
            $specFile = $testFile;
        }

        require_once realpath($specFile);

        $exp = explode(DIRECTORY_SEPARATOR,str_replace('.php','',$specFile));
        $class = null;
        $i = count($exp)-1;

        while(isset($exp[$i])){
            $class = $exp[$i].'\\'.$class;
            $class = rtrim($class,'\\');
            if(class_exists($class,true)){
                return $class;
            }
            $i--;
        }
        return false;
    }
    /**
     * @param $line
     * @param  ReflectionMethod $method
     * @return bool
     */
    protected function lineIsInsideMethod($line, ReflectionMethod $method)
    {
        $line = intval($line);

        return $line >= $method->getStartLine() && $line <= $method->getEndLine();
    }
    /**
     * @param  \ReflectionMethod $method
     * @return bool
     */
    private function methodIsEmpty(\ReflectionMethod $method)
    {
        $filename = $method->getFileName();
        $lines    = explode("\n", file_get_contents($filename));
        $function = trim(implode("\n",
            array_slice($lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine()
            )
        ));

        $function = trim(preg_replace(
            array('|^[^}]*{|', '|}$|', '|//[^\n]*|s', '|/\*.*\*/|s'), '', $function
        ));

        return '' === $function;
    }



}
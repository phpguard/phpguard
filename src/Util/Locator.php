<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Util;
use PhpGuard\Listen\Util\PathUtil;
use SplObjectStorage;
use Composer\Autoload\ClassLoader;

/**
 * Class Locator
 *
 */
class Locator
{
    /**
     * @var SplObjectStorage
     */
    private $autoLoaders;

    protected $prefixes = array();
    protected $prefixesPsr4 = array();

    protected $fallbackDirs = array();
    protected $fallbackDirsPsr4 = array();
    /**
     * @var ClassLoader
     */
    private $mainLoader;

    public function __construct()
    {
        $this->autoLoaders = new SplObjectStorage();
        $this->initialize();

        spl_autoload_register(array($this,'loadClass'));
    }

    public function loadClass($class)
    {
        /* @var ClassLoader $loader */
        foreach($this->autoLoaders as $loader)
        {
            if($loader->loadClass($class)){
                return true;
            }
        }
        return $this->mainLoader->loadClass($class);
    }

    public function add($prefix,$path,$prepend=false)
    {
        $this->mainLoader->add($prefix,$path,$prepend);
        $this->prefixes = array_merge($this->prefixes,$this->mainLoader->getPrefixes());
        return $this;
    }

    public function addPsr4($prefix,$paths,$prepend=false)
    {
        $this->mainLoader->addPsr4($prefix,$paths,$prepend);
        $this->prefixesPsr4 = array_merge($this->prefixesPsr4,$this->mainLoader->getPrefixesPsr4());
        $this->fallbackDirsPsr4 = array_merge($this->fallbackDirsPsr4,$this->mainLoader->getFallbackDirsPsr4());
        return $this;
    }

    /**
     * Find class file for class
     *
     * @param   string  $class Class name
     * @param   string  $baseDir
     *
     * @return  string  The filename or false if file not found
     */
    public function findClassFile($class,$baseDir = null)
    {
        /* @var ClassLoader $loader */

        $autoLoaders = $this->autoLoaders;
        $baseDir = is_null($baseDir) ? getcwd():$baseDir;

        $file = $this->mainLoader->findFile($class);
        if(!is_file($file)){
            foreach($autoLoaders as $loader)
            {
                $file = $loader->findFile($class);
                if(is_file($file)){
                    break;
                }
            }
        }

        if(is_file($file)){
            $spl = PathUtil::createSplFileInfo($baseDir,$file);
            return $spl;
        }

        return false;
    }

    public function findClass($file,$checkExistence=true)
    {
        $test = str_replace('.php','',$file);
        $test = str_replace(DIRECTORY_SEPARATOR,'\\',$test);
        $exp = explode('\\',$test);
        $class = array_pop($exp);//
        $dir = implode(DIRECTORY_SEPARATOR,$exp);
        $testClass = $this->getClass($dir,$class,$checkExistence);
        if(false===$testClass){
            $testClass = $this->getClassPsr4($dir,$class,$checkExistence);
        }

        return $testClass;
    }

    public function getPathOfNamespace($namespace)
    {
        $path = false;
        foreach($this->fallbackDirs as $dir){
            $testDir = $dir.DIRECTORY_SEPARATOR.$namespace;
            if(is_dir($testDir)){
                return $testDir;
            }
        }

        foreach($this->fallbackDirsPsr4 as $dir){
            $testDir = $dir.DIRECTORY_SEPARATOR.$namespace;
            if(is_dir($testDir)){
                return $testDir;
            }
        }

        $exp = explode('\\',$namespace);
        $topNamespace = $exp[0];
        foreach($this->prefixes as $ns=>$prefix){
            if(false!==strpos($ns,$topNamespace)){
                //print_r($prefix);
            }
        }

        foreach($this->prefixesPsr4 as $ns=>$prefix){
            if(false!==strpos($ns,$namespace)){
                foreach($prefix as $dir){
                    if(is_dir($dir)){
                        return $dir;
                    }
                }
            }
        }

        return $path;
    }

    private function getClass($dir,$class,$checkExistence = true)
    {
        return $this->checkPrefix($this->prefixes,$dir,$class,$checkExistence);
    }

    private function getClassPsr4($dir,$class,$checkExistence = true)
    {
        return $this->checkPrefix($this->prefixesPsr4,$dir,$class,$checkExistence);
    }

    private function checkPrefix(array $prefixes,$dir,$class=false,$checkExistence=false)
    {
        $absPath = realpath($dir);

        $testClass = false;
        foreach($prefixes as $ns=>$prefix){
            foreach($prefix as $nsDir){
                $len = strlen($nsDir);
                if($nsDir===substr($absPath,0,$len)){
                    $namespace = ltrim(substr($absPath,$len),'\\/');
                    if($class){
                        $testClass = $namespace.DIRECTORY_SEPARATOR.$class;
                        $testClass = str_replace(DIRECTORY_SEPARATOR,'\\',$testClass);
                        $testClass = ltrim($testClass,'\\');
                    }else{
                        $testClass = $namespace;
                    }
                    if(false!==strpos($ns,'\\')){
                        $ns = rtrim($ns,'\\');
                        $testClass = $ns.'\\'.$testClass;
                    }
                    break 2;
                }
            }
        }
        if(!is_null($testClass)){
            if($checkExistence){
                if(class_exists($testClass)){
                    return $testClass;
                }else{
                    return false;
                }
            }
            return $testClass;
        }
        return false;
    }

    /**
     * Initialize all registered loaders
     */
    private function initialize()
    {

        if(is_file($file = getcwd().'/vendor/autoload.php')){
            $autoload = include_once $file;
            if(is_object($autoload)){
                $autoload->register();
            }
        }
        $functions = spl_autoload_functions();
        foreach($functions as $loader){
            if(is_array($loader)){
                $ob = $loader[0];
                if(is_object($ob)){
                    $this->addAutoloader($ob);
                }
            }
        }
    }

    private function addAutoloader($object)
    {
        if (!$object instanceof ClassLoader) {
            return $object;
        }

        $object = clone $object;

        if ($this->autoLoaders->contains($object)) {
            return;
        }
        if ($this->autoLoaders->count()===0) {
            $this->mainLoader = $object;
        }

        if(is_array($object->getFallbackDirs())){
            $this->fallbackDirs = array_merge_recursive($this->fallbackDirs,$object->getFallbackDirs());
        }

        if(is_array($object->getFallbackDirsPsr4())){
            $this->fallbackDirs = array_merge_recursive($this->fallbackDirs,$object->getFallbackDirs());
        }

        if(is_array(@$object->getPrefixes())){
            $this->prefixes = array_merge_recursive($this->prefixes,$object->getPrefixes());
        }
        if(is_array(@$object->getPrefixesPsr4())){
            $this->prefixesPsr4 = array_merge_recursive($this->prefixesPsr4,$object->getPrefixesPsr4());
        }

        $this->autoLoaders->attach($object);
    }
}

<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Plugins\PhpSpec\Command\DescribeCommand;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Yaml\Yaml;

class PhpSpecPlugin extends Plugin
{
    protected $suites = array();

    public function __construct()
    {
        // set default options for phpspec plugin
        $this->setOptions(array());
    }

    public function addWatcher(Watcher $watcher)
    {
        parent::addWatcher($watcher);
        if($this->options['always_lint']){
            $options = $watcher->getOptions();
            $linters = array_keys($options['lint']);
            if(!in_array('php',$linters)){
                $linters[] = 'php';
                $options['lint'] = $linters;
                $watcher->setOptions($options);
            }
        }
    }

    public function configure()
    {
        if(class_exists('PhpSpec\\Console\\Application')){
            // only load command when phpspec package exists
            /* @var \PhpGuard\Application\Console\Application $application */
            $container = $this->container;
            $application = $container->get('ui.application');
            $command = new DescribeCommand();
            $command->setContainer($this->container);
            $application->add($command);
        }

        if($this->options['import_suites']){
            $this->importSuites();
        }
    }

    public function getName()
    {
        return 'phpspec';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'PhpSpec';
    }


    public function runAll()
    {
        $options = $this->options['run_all'];
        $options = array_merge($this->options,$options);
        $arguments = $this->buildArguments($options);
        $runner = $this->createRunner('phpspec',$arguments);
        $return = $runner->run();
        if($return){
            $this->logger->addCommon('All spec pass');
        }else{
            $this->logger->addCommon('PhpSpec Run All failed');
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        $running = false;
        foreach($paths as $file)
        {
            if(!$this->hasSpecFile($file)){
                $message = 'Spec file not found for <comment>'.$file->getRelativePathname().'</comment>';
                $this->logger->addDebug($message);
                continue;
            }

            $classFile = $this->getClassFile($file);
            $arguments = $this->buildArguments($this->options);
            $arguments[] = $classFile;
            $runner = $this->createRunner('phpspec',$arguments);
            $return = $runner->run();
            $running = true;
            if(!$return){
                $success = false;
            }
        }
        if($running){
            if($success){
                $this->logger->addFail('Run spec success');
                if($this->options['all_after_pass']){
                    $this->logger->addFail('Run all specs after pass');
                    $this->runAll();
                }
            }
            else{
                $this->logger->addFail('Run spec failed');
            }
        }
    }

    public function hasSpecFile(SplFileInfo $path)
    {
        //find by relative path first
        $absPath = realpath($path);
        if(false!==strpos($absPath,'Spec.php')){
            return true;
        }
        $rpath = $path->getRelativePathname();

        $baseDir = rtrim(str_replace($rpath,'',$absPath),'\\/');

        $pattern = '#^(\w+)\/(.*)\.php$#';
        preg_match($pattern,$rpath,$matches);

        $transform = $baseDir.DIRECTORY_SEPARATOR.preg_replace($pattern,'spec/${2}Spec.php',$rpath);
        if(is_file($transform)){
            return true;
        }

        // find based on suites
        foreach($this->suites as $suite){
            $specPrefix = isset($suite['spec_prefix']) ? $suite['spec_prefix']:'spec';
            if(isset($suite['spec_path'])){
                $specPath = $suite['spec_path'].DIRECTORY_SEPARATOR.$specPrefix;
            }else{
                $specPath = 'spec';
            }
            $testPath = $baseDir.DIRECTORY_SEPARATOR.$specPath;
            $testPath = rtrim($testPath,'\\/');
            $testFile = $testPath.DIRECTORY_SEPARATOR.$matches[2]."Spec.php";
            if(is_file($testFile)){
                return true;
            }
        }

        return false;
    }

    public function getClassFile(SplFileInfo $file)
    {
        $absPath = $file;

        if(false===strpos($absPath,'Spec.php')){
            return $file->getRelativePathname();
        }

        $rpath = $file->getRelativePathname();
        $baseDir = str_replace($rpath,'',$absPath);
        $classFile = str_replace('Spec.php','',$rpath);

        $exp = explode(DIRECTORY_SEPARATOR,$classFile);
        $class = null;
        $i = count($exp)-1;

        while(isset($exp[$i])){
            $class = $exp[$i].'\\'.$class;
            $class = rtrim($class,'\\');
            if(class_exists($class,true)){
                $r = new \ReflectionClass($class);
                $file = str_replace($baseDir,'',$r->getFileName());
                return $file;
            }
            $i--;
        }

        // class file not exists
        // should wait until phpspec complete feature to run spec from spec file
        return $file->getRelativePathname();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'format' => 'pretty',
            'ansi' => true,
            'all_on_start' => false,
            'all_after_pass' => false,
            'keep_failed' => false,
            'import_suites' => false, // import suites as watcher
            'always_lint' => true,
            'run_all' => array(
                'format' => 'progress'
            )
        ));
    }

    public function importSuites()
    {
        $path = null;
        if(is_file($file='phpspec.yml')){
            $path = $file;
        }
        elseif(is_file($file='phpspec.yml.dist')){
            $path = $file;
        }
        if(is_null($path)){
            return;
        }

        $config = Yaml::parse($path);

        if(!isset($config['suites'])){
            return;
        }

        $this->suites = $suites = $config['suites'];

        foreach($suites as $name=>$definition){
            $source = 'src';
            if(isset($definition['src'])){
                $source = $definition['src'];
            }
            elseif(isset($definition['spec_path'])){
                $source = $definition['spec_path'];
            }
            $pattern = '#^'.str_replace('/','\/',$source).'(.+)\.php$#';
            $watcher = new Watcher($this->container);
            $watcher->setContainer($this->container);
            $options = array(
                'pattern' => $pattern,
                'tags' => $name
            );
            $watcher->setOptions($options);
            $message = 'Imported suite '.$name;
            $this->logger->addDebug($message,$options);
            $this->addWatcher($watcher);
        }
    }

    private function buildArguments($options)
    {
        $args = array('run');
        if($options['ansi']){
            $args[] = '--ansi';
        }
        $args[] = '--format='.$options['format'];
        return $args;
    }
}
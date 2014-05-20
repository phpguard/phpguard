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

    public function runAll()
    {
        $options = $this->options['run_all'];
        $options = array_merge($this->options,$options);
        $arguments = $this->buildArguments($options);
        $runner = $this->createRunner('phpspec',$arguments);
        $return = $runner->run();
        if($return){
            $this->log('All spec pass');
        }else{
            $this->log('<log-error>PhpSpec Run All failed</log-error>');
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        foreach($paths as $file)
        {
            if(!$this->hasSpecFile($file)){
                continue;
            }
            $arguments = $this->buildArguments($this->options);
            $arguments[] = $file->getRelativePathName();
            $runner = $this->createRunner('phpspec',$arguments);
            $return = $runner->run();
            if(!$return){
                $success = false;
            }
        }
        if($success){
            $this->log('Run spec success');
            if($this->options['all_after_pass']){
                $this->log('Run all specs after pass');
                $this->runAll();
            }
        }else{
            $this->log('<log-error>Run spec failed</log-error>');
        }
    }

    public function hasSpecFile(SplFileInfo $path)
    {
        //find by relative path first
        $absPath = realpath($path);
        $rpath = $path->getRelativePathname();
        $baseDir = rtrim(str_replace($rpath,'',$absPath),'\\/');

        $pattern = '#^(\w+)\/(.*)\.php$#';
        preg_match($pattern,$rpath,$matches);

        if($matches[1]=='spec'){
            return true;
        }
        if(false!==strpos($rpath,'Spec.php')){
            return true;
        }

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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'format' => 'pretty',
            'ansi' => true,
            'all_after_pass' => false,
            'import_suites' => false,
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
            $watcher = new Watcher();
            $watcher->setOptions(array(
                'pattern' => $pattern,
                'tags' => $name
            ));
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
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

use PhpGuard\Application\Exception\ConfigurationException;
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Application\Watcher;
use PhpGuard\Listen\Util\PathUtil;
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
        if(!is_null($this->logger)){
            $options = $watcher->getOptions();
            $this->logger->addDebug('added watcher pattern: '.$options['pattern']);
        }

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
        $container = $this->container;
        if(class_exists('PhpSpec\\Console\\Application')){
            // only load command when phpspec package exists
            /* @var \PhpGuard\Application\Console\Application $application */

            $application = $container->get('ui.application');
            $command = new DescribeCommand();
            $command->setContainer($this->container);
            $application->add($command);
        }

        if($this->options['import_suites']){
            $this->importSuites();
        }

        $logger = $this->logger;
        $options = $this->options;

        $container->setShared('phpspec.inspector',function($c) use($logger,$options){
            $inspector = new Inspector();
            $inspector->setLogger($logger);
            $inspector->setContainer($c);
            $inspector->setOptions($options);
            return $inspector;
        });
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
        $container = $this->container;
        $inspector = $container->get('phpspec.inspector');

        $tags = $container->getParameter('filter.tags',array());
        if(empty($tags)){
            return $inspector->runAll();
        }

        $suites = $container->getParameter('phpspec.suites',array());
        $paths = $this->getPathOfSuites($tags,$suites);
        if(empty($suites) || empty($paths)){
            return array();
        }
        return $inspector->runFiltered($paths);
    }

    private function getPathOfSuites($tags,$suites)
    {
        $paths = array();
        foreach($tags as $tag){
            if(isset($suites[$tag])){
                $paths[] = $suites[$tag]['spec_path'];
            }
        }
        return $paths;
    }

    public function run(array $paths = array())
    {
        $specFiles = array();
        foreach($paths as $file)
        {
            /*$specFile = $this->getSpecFile($file);
            if(false===$specFile){
                $message = 'Spec file not found for <comment>'.$file->getRelativePathname().'</comment>';
                $this->logger->addDebug($message);
                continue;
            }*/
            $spl = PathUtil::createSplFileInfo(getcwd(),$file);

            $specFiles[] = $spl->getRelativePathname();
        }
        if(count($specFiles)>0){
            $inspector = $this->container->get('phpspec.inspector');
            return $inspector->run($specFiles);
        }
    }

    public function getSpecFile(SplFileInfo $path)
    {
        if(false!==strpos($path,'Spec.php')){
            return $path;
        }
        /* @var \PhpGuard\Application\Util\Locator $locator */
        $locator = $this->container->get('locator');
        $class = $locator->findClass($path->getRealPath(),false);

        $spec = 'spec\\'.$class.'Spec';
        $this->logger->debug('locator: '.$class);

        $specFile = $locator->findClassFile($spec,getcwd());

        $this->logger->addDebug($spec." file:".$specFile);
        return $specFile;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => '--format pretty --ansi',
            'all_on_start' => false,
            'all_after_pass' => false,
            'keep_failed' => false,
            'import_suites' => false, // import suites as watcher
            'always_lint' => true,
            'run_all' => array(
                'format' => 'progress',
                'cli' => '--format dot --ansi'
            )
        ));
    }

    public function importSuites()
    {
        $path = null;
        if(is_file($file=getcwd().'/phpspec.yml')){
            $path = $file;
        }
        elseif(is_file($file=getcwd().'/phpspec.yml.dist')){
            $path = $file;
        }

        if(is_null($path)){
            return;
        }

        $config = Yaml::parse($path);

        if(!isset($config['suites'])){
            return;
        }

        $suites = array();

        foreach($config['suites'] as $name=>$definition){
            $suites[$name]=$this->parseDefinition($name,$definition);
        }

        $this->container->setParameter('phpspec.suites',$suites);
    }

    private function parseDefinition($tags,$definition)
    {
        /* @var \PhpGuard\Application\Util\Locator $locator */
        $locator = $this->container->get('locator');

        $definition = $this->normalizePhpSpecConfig($definition);

        $tags = array($tags);
        $specPrefix = isset($definition['spec_prefix']) ? $definition['spec_prefix']:'spec';
        $specPath = isset($definition['spec_path']) ? $definition['spec_path']:null;

        $namespace = $definition['namespace'];

        //$path = $locator->getPathOfNamespace($namespace);
        //if($path){
        //    $watcher = $this->createWatcher($path,$tags);
        //    $this->addWatcher($watcher);
        //}
        $specDir = getcwd().DIRECTORY_SEPARATOR.$specPath;
        $specDir   = rtrim($specDir,'\\/');
        $this->addWatcher($this->createWatcher($specDir,$tags));
        $locator->addPsr4($specPrefix.'\\',$specDir);
        $this->logger->addDebug('Locator add prefix: '.$specPrefix.' dir: '.$specDir.DIRECTORY_SEPARATOR.$specPrefix);
        $psr4 = isset($definition['psr4_prefix']) ? $definition['psr4_prefix']:null;

        return array(
            'namespace' => $namespace,
            'psr4_prefix' => $psr4,
            'spec_path' => $specPath,
            'prefix' => $specPrefix,
            //'src' => $path
        );
    }

    private function normalizePhpSpecConfig($definition)
    {
        $norm = array();
        foreach($definition as $key=>$val){
            $key = strtolower($key);
            $norm[$key] = $val;
        }
        return $norm;
    }

    private function createWatcher($dir,$tags,$suffix='\.php')
    {
        $dir = ltrim(str_replace(getcwd(),'',$dir),'\\/');
        $pattern = '#^'.str_replace('/','\/',$dir).'\/(.+)'.$suffix.'$#';
        $options = array(
            'pattern' => $pattern,
            'tags' => $tags
        );
        $watcher = new Watcher($this->container);
        $watcher->setOptions($options);
        return $watcher;
    }
}
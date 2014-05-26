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
    const CACHE_DIR = '/phpguard/cache/plugin-phpspec';
    protected $suites = array();

    /**
     * @var Inspector
     */
    protected $inspector;

    public function __construct()
    {
        // set default options for phpspec plugin
        $this->setOptions(array());
    }

    public function addWatcher(Watcher $watcher)
    {
        parent::addWatcher($watcher);
        if(!is_null($this->logger)){
            $this->logger->addDebug('added watcher ',$watcher->getOptions());
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
        $inspector = new Inspector();
        $inspector->setLogger($logger);
        $inspector->setContainer($container);
        $inspector->setOptions($this->options);
        $this->inspector = $inspector;
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
        return $this->inspector->runAll();
    }

    public function run(array $paths = array())
    {
        $specFiles = array();
        foreach($paths as $file)
        {
            $specFile = $this->getSpecFile($file);
            if(false===$specFile){
                $message = 'Spec file not found for <comment>'.$file->getRelativePathname().'</comment>';
                $this->logger->addDebug($message);
                continue;
            }
            $spl = PathUtil::createSplFileInfo(getcwd(),$specFile);
            $specFiles[] = $spl->getRelativePathname();
        }
        if(count($specFiles)>0){
            return $this->inspector->run($specFiles);
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

        $this->suites = $suites = $config['suites'];

        foreach($suites as $name=>$definition){
            $this->parseDefinition($name,$definition);
        }
        return $suites;
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

        $path = $locator->getPathOfNamespace($namespace);
        if($path){
            $watcher = $this->createWatcher($path,$tags);
            $this->addWatcher($watcher);
        }
        $specDir = getcwd().DIRECTORY_SEPARATOR.$specPath;
        $specDir   = rtrim($specDir,'\\/').DIRECTORY_SEPARATOR.$specPrefix;
        $this->addWatcher($this->createWatcher($specDir,$tags,'Spec.php'));
        $locator->addPsr4($specPrefix.'\\',$specDir);
        $this->logger->addDebug('Locator add prefix: '.$specPrefix.' dir: '.$specDir);
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
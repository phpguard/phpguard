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
        return $suites;
    }
}
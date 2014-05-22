<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Tests;

use PhpGuard\Plugins\PhpSpec\Bridge\Console\Application as SpecApplication;
use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use PhpGuard\Application\Spec\ObjectBehavior as ob;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Inspector;

abstract class TestCase extends FunctionalTestCase
{
    /**
     * @var SpecApplication
     */
    static $specApp;

    /**
     * @var ApplicationTester
     */
    static $specTester;

    static $inspector;

    /**
     * @param bool $rebuild
     *
     * @return Inspector
     */
    protected function getInspector($rebuild=false)
    {
        if(!is_null(static::$inspector) && !$rebuild){
            return static::$inspector;
        }
        $container = static::getApplication()->getContainer();
        $phpspec = $container->get('plugins.phpspec');
        $logger = new Logger('Inspector');
        $logger->pushHandler($container->get('logger.handler'));

        $inspector = new Inspector();
        $inspector->setContainer($container);
        $inspector->setLogger($logger);
        $inspector->setOptions($phpspec->getOptions());

        static::$inspector = $inspector;
        return static::$inspector;
    }

    public function getSpecApplication()
    {
        if(is_null(static::$specApp)){
            $app = new SpecApplication('2.0.0-DEV');
            $app->setAutoExit(false);
            $app->setCatchExceptions(true);
            $app->setInspector($this->getInspector());
            static::$specApp = $app;
        }
        return static::$specApp;
    }

    public function getSpecTester()
    {
        if(is_null(self::$specTester)){
            $tester = new ApplicationTester($this->getSpecApplication());
            static::$specTester = $tester;
        }
        return static::$specTester;
    }

    public function getSpecDisplay()
    {
        return $this->getSpecTester()->getDisplay();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::rebuildApplication();
    }

    static public function rebuildApplication()
    {
        self::$tmpDir = sys_get_temp_dir().'/phpguard-phpspec';
        ob::cleanDir(self::$tmpDir);
        ob::mkdir(self::$tmpDir);
        chdir(self::$tmpDir);
        self::buildFixtures();
        self::createApplication();
        $app = self::$app;
        /*$app->getContainer()->setShared('plugins.phpspec',function($c){
            $plugin = new PhpSpecPlugin();
            $plugin->setContainer($c);
            $plugin->configure();
            return $plugin;
        });*/
    }

    static public function buildFixtures($prefix=null)
    {
        $finder = Finder::create();
        $finder->in(__DIR__.'/fixtures');

        foreach($finder->files() as $file){
            $target = self::$tmpDir.$prefix.'/'.$file->getRelativePathname();
            ob::mkdir(dirname($target));
            copy($file,$target);
            if(false!==strpos($target,'.php')){
                require_once($target);
            }
        }

        $finder = new ExecutableFinder();
        if(!is_executable($executable = $finder->find('composer.phar'))){
            $executable = $finder->find('composer');
        }
        if(!is_executable($executable)){
            //$this->markTestSkipped('Composer executable not found');
            return;
        }
        $process = new Process($executable.' dumpautoload');
        $process->run();
        if($process->getExitCode()!==0){
            //$this->markTestSkipped('Composer failed to dumpautoload');
        }
    }

    protected function getClassContent($namespace,$class)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $content = <<<EOF
<?php

// created at {$time}
namespace {$namespace};

class {$class}
{
}
EOF;
        return $content;

    }

    protected function getSpecContent($namespace,$class)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $specClass = strtr($namespace.'\\'.$class,array(
            'spec\\' => '',
            'Spec\\' => '',
        ));

        $specClass = substr($specClass,0,strlen($specClass)-4);

        $content = <<<EOF
<?php

// created at {$time}
namespace {$namespace};

use PhpSpec\ObjectBehavior;

class {$class} extends ObjectBehavior
{
    function it_is_initializable()
    {
        \$this->shouldHaveType("{$specClass}");
    }
}
EOF;
        return $content;
    }

    protected function createSpecFile($target,$namespace,$class)
    {
        $content = $this->getSpecContent($namespace,$class);
        $target = static::$tmpDir.'/'.$target;
        file_put_contents($target,$content);

        return $target;
    }
}
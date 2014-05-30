<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Functional;

use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Application\Util\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use PhpGuard\Plugins\PhpSpec\Inspector;

abstract class TestCase extends FunctionalTestCase
{
    static $composerOutput;

    static public function setUpBeforeClass()
    {
        static::buildFixtures();
    }

    static public function buildFixtures($type='psr0')
    {
        static::$tmpDir = sys_get_temp_dir().'/phpguard-test/'.uniqid('phpguard');
        static::cleanDir(static::$tmpDir);
        static::mkdir(static::$tmpDir);
        static::createApplication();
        if(is_null(static::$cwd)){
            static::$cwd = getcwd();
        }
        chdir(static::$tmpDir);

        $finder = Finder::create();
        Filesystem::copyDir(__DIR__.'/fixtures/'.$type,static::$tmpDir,$finder);

        $exFinder = new ExecutableFinder();
        if(!is_executable($executable=$exFinder->find('composer.phar'))){
            $executable = $exFinder->find('composer');
        }
        chdir(static::$tmpDir);
        $process = new Process($executable.' dumpautoload');
        $process->run();
        static::$composerOutput = $process->getOutput();
    }

    protected function buildClass($target,$class)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');

        $exp = explode('\\',$class);
        $class = array_pop($exp);
        $namespace = implode('\\',$exp);
        $content = <<<EOF
<?php

// created at {$time}
// file: %relative_path%
namespace {$namespace};

class {$class}
{
}
EOF;
        $absPath = static::$tmpDir.DIRECTORY_SEPARATOR.$target;
        $dir = dirname($absPath);
        static::mkdir($dir);
        file_put_contents($absPath,$content,LOCK_EX);
    }

    private function getSpecContent($class,$content)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $exp = explode('\\',$class);
        $class = array_pop($exp);
        $namespace = implode('\\',$exp);
        $content = <<<EOF
<?php

// created at {$time}
// relative path: %relative_path%
namespace {$namespace};

use PhpSpec\ObjectBehavior;

class {$class} extends ObjectBehavior
{
    function it_should_do_something()
    {
        {$content}
    }
}
EOF;
        return $content;
    }

    protected function buildSpec($target,$class,$content)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $content = $this->getSpecContent($class,$content);
        $content = str_replace('%relative_path%',$target,$content);
        $target = static::$tmpDir.'/'.$target;
        $dir = dirname($target);
        static::mkdir($dir);

        file_put_contents($target,$content);
        return $target;
    }

    protected function clearCache()
    {
        @unlink(Inspector::getCacheFileName());
        @unlink(Inspector::getErrorFileName());
    }
}
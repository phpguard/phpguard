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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Inspector;

abstract class TestCase extends FunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        static::buildFixtures();
        parent::setUpBeforeClass();
    }

    static public function buildFixtures($src='psr0')
    {
        static::$tmpDir = $tmpDir = sys_get_temp_dir().'/phpguard-test/'.uniqid('phpspec-'.$src.'-');
        static::cleanDir($tmpDir);
        static::mkdir($tmpDir);
        $finder = Finder::create();
        $finder->in(__DIR__.'/fixtures/'.$src);
        foreach($finder->files() as $file){
            $target = static::$tmpDir.DIRECTORY_SEPARATOR.$file->getRelativePathname();
            static::mkdir(dirname($target));
            copy($file,$target);
            if(false!==strpos($target,'.php')){
                //require_once($target);
            }
        }
        chdir(static::$tmpDir);
        $finder = new ExecutableFinder();
        if(!is_executable($executable = $finder->find('composer.phar'))){
            $executable = $finder->find('composer');
        }
        if(!is_executable($executable)){
            return;
        }
        $process = new Process($executable.' dumpautoload');
        $process->run();
        if($process->getExitCode()!==0){
            throw new \PHPUnit_Framework_IncompleteTestError('Composer failed to dump autoload');
        }
    }

    protected function getClassContent($namespace,$class)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $content = <<<EOF
<?php

// created at {$time}
// file: %relative_path%
namespace {$namespace};

class {$class}
{
}
EOF;
        return $content;

    }

    protected function buildClass($target,$class)
    {
        $exp = explode('\\',$class);
        $class = array_pop($exp);
        $namespace = implode('\\',$exp);
        $content = $this->getClassContent($namespace,$class);
        $content = str_replace('%relative_path%',$target,$content);
        $absPath = static::$tmpDir.DIRECTORY_SEPARATOR.$target;
        $dir = dirname($absPath);
        static::mkdir($dir);
        file_put_contents($absPath,$content,LOCK_EX);
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
        \$this->shouldHaveType('{$specClass}');
    }
}
EOF;
        return $content;
    }

    protected function createSpecFile($target,$namespace,$class)
    {
        $content = $this->getSpecContent($namespace,$class);
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
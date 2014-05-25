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
        parent::setUpBeforeClass();
        static::buildFixtures();
    }

    static public function buildFixtures($prefix=null)
    {
        $finder = Finder::create();
        $finder->in(__DIR__.'/fixtures');

        foreach($finder->files() as $file){
            $target = static::$tmpDir.$prefix.'/'.$file->getRelativePathname();
            static::mkdir(dirname($target));
            copy($file,$target);
            if(false!==strpos($target,'.php')){
                //require_once($target);
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
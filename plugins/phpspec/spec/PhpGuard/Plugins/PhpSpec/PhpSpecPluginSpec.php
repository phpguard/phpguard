<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Event\EvaluateEvent;
use \PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Linter\LinterInterface;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Plugins\PhpSpec\Inspector;
use Prophecy\Argument;
use Symfony\Component\Finder\Finder;

class PhpSpecPluginSpec extends ObjectBehavior
{
    static $cwd;
    static $fixturesDir;

    protected function buildFixtures()
    {
        self::cleanDir(self::$fixturesDir);
        $finder = Finder::create();
        $finder->in(__DIR__.'/Resources/fixtures');
        foreach($finder->files() as $path)
        {
            $rPath = $path->getRelativePathname();
            $target = self::$fixturesDir.DIRECTORY_SEPARATOR.$rPath;
            self::mkdir(dirname($target));
            copy($path->getRealpath(),$target);
            if(false!==strpos($target,'.php')){
                require_once $target;
            }
        }
    }

    function let(
        ContainerInterface $container,
        LinterInterface $linter,
        Logger $logger,
        Locator $locator
    )
    {
        $container->has('linters.php')
            ->willReturn(true);
        $container->get('linters.php')
            ->willReturn($linter);
        $container->get('locator')
            ->willReturn($locator)
        ;

        // initialize default options
        $this->setOptions(array());
        $container->get('logger')
            ->willReturn($logger);

        $this->setContainer($container);
        $this->setLogger($logger);

        self::mkdir(self::$tmpDir);
        if(is_null(self::$cwd)){
            self::$cwd = getcwd();
        }

        if(is_null(self::$fixturesDir)){
            self::$fixturesDir = self::$tmpDir.'/phpspec-plugin';
        }
    }

    function letgo()
    {
        chdir(self::$cwd);
        self::cleanDir(self::$tmpDir);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\PhpSpecPlugin');
    }

    function it_should_be_the_PhpSpec_plugin()
    {
        $this->getName()->shouldReturn('phpspec');
        $this->shouldHaveType('PhpGuard\\Application\\Plugin\\Plugin');
    }

    function it_should_set_default_options_properly()
    {
        $this->setOptions(array());

        $options = $this->getOptions();

        $options->shouldHaveKey('run_all');
        $options->shouldHaveKey('cli');
        $options->shouldHaveKey('all_after_pass');
        $options->shouldHaveKey('keep_failed');
        $options->shouldHaveKey('all_on_start');
    }
}
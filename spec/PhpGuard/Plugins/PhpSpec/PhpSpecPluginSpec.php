<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

require_once __DIR__.'/MockPhpSpecPlugin.php';

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Runner;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Finder\Finder;

class PhpSpecPluginSpec extends ObjectBehavior
{
    static $cwd;
    static $fixturesDir;

    function let(
        ContainerInterface $container,
        Runner $runner,
        PhpGuard $phpGuard
    )
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpSpecPlugin');
        $this->setRunner($runner);

        // initialize default options
        $this->setOptions(array());
        $runner->setCommand('phpspec')
            ->willReturn(true);
        $runner->setArguments(Argument::any())
            ->willReturn(true);

        $container->get('phpguard')
            ->willReturn($phpGuard);

        $this->setContainer($container);

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
        $options->shouldHaveKey('format');
        $options->shouldHaveKey('all_after_pass');
    }

    function it_should_run_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $this->setOptions(array(
            'format' => 'dot'
        ));

        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled()
        ;
        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->run(array($spl));
    }

    function it_should_run_all_after_pass(Runner $runner,PhpGuard $phpGuard)
    {
        $this->setOptions(array(
            'all_after_pass' => true,
            'format' => 'dot',
            'run_all' => array(
                'format' => 'progress'
            )
        ));
        $runner->run()
            ->willReturn(true);
        $runner->setCommand('phpspec')
            ->shouldBeCalled()
        ;
        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled()
        ;
        $runner->setArguments(Argument::containing('--format=progress'))
            ->shouldBeCalled()
        ;

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);


        $this->run(array($spl));
    }

    function it_should_log_error_when_failed_to_run(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->willReturn(false);

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->run(array($spl));
    }

    function it_should_run_all_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $this->setOptions(array(
            'run_all' => array(
                'format' => 'dot'
            )
        ));
        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled();


        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->runAll(array($spl));
    }

    function it_should_log_error_when_failed_to_run_all(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->willReturn(false);

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->runAll(array($spl));
    }

    function it_should_configured_properly(
        ContainerInterface $container,
        Application $application
    )
    {
        $container->get('ui.application')
            ->willReturn($application);

        $application->add(Argument::any())
            ->shouldBeCalled();
        $this->setOptions(array(
            'import_suites' => true,
        ));
        chdir($dir = self::$tmpDir);
        file_put_contents($dir.'/phpspec.yml',$this->getPhpSpecFileContent(),LOCK_EX);
        $this->configure();
        $this->getWatchers()->shouldHaveCount(2);
    }

    protected function getPhpSpecFileContent()
    {
        $content = <<<EOF
suites:
    Namespace1: { namespace: Namespace1, spec_path: src/Namespace1 }
    Namespace2: { namespace: Namespace2, src: src/Namespace2 }
EOF;
        return $content;
    }

    function it_should_import_suites_from_phpspec_file()
    {
        chdir(self::$tmpDir);

        // no configuration file in the cwd
        $this->importSuites()->shouldReturn(null);
        $this->getWatchers()->shouldHaveCount(0);

        // configuration file exists but with no suites configuration
        $file = self::$tmpDir.'/phpspec.yml';
        touch($file);
        $this->importSuites()->shouldReturn(null);
        $this->getWatchers()->shouldHaveCount(0);

        // configuration file exists with suites configuration
        file_put_contents($file,$this->getPhpSpecFileContent(),LOCK_EX);
        $this->importSuites();
        $this->getWatchers()->shouldHaveCount(2);
    }

    function it_should_import_suites_from_phpspec_dist_file(
        EvaluateEvent $event,
        ContainerInterface $container
    )
    {
        chdir(self::$tmpDir);
        file_put_contents(self::$tmpDir.'/phpspec.yml.dist',$this->getPhpSpecFileContent());
        $this->importSuites();
        $this->getWatchers()->shouldHaveCount(2);

        self::mkdir($dir1 = self::$tmpDir.'/src/Namespace1');
        self::mkdir($dir2 = self::$tmpDir.'/src/Namespace2');
        touch($file1 = $dir1.'/Class.php');
        touch($file2 = $dir2.'/Class.php');

        $container->getParameter('filter.tags',Argument::any())
            ->willReturn(array())
        ;
        $event->getFiles()
            ->willReturn(array($file1,$file2));
        $this->getMatchedFiles($event)->shouldHaveCount(2);

        // edge cases section

        // test for Namespace1
        $container->getParameter('filter.tags',Argument::any())
            ->willReturn(array('Namespace1'))
        ;
        $event->getFiles()
            ->willReturn(array($file1,$file2));
        $this->getMatchedFiles($event)->shouldHaveCount(1);
        $matched = $this->getMatchedFiles($event);
        $matched[0]->getRelativePathname()->shouldReturn('src/Namespace1/Class.php');

        // test for Namespace2
        $container->getParameter('filter.tags',Argument::any())
            ->willReturn(array('Namespace2'))
        ;
        $event->getFiles()
            ->willReturn(array($file1,$file2));
        $this->getMatchedFiles($event)->shouldHaveCount(1);
        $matched = $this->getMatchedFiles($event);
        $matched[0]->getRelativePathname()->shouldReturn('src/Namespace2/Class.php');
    }

    function its_hasSpecFile_returns_true_if_spec_file_exists()
    {
        self::buildFixtures();
        chdir(self::$fixturesDir);

        $this->importSuites();

        $spl = PathUtil::createSplFileInfo(
            getcwd(),getcwd().'/src/Namespace1/Class.php'
        );
        $this->shouldHaveSpecFile($spl);

        $spl = PathUtil::createSplFileInfo(
            getcwd(),getcwd().'/src/Namespace2/Class.php'
        );
        $this->shouldHaveSpecFile($spl);

        $spl = PathUtil::createSplFileInfo(
            getcwd(),getcwd().'/src/Namespace3/Class.php'
        );
        $this->shouldHaveSpecFile($spl);
    }

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
        }
    }
}
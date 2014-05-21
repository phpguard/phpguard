<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class ConfigurationSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,PhpGuard $guard, PluginInterface $plugin)
    {
        self::cleanDir(self::$tmpDir);
        self::mkdir(self::$tmpDir);
        $container->get('phpguard')
            ->willReturn($guard)
        ;
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Configuration');
    }

    function it_throws_when_configuration_file_not_exist()
    {
        $this
            ->shouldThrow('RuntimeException')
            ->duringCompileFile('foo.yml');
    }

    function it_should_not_process_empty_file()
    {
        $this->compile('')->shouldReturn(null);
    }

    function it_should_process_global_section(ContainerInterface $container,PhpGuard $guard)
    {
        $guard->setOptions(array(
                'ignores' => 'app/cache'
            ))
            ->shouldBeCalled()
        ;

        $text = <<<EOF
phpguard:
    ignores: app/cache
EOF;
        $this->compile($text);
    }

    function it_should_process_plugin_section(ContainerInterface $container, PluginInterface $pspec, PluginInterface $plugin)
    {
        $container->has('plugins.phpspec')
            ->willReturn(true)
        ;
        $container->get('plugins.phpspec')
            ->willReturn($plugin)
        ;

        $plugin->setOptions(array(
            'all_on_success'=>true,
            'formatter' => 'progress',
            'ansi'=>true
        ))
            ->shouldBeCalled()
        ;
        $plugin->addWatcher(Argument::any())->shouldBeCalled();
        $plugin->setActive(true)
            ->shouldBeCalled();

        $text = <<<EOF

phpspec:
    options:
        all_on_success: true
        formatter: progress
        ansi: true
    watch:
        - { pattern: "#^spec\/.*\.php$#" }
        - { pattern: "#^src\/.*\.php$#" }

EOF;
        touch($file = self::$tmpDir.'/test.yml');
        file_put_contents($file,$text,LOCK_EX);

        $this->compileFile($file);
    }

    function it_throws_when_plugin_not_exists(
        ContainerInterface $container
    )
    {
        $container->has('plugins.some')
            ->willReturn(false);

        $text = <<<EOF
some:
    watch:
        - { pattern: "#^spec\/.*\.php" }
EOF;

        $this->shouldThrow('InvalidArgumentException')
            ->duringCompile($text)
        ;
    }
}
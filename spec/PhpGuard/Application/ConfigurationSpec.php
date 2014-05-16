<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Guard;
use PhpGuard\Application\Container;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\Watcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfigurationSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,Guard $guard, PluginInterface $plugin)
    {
        $container->get('guard')
            ->willReturn($guard)
        ;
        $container->has(Argument::any())
            ->willReturn(true)
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

    function it_should_process_global_section(ContainerInterface $container,Guard $guard)
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
        $container->has('guard.plugins.phpspec')
            ->willReturn(true)
        ;
        $container->get('guard.plugins.phpspec')
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

        $this->compile($text);
    }
}
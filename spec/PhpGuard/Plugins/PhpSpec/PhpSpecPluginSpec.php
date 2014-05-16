<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhpSpecPluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\PhpSpecPlugin');
    }

    function it_should_set_default_options_properly(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'format' => 'pretty',
                'ansi' => true
            ))
            ->shouldBeCalled()
        ;
        $this->setDefaultOptions($resolver);
    }
}
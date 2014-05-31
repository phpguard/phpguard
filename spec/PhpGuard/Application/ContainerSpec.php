<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Spec\ObjectBehavior;

include __DIR__ . '/ContainerAwareMock.php';

class ContainerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Container');
    }

    public function it_should_implement_the_ContainerInterface()
    {
        $this->shouldImplement('PhpGuard\Application\Container\ContainerInterface');
    }

    public function it_stores_parameters()
    {
        $this->setParameter('some','value');
        $this->getParameter('some')->shouldReturn('value');
    }

    public function it_returns_null_value_for_unexisting_parameter()
    {
        $this->getParameter('none')->shouldReturn(null);
    }

    public function it_returns_custom_default_value_for_unexisting_parameter_if_provided()
    {
        $this->getParameter('none','value')->shouldReturn('value');
    }

    public function it_should_tell_when_parameter_are_already_defined()
    {
        $this->setParameter('some','value');
        $this->hasParameter('some')->shouldReturn(true);
    }

    public function it_stores_services($service)
    {
        $this->set('name',$service);
        $this->get('name')->shouldReturn($service);
    }

    public function it_provides_a_way_to_retrieve_services_by_prefix($service1, $service2, $service3)
    {
        $this->set('collection1.serv1', $service1);
        $this->set('collection1.serv2', $service2);
        $this->set('collection2.serv3', $service3);

        $this->getByPrefix('collection1')->shouldReturn(array($service1, $service2));
        $this->getByPrefix('none')->shouldReturn(array());
    }

    public function it_should_tell_if_service_registered_or_not($service)
    {
        $this->set('name',$service);
        $this->has('name')->shouldReturn(true);
        $this->has('none')->shouldReturn(false);
    }
    public function it_throws_exception_when_trying_to_set_an_invalid_service()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringSet('id','none');
    }

    public function it_throws_exception_when_trying_to_get_unexisting_service()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringGet('none');
    }

    public function it_should_set_container_when_service_is_an_implement_of_ContainerInterface(\ContainerAwareMock $mock)
    {
        $mock->setContainer($this)->shouldBeCalled();

        $this->set('mock',$mock);
        $this->get('mock')->shouldReturn($mock);
    }

    public function it_evaluates_factory_function_set_as_service()
    {
        $this->set('random_number', function () { return rand(); });
        $number1 = $this->get('random_number');
        $number2 = $this->get('random_number');

        $number1->shouldBeInteger();
        $number2->shouldBeInteger();

        $number2->shouldNotBe($number1);
    }

    public function it_evaluates_factory_function_only_once_for_shared_services()
    {
        $this->setShared('random_number', function () { return rand(); });
        $number1 = $this->get('random_number');
        $number2 = $this->get('random_number');

        $number2->shouldBe($number1);
    }

    public function it_throws_when_set_shared_service_with_an_invalid_type()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringSetShared('id','foo');
    }

}

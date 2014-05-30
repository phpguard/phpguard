<?php

namespace Test;

class FooTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldSucceed()
    {
        $this->assertTrue(true);
    }

    public function testShouldFailed()
    {
        $this->assertTrue(false);
    }

    public function testShouldBroken()
    {
        throw new \Exception('bar');
    }

    public function testShouldIncomplete()
    {
        $this->markTestIncomplete();
    }

    public function testShouldSkipped()
    {
        $this->markTestSkipped();
    }
}
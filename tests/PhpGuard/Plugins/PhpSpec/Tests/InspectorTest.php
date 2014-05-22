<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Tests;

class InspectorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        static::$tester->run(array('-vvv'=>''));
    }

    public function testShouldRunWithClassName()
    {
        $this->getApplicationTester()->run(array('-vvv'=>''));
        $inspector = $this->getInspector(true);

        $inspector->runAll();
        // test
        $display = $this->getDisplay();
        $this->assertContains('3 passed',$display);
    }

    public function testShouldKeepRunningFailedSpec()
    {
        $this->createSpecFile('spec/PhpSpecTest1/FooSpec.php','spec\\PhpSpecTest1','FooSpec');
        $this->createSpecFile('spec/PhpSpecTest1/BarSpec.php','spec\\PhpSpecTest1','BarSpec');

        $inspector = $this->getInspector();
        $inspector->runAll();

        $display = $this->getDisplay();
        $this->assertContains('2 broken',$display);

        // clear display
        $this->getApplicationTester()->run(array('-vvv'=>''));
        $inspector->runAll();
        $display = $this->getDisplay();
        $this->assertNotContains('TestClass',$display);
        $this->assertContains('Foo',$display);
        $this->assertContains('Bar',$display);

        unlink(getcwd().'/spec/PhpSpecTest1/BarSpec.php');
        $this->getApplicationTester()->run(array('-vvv'=>''));
        $inspector->runAll();
        $display = $this->getDisplay();
        $this->assertNotContains('TestClass',$display);
        $this->assertContains('Foo',$display);
        $this->assertNotContains('Bar',$display);

        $options = $this->getApplication()->getContainer()->get('plugins.phpspec')->getOptions();
        $options['keep_failed'] = false;
        $inspector->setOptions($options);
        $this->getApplicationTester()->run(array('-vvv'=>''));
        $inspector->runAll();
        $display = $this->getDisplay();
        $this->assertNotContains('Bar',$display);
        $this->assertContains('Foo',$display);
        $this->assertContains('3 passed',$display);
    }
}

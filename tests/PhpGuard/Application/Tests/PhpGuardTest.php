<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;


use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Spec\ObjectBehavior as ob;

class PhpGuardTest extends FunctionalTestCase
{
    public function testShouldConfigureOptions()
    {
        $phpGuard = new PhpGuard();

        $phpGuard->setOptions(array(
            'ignores' => 'test'
        ));
        $options = $phpGuard->getOptions();
        $this->assertSame(array('test'),$options['ignores']);

        $ignores = array('foo','bar');
        $phpGuard->setOptions(array(
            'ignores' => $ignores
        ));
        $options = $phpGuard->getOptions();
        $this->assertSame($ignores,$options['ignores']);
    }

    public function testShouldSetupListenProperly()
    {
        $listener = $this->app->getContainer()->get('listen.listener');
        $this->assertInstanceOf('PhpGuard\\Listen\\Listener',$listener);
        $this->assertEquals(doubleval(0.01*1000000),$listener->getLatency());
        $this->assertContains('foo',$listener->getIgnores());
    }

    public function testShouldLoadPlugins()
    {
        $container = $this->app->getContainer();
        $this->assertTrue($container->get('plugins.test')->isActive());
    }

    public function testShouldMonitorBasedOnTags()
    {
        ob::mkdir($dirTag1 = self::$tmpDir.'/tag1');
        ob::mkdir($dirTag2 = self::$tmpDir.'/tag2');

        $this->tester->run(array('--tags'=>'tag1'));
        touch($ftag1 = $dirTag1.'/test1.php');
        touch($ftag2 = $dirTag2.'/test1.php');
        $this->getShell()->evaluate();
        $this->assertContains($ftag1,$this->getDisplay());
        $this->assertNotContains($ftag2,$this->getDisplay());

        $this->tester->run(array('--tags'=>'tag2'));
        touch($ftag1 = $dirTag1.'/test2.php');
        touch($ftag2 = $dirTag2.'/test2.php');
        $this->getShell()->evaluate();
        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertNotContains($ftag1,$this->getDisplay());

        $this->tester->run(array('--tags'=>'tag1,tag2'));
        touch($ftag1 = $dirTag1.'/test3.php');
        touch($ftag2 = $dirTag2.'/test3.php');
        $this->getShell()->evaluate();
        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertContains($ftag1,$this->getDisplay());
    }


}
 
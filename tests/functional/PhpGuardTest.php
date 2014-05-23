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

class PhpGuardTest extends TestCase
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
        $listener = self::$app->getContainer()->get('listen.listener');
        $this->assertInstanceOf('PhpGuard\\Listen\\Listener',$listener);
        $this->assertEquals(doubleval(0.01*1000000),$listener->getLatency());
        $this->assertContains('foo',$listener->getIgnores());
    }

    public function testShouldLoadPlugins()
    {
        $container = self::$app->getContainer();
        $this->assertTrue($container->get('plugins.test')->isActive());
    }

    public function testShouldMonitorBasedOnTags()
    {

        ob::cleanDir($dirTag1 = self::$tmpDir.'/tag1');
        ob::cleanDir($dirTag2 = self::$tmpDir.'/tag2');

        ob::mkdir($dirTag1);
        ob::mkdir($dirTag2);
        $ftag1 = $dirTag1.'/test1.php';
        $ftag2 = $dirTag2.'/test1.php';
        static::$tester->run(array('--tags'=>'tag1'));
        file_put_contents($ftag1,'Hello World');
        file_put_contents($ftag2,'Hello WOrld');
        static::getShell()->evaluate();
        $this->assertContains($ftag1,$this->getDisplay());
        $this->assertNotContains($ftag2,$this->getDisplay());


        static::$tester->run(array('--tags'=>'tag2'));
        touch($ftag1 = $dirTag1.'/test2.php');
        touch($ftag2 = $dirTag2.'/test2.php');

        static::getShell()->evaluate();
        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertNotContains($ftag1,$this->getDisplay());

        static::$tester->run(array('--tags'=>'tag1,tag2'));
        touch($ftag1 = $dirTag1.'/test3.php');
        touch($ftag2 = $dirTag2.'/test3.php');
        static::getShell()->evaluate();


        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertContains($ftag1,$this->getDisplay());//
    }


}
 
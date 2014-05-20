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
        chdir(__DIR__.'/fixtures');
        $app = $this->getApplication();
        $tester = $this->getApplicationTester($app);
        $tester->run(array());
        $listener = $app->getContainer()->get('listen.listener');
        $this->assertInstanceOf('PhpGuard\\Listen\\Listener',$listener);
        $this->assertEquals(doubleval(0.01*1000000),$listener->getLatency());
        $this->assertContains('foo',$listener->getIgnores());
    }

    public function testShouldLoadPlugins()
    {
        $this->buildFixtures();
        chdir(self::$tmpDir);
        $app = $this->getApplication();
        $tester = $this->getApplicationTester($app);
        $tester->run(array());

        $container = $app->getContainer();
        $this->assertTrue($container->get('plugins.test')->isActive());
    }
}
 
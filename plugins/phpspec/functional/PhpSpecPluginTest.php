<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Functional;


class PhpSpecPluginTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        static::getTester()->run('-vvv');

    }

    public function testShouldRunAllSpecs()
    {
        $this->assertFileExists(getcwd().'/vendor/autoload.php');
        static::getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('3 passed');
    }

    /**
     * @dataProvider getRunFromSpecFile
     */
    public function testShouldRunPsr0Specs($class,$content,$expected,$prefix='spec')
    {
        $exp = explode('\\',$class);
        $file =$this->buildSpec(
            'src/psr0/'
                .$exp[0]
                .'/'.$prefix.'/psr0/'.str_replace('\\','/',$class).'.php'
            ,
            $prefix.'\\psr0\\'.$class,
            $content
        );
        $this->evaluate();
        //$this->assertEquals('foo',file_get_contents($file));
        $this->assertFileExists($file);
        $this->assertDisplayContains($expected);
    }

    public function getRunFromSpecFile()
    {
        return array(
            array(
                'namespace1\\TestClassSpec',
                '$this->shouldHaveType(\'psr0\namespace1\TestClass\');',
                '1 passed'
            ),
            array(
                'namespace2\\TestClassSpec',
                '$this->shouldHaveType(\'psr0\namespace2\TestClass\');',
                '1 passed'
            ),
            array(
                'namespace3\\TestClassSpec',
                '$this->shouldHaveType(\'psr0\namespace3\TestClass\');',
                '1 passed',
                'Spec'
            ),
            array(
                'namespace1\\TestFooSpec',
                '$this->shouldHaveType("psr0\\namespace1\\TestFoo");',
                '1 broken'
            ),
            array(
                'namespace1\\sub1\\TestFooSpec',
                '$this->shouldHaveType("psr0\\namespace1\\sub1\\TestFoo");',
                '1 broken'
            ),
            array(
                'namespace1\\sub1\\TestFooSpec',
                'throw new FatalErrorException("Fatal");',
                'Fatal Error'
            ),
        );
    }

    /**
     * @dataProvider getRunPsr4Specs
     * @group current
     */
    public function testShouldRunPsr4Specs($class,$content,$expected,$specPath='spec')
    {
        static::buildFixtures('psr4');
        $this->getTester()->run('-vvv');
        $exp = explode('\\',$class);
        $file =$this->buildSpec(
            $specPath.'/psr4/'
            .'/'.str_replace('\\','/',$class).'.php'
            ,
            'spec\\psr4\\'.$class,
            $content
        );
        $this->evaluate();
        //$this->assertEquals('foo',file_get_contents($file));
        $this->assertFileExists($file);
        $this->assertDisplayContains($expected);
    }

    public function getRunPsr4Specs()
    {
        return array(
            array(
                'namespace1\\FooSpec',
                '$this->shouldHaveType(\'psr4\namespace1\Foo\');',
                '1 passed',
                'spec'
            ),
            array(
                'namespace2\\FooSpec',
                '$this->shouldHaveType(\'psr4\namespace2\Foo\');',
                '1 passed',
                'some/namespace2/spec'
            ),
            array(
                'namespace3\\FooSpec',
                '$this->shouldHaveType(\'psr4\namespace3\Foo\');',
                '1 passed',
                'some/namespace3/spec'
            ),
        );
    }


    public function testShouldKeepRunningFailedSpec()
    {
        static::buildFixtures('psr4');
        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('3 passed');

        $this->buildSpec('spec/psr4/namespace1/FooSpec.php','spec\\psr4\\namespace1\\FooSpec','throw new \\Exception("something");');
        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('1 broken');
        $this->assertDisplayContains('2 passed');

        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('1 broken');
        $this->assertNotDisplayContains('2 passed');

        $this->buildSpec(
            'spec/psr4/namespace1/FooSpec.php',
            'spec\\psr4\\namespace1\\FooSpec',
            '$this->shouldHaveType(\'psr4\\namespace1\\Foo\');'
        );
        $this->getTester()->run('all phpspec -vvv');
        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('3 passed');
    }
}
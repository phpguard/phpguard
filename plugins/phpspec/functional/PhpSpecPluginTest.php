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
use PhpGuard\Plugins\PhpSpec\Inspector;

/**
 * Class PhpSpecPluginTest
 *
 * @package PhpGuard\Plugins\PhpSpec\Tests
 */
class PhpSpecPluginTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider getTestRun
     */
    public function testShouldRunSpecs($fileName,$className,$tags=null,$assertNot=false)
    {
        $this->buildPsr0();
        $args = '-vvv';
        if(!is_null($tags)){
            $args .= ' --tags='.$tags;
        }
        $this->getTester()->run($args);

        $file = static::$tmpDir.DIRECTORY_SEPARATOR.$fileName;

        $exp = explode("\\",$className);
        $class = array_pop($exp);
        $namespace = implode('\\',$exp);

        if(!is_dir($dir=dirname($file))){
            static::mkdir($dir);
        }

        file_put_contents($file,$this->getClassContent($namespace,$class));
        $this->evaluate();
        $this->assertDisplayContains($dir);
        if($assertNot){
            $this->assertNotDisplayContains('executing');
        }else{
            $this->assertDisplayContains('executing');
        }
    }

    public function getTestRun()
    {
        return array(
            array('src/psr0/namespace1/TestClass.php','psr0\\namespace1\\TestClass'),
            array('src/psr0/namespace2/TestClass.php','psr0\\namespace2\\TestClass'),
            array('src/psr0/namespace3/TestClass.php','psr0\\namespace3\\TestClass'),
            array('src/psr0/namespace1/TestTag1.php','psr0\\namespace1\\TestTag1','Tag1'),
            array('src/psr0/namespace2/TestTag2.php','psr0\\namespace2\\TestTag2','Tag1',true),
            array('src/psr0/namespace2/TestTag3.php','psr0\\namespace3\\TestTag3','Tag1',true),
            array('src/psr0/namespace2/TestTag4.php','psr0\\namespace2\\TestTag4','Tag1,Tag2'),
            array('src/psr0/namespace3/TestTag5.php','psr0\\namespace3\\TestTag5','Tag1,Tag2',true),
            array('src/psr0/namespace1/TestTag6.php','psr0\\namespace1\\TestTag6','Tag1,Tag2'),

        );
    }

    /**
     * @dataProvider getTestSpecFile
     *
     */
    public function testShouldRunFromSpecFile($specFile,$className)
    {
        $this->markTestIncomplete();
        static::createApplication();
        $this->getTester()->run('-vvv');
        $file = self::$tmpDir.DIRECTORY_SEPARATOR.$specFile;
        $exp = explode('\\',$className);
        $class = array_pop($exp).'Spec';
        $namespace = implode('\\',$exp);
        $content = $this->getSpecContent($namespace,$class);

        file_put_contents($file,$content,LOCK_EX);
        $this->evaluate();

        $display = $this->getDisplay();
        $this->assertContains($specFile,$display);

    }

    public function getTestSpecFile()
    {
        return array(
            array('src/psr0/namespace1/spec/psr0/namespace1/TestClassSpec.php','spec\\psr0\\namespace1\\TestClass'),
            array('src/psr0/namespace2/spec/psr0/namespace2/TestClassSpec.php','spec\\psr0\\namespace2\\TestClass'),
            array('src/psr0/namespace3/Spec/psr0/namespace3/TestClassSpec.php','Spec\\psr0\\namespace3\\TestClass'),
        );
    }

    public function testShouldLogFailedMessage()
    {
        $this->markTestIncomplete();

        $this->buildPsr0();
        $this->getTester()->run('-vvv');
        $file = static::$tmpDir.'/src/psr0/namespace1/spec/psr0/namespace1/NotExistSpec.php';
        $content = $this->getSpecContent('spec\\psr0\\namespace1','NotExistSpec');
        file_put_contents($file,$content);
        $this->evaluate();
        $this->assertContains(getcwd(),$file);
        $this->assertDisplayContains('failed');

        $this->getTester()->run('all phpspec -vvv');
        $this->assertFileExists($file);
        $this->assertDisplayContains('broken');
    }

    public function testShouldNotRunUnexistentSpecFile()
    {
        $this->markTestIncomplete();
        @unlink(Inspector::getCacheFileName());
        @unlink(Inspector::getErrorFileName());
        $this->getTester()->run('-vvv');

        $file = static::$tmpDir.'/src/psr0/namespace1/TestClass2.php';
        file_put_contents($file,$this->getClassContent('psr0\\namespace1','TestClass2'));
        $this->evaluate();
        $this->assertDisplayContains('file not found');
        $this->assertDisplayContains('src/psr0/namespace1/TestClass2.php');
    }

    public function testShouldImportSuites()
    {
        static::setUpBeforeClass();
        @unlink(Inspector::getCacheFileName());
        @unlink(Inspector::getErrorFileName());
        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('3 passed');
        $this->assertNotDisplayContains('broken');
    }

    /**
     * @dataProvider getTestPsr4
     */
    public function testShouldImportPsr4Suites($targetClass,$class,$specPath,$specPrefix)
    {
        $this->buildPsr4();
        $exp = explode('\\',$class);
        $specClass = array_pop($exp).'Spec';
        $specNs = $specPrefix.'\\'.implode('\\',$exp);
        $target = $specPath.$specPrefix.DIRECTORY_SEPARATOR.$class.'Spec.php';
        $target = str_replace('\\','/',$target);

        // build spec first before run
        $this->createSpecFile($target,$specNs,$specClass);
        $this->getTester()->run('all phpspec -vvv');
        $this->buildClass($targetClass,$class);
        $this->evaluate();

        $this->assertDisplayContains($class);
    }

    public function getTestPsr4()
    {
        return array(
            array('some/TestClass.php','psr4\\namespace1\\TestClass',null,'spec'),
            array('some/namespace2/src/TestClass.php','psr4\\namespace2\\TestClass','some/namespace2/','spec'),
            array('some/namespace3/src/TestClass.php','psr4\\namespace3\\TestClass','some/namespace3/','Spec'),
        );
    }

    protected function buildPsr4()
    {
        static::buildFixtures('psr4');
        static::createApplication();
        $autoload = include_once getcwd().'/vendor/autoload.php';
        if(is_object($autoload)){
            $autoload->register();
        }
        chdir(static::$tmpDir);
    }

    static protected function buildPsr0()
    {
        static::buildFixtures('psr0');
        static::createApplication();
        $autoload = include_once getcwd().'/vendor/autoload.php';
        if(is_object($autoload)){
            $autoload->register();
        }
        chdir(static::$tmpDir);
    }

    /**
     * @dataProvider getTestFilteredTags
     */
    public function testShouldRunWithFilteredTags($tags,$class,$assertNot=false)
    {
        $this->markTestIncomplete();
        $this->getTester()->run('all phpspec --tags='.$tags.' -vvv');

        if($assertNot){
            $this->assertNotDisplayContains($class);
        }else{
            $this->assertDisplayContains($class);
        }
    }

    public function getTestFilteredTags()
    {
        return array(
            array('Tag1','psr0\\namespace1\\TestClass'),
            array('Tag1','psr0\\namespace2\\TestClass',true),
            array('Tag1','psr0\\namespace3\\TestClass',true),
            array('Tag2','psr0\\namespace2\\TestClass'),
            array('Tag3','psr0\\namespace3\\TestClass'),
            array('Tag1,Tag2,Tag3','psr0\\namespace1\\TestClass'),
            array('Tag1,Tag2,Tag3','psr0\\namespace2\\TestClass'),
            array('Tag1,Tag2,Tag3','psr0\\namespace3\\TestClass'),
        );
    }
}
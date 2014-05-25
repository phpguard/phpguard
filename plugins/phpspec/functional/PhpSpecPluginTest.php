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
        $args = '-vvv';
        if(!is_null($tags)){
            $args .= ' --tags='.$tags;
        }
        //static::createApplication();
        $this->getTester()->run($args);

        $file = self::$tmpDir.DIRECTORY_SEPARATOR.$fileName;

        $exp = explode("\\",$className);
        $namespace = $exp[0];
        $class = $exp[1];
        file_put_contents($file,$this->getClassContent($namespace,$class));
        $this->evaluate();
        $display = $this->getDisplay(true);
        if($assertNot){
            $this->assertNotContains($fileName,$display);
            ///$this->assertNotContains($className,$display);
        }else{
            $this->assertDisplayContains($fileName);
            ///$this->assertContains($className,$display);
        }
    }

    public function getTestRun()
    {
        return array(
            array('src/PhpSpecTest1/TestClass.php','PhpSpecTest1\\TestClass'),
            array('src/PhpSpecTest2/TestClass.php','PhpSpecTest2\\TestClass'),
            array('src/PhpSpecTest3/TestClass.php','PhpSpecTest3\\TestClass'),

            array('src/PhpSpecTest1/TestTag1.php','PhpSpecTest1\\TestTag1','Tag1'),
            array('src/PhpSpecTest2/TestTag2.php','PhpSpecTest2\\TestTag2','Tag1',true),
            array('src/PhpSpecTest2/TestTag3.php','PhpSpecTest3\\TestTag3','Tag1',true),
            array('src/PhpSpecTest2/TestTag4.php','PhpSpecTest2\\TestTag4','Tag1,Tag2'),
            array('src/PhpSpecTest3/TestTag5.php','PhpSpecTest3\\TestTag5','Tag1,Tag2',true),
            array('src/PhpSpecTest1/TestTag6.php','PhpSpecTest1\\TestTag6','Tag1,Tag2'),

        );
    }

    /**
     * @dataProvider getTestSpecFile
     */
    public function testShouldRunFromSpecFile($specFile,$className)
    {
        static::createApplication();
        $this->getTester()->run('-vvv');
        $file = self::$tmpDir.DIRECTORY_SEPARATOR.$specFile;
        $exp = explode('\\',$className);
        $class = array_pop($exp).'Spec';
        $namespace = implode('\\',$exp);
        $content = $this->getSpecContent($namespace,$class);

        file_put_contents($file,$content,LOCK_EX);
        self::getShell()->evaluate();

        $display = $this->getDisplay();
        $this->assertContains($specFile,$display);

    }

    public function getTestSpecFile()
    {
        return array(
            array('spec/PhpSpecTest1/TestClassSpec.php','spec\\PhpSpecTest1\\TestClass'),
            array('src/PhpSpecTest2/spec/PhpSpecTest2/TestClassSpec.php','spec\\PhpSpecTest2\\TestClass'),
            array('src/PhpSpecTest3/Spec/PhpSpecTest3/TestClassSpec.php','Spec\\PhpSpecTest3\\TestClass'),
        );
    }

    public function testShouldLogFailedMessage()
    {
        $file = static::$tmpDir.'/spec/PhpSpecTest1/NotExistSpec.php';
        $content = $this->getSpecContent('spec\\PhpSpecTest1','NotExistSpec');
        file_put_contents($file,$content);
        $this->evaluate();
        $display = $this->getDisplay();
        $this->assertContains('failed',$display);

        $this->getTester()->run('all phpspec');
        $this->assertDisplayContains('broken',$display);
    }

    public function testShouldNotRunUnexistentSpecFile()
    {
        @unlink(Inspector::getCacheFileName());
        @unlink(Inspector::getErrorFileName());
        $this->getTester()->run('-vvv');

        $file = static::$tmpDir.'/src/PhpSpecTest1/TestClass2.php';
        file_put_contents($file,$this->getClassContent('PhpSpecTest1','TestClass2'));
        $this->evaluate();
        $this->assertDisplayContains('file not found');
        $this->assertDisplayContains('src/PhpSpecTest1/TestClass2.php');
    }
}
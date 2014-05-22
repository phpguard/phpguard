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
        $this->rebuildApplication();
        self::$tester->run(array('-vvv' => ''));

    }

    /**
     * @param      $fileName
     * @param      $className
     *
     * @param null $tags
     * @param bool $assertNot
     *
     * @dataProvider getTestRun
     */
    public function testShouldRunSpecs($fileName,$className,$tags=null,$assertNot=false)
    {
        self::$app->getContainer()->setParameter('filter.tags',array());
        if(!is_null($tags)){
            self::rebuildApplication();
            self::$tester->run(array('--tags'=>$tags,'-vvv' => ''));
        }else{
            self::$tester->run(array('-vvv'=>''));
        }
        $this->assertFileExists(getcwd().'/vendor/autoload.php');
        $file = self::$tmpDir.DIRECTORY_SEPARATOR.$fileName;
        $exp = explode("\\",$className);
        $namespace = $exp[0];
        $class = $exp[1];
        file_put_contents($file,$this->getClassContent($namespace,$class));
        $this->getShell()->evaluate(true);
        $display = $this->getDisplay(true);
        if($assertNot){
            $this->assertNotContains($fileName,$display);
            $this->assertNotContains($className,$display);
        }else{
            $this->assertContains($fileName,$display);
            $this->assertContains($className,$display);
        }
    }

    public function getTestRun()
    {
        return array(
            array('src/PhpSpecTest1/TestClass.php','PhpSpecTest1\\TestClass'),
            array('src/PhpSpecTest2/TestClass.php','PhpSpecTest2\\TestClass'),
            array('src/PhpSpecTest3/TestClass.php','PhpSpecTest3\\TestClass'),
            array('src/PhpSpecTest1/TestClass.php','PhpSpecTest1\\TestClass','Tag1'),
            array('src/PhpSpecTest2/TestClass.php','PhpSpecTest2\\TestClass','Tag1',true),
            array('src/PhpSpecTest2/TestClass.php','PhpSpecTest3\\TestClass','Tag1',true),
            array('src/PhpSpecTest1/TestClass.php','PhpSpecTest1\\TestClass','Tag1,Tag2'),
            array('src/PhpSpecTest2/TestClass.php','PhpSpecTest2\\TestClass','Tag1,Tag2'),
            array('src/PhpSpecTest3/TestClass.php','PhpSpecTest3\\TestClass','Tag1,Tag2',true),
        );
    }

    /**
     * @param $specFile
     * @param $specClass
     * @dataProvider getTestSpecFile
     */
    public function testShouldRunFromSpecFile($specFile,$className)
    {
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
        $file = self::$tmpDir.'/spec/PhpSpecTest1/NotExistSpec.php';
        $content = $this->getSpecContent('spec\\PhpSpecTest1','NotExistSpec');
        file_put_contents($file,$content);
        self::getShell()->evaluate();
        $display = $this->getDisplay();
        $this->assertContains('failed',$display);

        self::$tester->run(array('-vvv'=>''));
        self::getShell()->runCommand('all phpspec');
        $display = $this->getDisplay();
        $this->assertContains('failed',$display);
    }

    public function testShouldNotRunUnexistentSpecFile()
    {
        $file = self::$tmpDir.'/src/PhpSpecTest1/TestClass2.php';
        file_put_contents($file,$this->getClassContent('PhpSpecTest1','TestClass2'));
        $this->getShell()->evaluate();
        $display = $this->getDisplay();
        $this->assertContains('file not found',$display);
        $this->assertContains('src/PhpSpecTest1/TestClass2.php',$display);
    }
}
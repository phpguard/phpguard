<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Util;


use Composer\Autoload\ClassLoader;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Listen\Util\PathUtil;

class TestLocator extends Locator
{
    public function findClass($file, $checkExistence = true)
    {
        ksort($this->prefixes,SORT_ASC);
        ksort($this->prefixesPsr4,SORT_ASC);
        return parent::findClass($file, $checkExistence);

    }

    public function findClassFile($class, $baseDir = null)
    {
        ksort($this->prefixes,SORT_ASC);
        ksort($this->prefixesPsr4,SORT_ASC);
        return parent::findClassFile($class, $baseDir);
    }

}
class LocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getClassFile
     */
    public function testShouldFindPsr0Class($classFile,$expectedClass,$checkExistence=false)
    {
        $dir = __DIR__.'/fixtures';
        $locator = new TestLocator();
        $locator->add('A',$dir.'/src',true);
        $locator->add('Foo',$dir.'/src',true);

        $testFile = $dir.DIRECTORY_SEPARATOR.$classFile;
        $class = $locator->findClass($testFile,$checkExistence);
        $this->assertNotFalse($class);
        $this->assertEquals($class,$expectedClass);
    }

    /**
     * @dataProvider getTestPsr4
     */
    public function testShouldFindPsr4Class($classFile,$expectedClass,$checkExistence=false)
    {
        $dir = __DIR__.'/fixtures';
        $locator = new TestLocator();
        $locator->addPsr4('A\\Bar\\',$dir.'/src/A/Bar',true);
        $locator->addPsr4('Foo\\Bar\\',$dir.'/src/Foo/Bar',true);
        $locator->addPsr4('Hello\\World\\',$dir.'/hello');
        $locator->addPsr4('Custom\\A\\B\\',$dir.'/custom');

        $testFile = $dir.DIRECTORY_SEPARATOR.$classFile;
        $class = $locator->findClass($testFile,$checkExistence);
        $this->assertNotFalse($class);
        $this->assertEquals($class,$expectedClass);
    }

    public function getTestPsr4()
    {
        return array(
            array('src/A/Bar/Foo.php','A\\Bar\\Foo',true),
            array('src/A/Bar/Bar.php','A\\Bar\\Bar',true),
            array('src/A/Bar/Test.php','A\\Bar\\Test'),
            array('src/A/Bar/Hello.php','A\\Bar\\Hello'),
            array('src/Foo/Bar/A.php','Foo\\Bar\\A'),
            array('hello/Foo.php','Hello\\World\\Foo'),
            array('hello/Bar.php','Hello\\World\\Bar'),
            array('custom/Bar.php','Custom\\A\\B\\Bar'),
            array('custom/TestClass.php','Custom\\A\\B\\TestClass')
        );
    }

    public function getClassFile()
    {
        return array(
            array('src/A/Bar/Foo.php','A\\Bar\\Foo',true),
            array('src/A/Bar/Bar.php','A\\Bar\\Bar',true),
            array('src/A/Bar/Test.php','A\\Bar\\Test'),
            array('src/A/Bar/Hello.php','A\\Bar\\Hello'),
            array('src/Foo/Bar/A.php','Foo\\Bar\\A')
        );
    }
}
 
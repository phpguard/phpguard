<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit\Functional;

use PhpGuard\Application\PhpGuard;
use PhpGuard\Plugins\PHPUnit\Inspector;

/**
 * Class PHPUnitPluginTest
 *
 * @package PhpGuard\Plugins\PHPUnit\Functional
 * @group current
 */
class PHPUnitPluginTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->getTester()->run('all phpunit -vvv');
    }

    /**
     *
     * @dataProvider getTestClass
     */
    public function testShouldCheckPhpUnitResults($display,$className,$content,$file)
    {
        $this->getTester()->run('-vvv');
        $file = $this->createTestClass($className,$file,$content);
        $this->evaluate();
        $this->assertFileExists($file);
        $this->assertDisplayContains($display);
    }

    public function getTestClass()
    {
        return array(
            array('Succeed: Test\\SomeTest1','SomeTest1','$this->assertTrue(true);','tests/SomeTest1.php'),
            array('Failed: Test\\SomeTest2','SomeTest2','$this->assertTrue(false);','tests/SomeTest2.php'),
            array('Failed: Test\\SomeTest3','SomeTest3','$this->assertTrue(false);','tests/SomeTest2.php'),
        );
    }

    /**
     * @dataProvider getTestShouldRunAll
     *
     */
    public function testShouldRunAll($expected)
    {
        static::cleanDir(static::$tmpDir);
        static::mkdir(static::$tmpDir);
        static::buildFixtures();
        static::createApplication();

        $this->getTester()->run('all phpunit -vvv');
        $this->assertDisplayContains($expected);
    }

    public function getTestShouldRunAll()
    {
        return array(
            array('Test\\FooTest::testShouldFailed'),
            array('Test\\FooTest::testShouldBroken'),

        );
    }

    public function testShouldRunAllAfterPass()
    {
        static::cleanDir(static::$tmpDir);
        static::mkdir(static::$tmpDir);
        static::buildFixtures();
        static::createApplication();

        $this->createTestClass('FooTest','tests/FooTest.php','$this->assertTrue(true);');
        $this->createTestClass('SucceedTest','tests/SucceedTest.php','$this->assertTrue(true);');
        $this->createTestClass('FailedTest','tests/FailedTest.php','$this->assertFalse(true);');

        $this->getTester()->run('-vvv');

        $this->getTester()->run('all phpunit -vvv');
        $this->assertDisplayContains('Tests: 3');
        $this->assertDisplayContains('Assertions: 3');
        $this->assertDisplayContains('Failures: 1');

        $this->getTester()->run('all phpunit -vvv');
        $this->assertDisplayContains('Tests: 1');
        $this->assertDisplayContains('Assertions: 1');
        $this->assertDisplayContains('Failures: 1');
        $this->assertDisplayContains('Test\\FailedTest');

        $this->createTestClass('FailedTest','tests/FailedTest.php','$this->assertTrue(true);');
        $this->evaluate();
        $this->assertDisplayContains('1 test, 1 assertion');

        $this->assertDisplayContains('3 tests');

        //$this->getTester()->run('all phpunit -vvv');

    }

    protected function createTestClass($className,$file,$content)
    {
        $time = new \DateTime();
        $time = $time->format('H:i:s');
        $content = <<<EOC
<?php
// time: {$time}
namespace Test;

class {$className} extends \PHPUnit_Framework_TestCase
{
    public function testContent()
    {
        {$content}
    }
}

?>
EOC;
        $fileName = static::$tmpDir.DIRECTORY_SEPARATOR.$file;
        file_put_contents($fileName,$content);
        return $fileName;
    }
}
 
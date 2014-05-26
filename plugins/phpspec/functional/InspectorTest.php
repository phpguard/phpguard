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

use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Inspector;

/**
 * Class InspectorTest
 *
 * @package PhpGuard\Plugins\PhpSpec\Functional
 */
class InspectorTest extends TestCase
{
    /**
     * @var Inspector
     */
    protected $inspector;

    protected function setUp()
    {
        parent::setUp();
        $container = static::$container;
        $phpspec = $container->get('plugins.phpspec');
        $logger = new Logger('Inspector');
        $logger->pushHandler($container->get('logger.handler'));

        $inspector = new Inspector();
        $inspector->setContainer($container);
        $inspector->setLogger($logger);
        $inspector->setOptions($phpspec->getOptions());

        $this->inspector = $inspector;
        $this->getTester()->run('-vvv');
    }

    public function testShouldRunWithClassName()
    {
        $inspector = $this->inspector;
        $inspector->runAll();
        // test
        $this->assertDisplayContains('3 passed');
    }

    public function testShouldKeepRunningFailedSpec()
    {
        $this->markTestIncomplete();
        $inspector = $this->inspector;
        $this->createSpecFile('src/psr0/namespace1/spec/psr0/namespace1/FooSpec.php','spec\\psr0\\namespace1','FooSpec');
        $this->createSpecFile('src/psr0/namespace1/spec/psr0/namespace1/BarSpec.php','spec\\psr0\\namespace1','BarSpec');

        //$this->getTester()->resetDisplay();

        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertDisplayContains('2 broken');

        // clear display
        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertNotDisplayContains('TestClass');
        $this->assertDisplayContains('Foo');
        $this->assertDisplayContains('Bar');

        unlink(getcwd().'/src/psr0/namespace1/spec/psr0/namespace1/BarSpec.php');
        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertNotDisplayContains('TestClass');
        $this->assertDisplayContains('Foo');
        $this->assertNotDisplayContains('Bar');
        $this->assertDisplayContains('2 broken');

        $plugin = $this->getApplication()->getContainer()->get('plugins.phpspec');
        $options = $plugin->getOptions();

        $options['keep_failed'] = false;
        $plugin->setOptions($options);
        $this->getTester()->run('-vvv');
        $inspector->setOptions($options);
        $inspector->runAll();
        $this->assertNotDisplayContains('Bar');
        $this->assertDisplayContains('Foo');
        $this->assertDisplayContains('3 passed');
        $this->assertDisplayContains('1 broken');
    }

    public function testShouldLogBrokenSpecFiles()
    {
        $this->markTestIncomplete();
        $content = <<<EOF
<?php

namespace spec\psr0\namespace1;

use PhpSpec\ObjectBehavior;

class BrokenFileSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        \$this->shouldHaveType('spec\psr0\namespace1\BrokenFile);
        aaaaaaaaa
    }
}
EOF;
        $this->buildFixtures('psr0');
        $this->clearCache();
        static::createApplication();
        $file = $this->createSpecFile('src/psr0/namespace1/spec/psr0/namespace1/BrokenSpec.php','spec\\psr0\\namespace1','BrokenSpec');

        $this->getTester()->run('all phpspec -vvv');
        $this->assertDisplayContains('3 passed');
        $this->assertDisplayContains('psr0\\namespace1\\Broken');

        file_put_contents($file,$content);
        $this->getTester()->run('all phpspec -vvv');
        //$this->inspector->runAll();
        $this->assertFileExists(Inspector::getErrorFileName());
        $this->assertDisplayContains('Fatal Error');
    }
}

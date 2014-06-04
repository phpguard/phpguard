<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Behat;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;
use PhpGuard\Application\Test\ApplicationTester;
use PhpGuard\Application\Test\TestApplication;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Plugins\PhpSpec\Bridge\Console\Application;

/**
 * Class PhpGuardContext
 * @codeCoverageIgnore
 */
class PhpGuardContext extends BehatContext
{
    /**
     * @var string|null
     */
    protected $workDir;

    /**
     * @var ApplicationTester|null
     */
    protected $applicationTester = null;

    /**
     * @var Application|null
     */
    protected $application = null;

    static protected $cwd;

    /**
     * @BeforeScenario
     */
    public function createWorkDir()
    {
        if(!static::$cwd){
            static::$cwd = getcwd();
        }
        $this->workDir = sprintf(
            '%s/phpguard-test/%s',
            sys_get_temp_dir(),
            uniqid('behat_')
        );

        $fs = new Filesystem();
        $fs->mkdir($this->workDir,0777);
        if(is_file('stdout')){
            unlink('stdout');
        }
        chdir($this->workDir);
    }

    /**
     * @beforeSuite
     */
    static public function cleanTestDir()
    {
        Filesystem::create()
            ->cleanDir('/tmp/phpguard-test')
        ;
    }

    /**
     * @AfterScenario
     */
    public function removeWorkDir()
    {
        chdir(static::$cwd);
    }

    /**
     * @When /^(?:|I )start phpguard$/
     */
    public function iStartPhpGuard()
    {
        $this->applicationTester = $this->createApplicationTester();
        $this->applicationTester->run('start -vvv',array('decorated'=>false));
    }

    /**
     * @When /^(?:|I )start phpguard with "([^"]*)"$/
     */
    public function iStartPhpGuardWith($arguments)
    {
        $this->applicationTester = $this->createApplicationTester();
        $this->applicationTester->run('start '.$arguments,array('decorated'=>false));
    }

    /**
     * @When /^I run phpguard with "([^"]*)"$/
     */
    public function iRunPhpGuardWith($arguments)
    {
        $this->applicationTester->run($arguments,array('decorated'=>false));
    }

    /**
     * @When /^I (?:create |modify )file "(?P<file>[^"]+)" with:$/
     */
    public function iDoSomethingWithFile($file,PyStringNode $string)
    {
        $this->theFileContains($file,$string);
        $this->evaluate();
    }

    /**
     * @Given /^(?:|the )(?:spec |class |feature )file "(?P<file>[^"]+)" contains:$/
     */
    public function theFileContains($file,PyStringNode $string)
    {
        $fs = Filesystem::create();
        $dirname = dirname($file);
        if(!file_exists($dirname)){
            $fs->mkdir($dirname);
        }
        $fs->putFileContents($file,$string->getRaw());
        clearstatcache($file);
    }

    /**
     * @Then /^(?:|the )file "(?P<file>[^"]+)" should contains "(?P<content>[^"]*)"$/
     */
    public function theFileShouldContains($file,$content)
    {
        expect(file_exists($file))->toBe(true);
        expect(file_get_contents($file))->toMatch('/'.$content.'/sm');
    }

    /**
     * @Given /^the config file contains:$/
     */
    public function theConfigFileContains(PyStringNode $string)
    {
        file_put_contents('phpguard.yml', $string->getRaw());
    }

    /**
     * @Given /^the file "(?P<file>[^"]+)" contains:$/
     */
    public function theSomeFileContains($file,PyStringNode $string)
    {
        file_put_contents($file, $string->getRaw());
    }

    /**
     * @Then /^(?:|I )should see "(?P<message>[^"]*)"$/
     */
    public function iShouldSee($message)
    {
        if(file_exists('stdout')){
            $display = file_get_contents('stdout');
        }else{
            $display= $this->applicationTester->getDisplay();
        }
        expect($display)->toMatch('/'.$message.'/sm');
    }

    /**
     * @Then /^(?:|I )should see file "(?P<file>[^"]+)"$/
     */
    public function iShouldSeeFile($file)
    {
        expect(file_exists($file))->toBe(true);
    }

    /**
     * @return TestApplication|null
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Evaluate filesystem changes
     */
    protected function evaluate()
    {
        $this->getApplication()->getContainer()
            ->get('phpguard')
            ->evaluate()
        ;
    }

    /**
     * @return ApplicationTester
     */
    protected function createApplicationTester()
    {
        $this->application = $application = new TestApplication();

        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }

    public function setApplicationTester(ApplicationTester $tester)
    {
        $this->applicationTester = $tester;
    }
}
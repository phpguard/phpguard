<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit\Bridge\TextUI;

use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Container;
use PhpGuard\Plugins\PHPUnit\Inspector;
use PhpGuard\Plugins\PHPUnit\Bridge\TestListener;
use PhpGuard\Application\Util\Filesystem;

use PHP_CodeCoverage_Filter;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Runner_TestSuiteLoader;
use PHPUnit_TextUI_TestRunner;

/**
 * Class TestRunner
 *
 */
class TestRunner extends PHPUnit_TextUI_TestRunner
{
    /**
     * @var TestListener
     */
    private $testListener;

    private $testFiles = array();

    /**
     * @var CodeCoverageRunner
     */
    private $coverageRunner;

    public function __construct(PHPUnit_Runner_TestSuiteLoader $loader = null, PHP_CodeCoverage_Filter $filter = null)
    {
        $this->coverageRunner = $coverageRunner = CodeCoverageRunner::getCached();
        $filter = $coverageRunner->getFilter();

        parent::__construct($loader, $filter);
        if(is_file($file=Inspector::getResultFileName())){
            unlink($file);
        }
        $this->testListener = new TestListener();
        $this->testListener->setCoverage($coverageRunner);
    }

    public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array())
    {

        $arguments['listeners'][] = $this->testListener;
        $result = parent::doRun($suite,$arguments);
        $results = $this->testListener->getResults();
        Filesystem::serialize(Inspector::getResultFileName(),$results);
        $this->coverageRunner->saveState();
        return $result;
    }

    public function getTest($suiteClassName, $suiteClassFile = '', $suffixes = '')
    {
        // check if suite has comma separate forms
        if(false===strpos($suiteClassName,',')){
            return parent::getTest($suiteClassName, $suiteClassFile, $suffixes);
        }
        $files = explode(',',$suiteClassName);
        $files = array_unique($files);
        $suite = new PHPUnit_Framework_TestSuite('PhpGuard Unit Tests');
        $suite->addTestFiles($files);
        return $suite;
    }


}
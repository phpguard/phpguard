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
use PHPUnit_Framework_Test;
use PHPUnit_Framework_Exception;
use PHPUnit_TextUI_TestRunner;

/**
 * Class Command
 *
 */
class Command extends \PHPUnit_TextUI_Command
{
    protected function createRunner()
    {
        return new TestRunner($this->arguments['loader']);
    }

    public function run(array $argv, $exit = true)
    {
        $this->longOptions['phpguard-test-files'] = null;
        return parent::run($argv, $exit);
    }


}
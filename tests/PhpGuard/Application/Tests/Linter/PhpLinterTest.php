<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Linter;


use PhpGuard\Application\Linter\PhpLinter;
use PhpGuard\Application\Tests\TestCase;

class PhpLinterTest extends TestCase
{
    public function testShouldCheckPhpSyntax()
    {
        $linter = new PhpLinter();
        $file = self::$tmpDir.'/src/unchecked.php';
        file_put_contents($file,'<?php errror');
        self::getShell()->evaluate();

        $display = $this->getDisplay();
        $this->assertContains($linter->getTitle(),$display);
    }
}
 
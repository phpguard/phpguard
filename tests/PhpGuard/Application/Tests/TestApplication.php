<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;
use PhpGuard\Application\Console\Application;

class TestApplication extends Application
{
    public function __construct()
    {
        parent::__construct();

        $this->getContainer()
            ->set('phpguard',new TestPhpGuard())
        ;
        $this->setCatchExceptions(true);
        $this->setAutoExit(false);
    }
} 
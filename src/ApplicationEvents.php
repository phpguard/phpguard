<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application;

final class ApplicationEvents
{
    const initialize       = 'phpguard.initialize';
    const started          = 'phpguard.started';
    const terminated       = 'phpguard.terminated';

    const preEvaluate       = 'listen.preEvaluate';
    const evaluate          = 'listen.evaluate';
    const postEvaluate      = 'listen.postEvaluate';

    const preRunCommand     = 'plugin.preRunCommand';
    const postRunCommand    = 'plugin.postRunCommand';

    const preRunAll         = 'plugin.preRunAll';
    const runAll    = 'plugin.runAll';
    const postRunAll        = 'plugin.postRunAll';
}
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


final class PhpGuardEvents
{
    const preLoadConfig     = 'config.preLoad';
    const postLoadConfig    = 'config.postLoad';

    const postEvaluate      = 'listen.postEvaluate';

    const preRunCommand     = 'plugin.preRunCommand';
    const postRunCommand    = 'plugin.postRunCommand';

    const runAllCommands    = 'plugin.runAllCommand';
}
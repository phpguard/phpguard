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
    const CONFIG_PRE_LOAD   = 'preLoadConfiguration';
    const CONFIG_POST_LOAD  = 'postLoadConfiguration';

    const POST_EVALUATE = 'postEvaluateFilesystem';

    const PRE_RUN_COMMANDS = 'preRunCommands';
    const POST_RUN_COMMANDS = 'postRunCommands';
}
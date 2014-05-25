<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Configuration;


final class ConfigEvents
{
    /**
     * Before configuration loaded
     */
    const PRELOAD       = 'preLoad';

    /**
     * After configuration loaded
     */
    const POSTLOAD      = 'postLoad';

    /**
     * Load configuration file
     */
    const LOAD          = 'load';

    /**
     * Processor reload
     */
    const RELOAD         = 'reload';
}
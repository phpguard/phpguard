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
    const PRELOAD       = 'config.pre_load';

    /**
     * After configuration loaded
     */
    const POSTLOAD      = 'config.post_load';

    /**
     * Load configuration file
     */
    const LOAD          = 'config.load';

    /**
     * Processor reload
     */
    const RELOAD         = 'config.reload';
}

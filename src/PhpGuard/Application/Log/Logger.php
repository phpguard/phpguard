<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Log;

use Monolog\Logger as BaseLogger;

/**
 * Class Logger
 *
 */
class Logger extends BaseLogger
{
    const COMMON    = 301;

    const SUCCESS   = 302;

    /**
     * Command fail
     *
     * Examples: failed to run PHPUnit test
     */
    const FAIL      = 303;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        301 => 'COMMON',
        302 => 'SUCCESS',
        303 => 'FAIL',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    public function addCommon($message,array $context=array())
    {
        return $this->addRecord(static::COMMON,$message,$context);
    }

    public function addSuccess($message,array $context=array())
    {
        return $this->addRecord(static::SUCCESS,$message,$context);
    }

    public function addFail($message,array $context=array())
    {
        return $this->addRecord(static::FAIL,$message,$context);
    }
}
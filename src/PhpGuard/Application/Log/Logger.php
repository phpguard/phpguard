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
    /**
     * Command fail
     *
     * Examples: failed to run PHPUnit test
     */
    const FAIL = 201;

    const SUCCESS = 202;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        201 => 'FAIL',
        202 => 'SUCCESS',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    public function addFail($message,array $context=array())
    {
        return $this->addRecord(self::FAIL,$message,$context);
    }

    public function addSuccess($message,array $context=array())
    {
        return $this->addRecord(static::SUCCESS,$message,$context);
    }
}
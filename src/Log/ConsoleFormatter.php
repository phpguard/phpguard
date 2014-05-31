<?php

/*
 * This file is part of the PhpGuard package.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Log;

use Monolog\Formatter\LineFormatter;

/**
 * Formats incoming records for console output by coloring them depending on log level.
 *
 * @author Tobias Schultze <http://tobion.de>
 * @author Anthonius Munthi <me@itstoni.com>
 */
class ConsoleFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n";
    const SIMPLE_DATE = "H:i:s";

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $record['start_tag'] = '';
        $record['end_tag']   = '';

        if ($record['level'] >= Logger::ERROR) {
            $record['start_tag'] = '<error>';
            $record['end_tag']   = '</error>';
        } elseif ($record['level'] == Logger::FAIL) {
            $record['start_tag'] = '<fail>';
            $record['end_tag'] = '</fail>';
        } elseif ($record['level'] >= Logger::WARNING) {
            $record['start_tag'] = '<info>';
            $record['end_tag']   = '</info>';
        } elseif ($record['level'] >= Logger::NOTICE) {
            $record['start_tag'] = '<comment>';
            $record['end_tag']   = '</comment>';
        } elseif ($record['level'] >= Logger::INFO) {
            $record['start_tag'] = '<info>';
            $record['end_tag']   = '</info>';
        } elseif ($record['level'] >= Logger::DEBUG) {
            $record['start_tag'] = '<comment>';
            $record['end_tag']   = '</comment>';
        }

        return parent::format($record);
    }
}

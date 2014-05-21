<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Linter;


use PhpGuard\Listen\Exception\RuntimeException;

class LinterException extends RuntimeException
{
    /**
     * @var LinterInterface $linter
     */
    private $linter;

    public function __construct(LinterInterface $linter,$output)
    {
        $this->linter = $linter;
        parent::__construct($output);
    }

    public function getFormattedOutput()
    {
        $format = '<log-error>%s failed: <comment>%s</comment></log-error>';
        $output = sprintf($format,$this->linter->getTitle(),$this->message);
        return $output;
    }
} 
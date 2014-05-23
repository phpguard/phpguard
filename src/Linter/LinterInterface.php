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


interface LinterInterface
{
    /**
     * Validate file syntax
     *
     * @param   string $file
     * @throws  \PhpGuard\Application\Linter\LinterException
     *
     * @return  bool
     */
    public function check($file);

    /**
     * Name for this linter
     * @return string
     */
    public function getName();

    /**
     * Title for this linter
     * @return string
     */
    public function getTitle();
}
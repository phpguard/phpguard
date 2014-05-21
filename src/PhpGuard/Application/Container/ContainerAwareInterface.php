<?php

/*
 * This file is part of the phpguard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Container;


interface ContainerAwareInterface
{
    /**
     * Set container for command
     *
     * @param   ContainerInterface $container
     * @return  mixed
     */
    public function setContainer(ContainerInterface $container);
} 
<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Interfaces;


interface ContainerInterface
{
    public function setParameter($name,$value);

    public function getParameter($name,$default = null);

    public function hasParameter($name);

    public function set($id,$service);

    public function setShared($id,$callable);

    public function get($id);

    public function has($id);

    public function getByPrefix($prefix);
}
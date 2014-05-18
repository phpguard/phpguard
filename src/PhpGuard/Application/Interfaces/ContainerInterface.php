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
    /**
     * @param   string  $name
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function setParameter($name,$value);

    /**
     * @param       string  $name
     * @param       mixed   $default
     *
     * @return      mixed
     */
    public function getParameter($name,$default = null);

    /**
     * @param   string  $name
     *
     * @return  bool True if parameter exists
     */
    public function hasParameter($name);

    /**
     * @param   string  $id
     * @param   mixed  $service
     *
     * @return  $this
     */
    public function set($id,$service);

    /**
     * @param   string      $id
     * @param   callable    $callable
     *
     * @return  $this
     */
    public function setShared($id,$callable);

    /**
     * @param   string  $id
     *
     * @return  mixed
     */
    public function get($id);

    /**
     * @param string $id
     *
     * @return bool True if service registered
     */
    public function has($id);

    /**
     * @param   string  $prefix
     *
     * @return  array
     */
    public function getByPrefix($prefix);
}
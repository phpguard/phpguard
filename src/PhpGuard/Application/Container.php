<?php

namespace PhpGuard\Application;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PhpGuard\Application\Container\ContainerInterface;

/**
 * Class Container
 * Some methods Proudly Stolen from PhpSpec, and Symfony
 *
 */
class Container implements ContainerInterface
{
    protected $parameters = array();

    protected $services = array();

    private $prefixed = array();

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function setParameter($name,$value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null   $default
     *
     * @return mixed|null
     */
    public function getParameter($name,$default=null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name]:$default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return array_key_exists($name,$this->parameters);
    }

    /**
     * @param string $id
     * @param mixed  $service
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function set($id, $service)
    {
        if(!is_object($service)){
            throw new \InvalidArgumentException(sprintf(
                'Service should be callback or object, but "%s" type given.',
                gettype($service)
            ));
        }
        list($prefix, $sid) = $this->getPrefixAndSid($id);
        if ($prefix) {
            if (!isset($this->prefixed[$prefix])) {
                $this->prefixed[$prefix] = array();
            }

            $this->prefixed[$prefix][$sid] = $id;
        }

        $this->services[$id] = $service;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function get($id)
    {
        if(!array_key_exists($id,$this->services)){
            throw new \InvalidArgumentException(sprintf(
                'Service with id "%s" is not registered.',
                $id
            ));
        }

        $value = $this->services[$id];
        if (method_exists($value, '__invoke')) {
            $value = $value($this);
        }
        
        if(method_exists($value,'setContainer')){
            $value->setContainer($this);
        }

        if(method_exists($value,'setLogger') && $this->has('logger')){
            $value->setLogger($this->get('logger'));
        }
        return $value;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id,$this->services);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getPrefixAndSid($id)
    {
        if (count($parts = explode('.', $id)) < 2) {
            return array(null, $id);
        }

        $sid    = array_pop($parts);
        $prefix = implode('.', $parts);

        return array($prefix, $sid);
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function getByPrefix($prefix)
    {
        if (!array_key_exists($prefix, $this->prefixed)) {
            return array();
        }

        $services = array();
        foreach ($this->prefixed[$prefix] as $id) {
            $services[] = $this->get($id);
        }

        return $services;
    }

    /**
     * @param string   $id
     * @param callable $callable
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setShared($id, $callable)
    {
        if (!is_object($callable)) {
            throw new \InvalidArgumentException(sprintf(
                'Service should be callback, "%s" given.', gettype($callable)
            ));
        }

        $this->set($id, function ($container) use ($callable) {
            static $instance;

            if (null === $instance) {
                $instance = $callable($container);
            }

            return $instance;
        });
        return $this;
    }
}

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
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

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

    public function setParameter($name,$value)
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter($name,$default=null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name]:$default;
    }

    public function hasParameter($name)
    {
        return array_key_exists($name,$this->parameters);
    }

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
    }

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

        if(method_exists($value,'setLogger') && $this->has('phpguard.logger')){
            $value->setLogger($this->get('phpguard.logger'));
        }
        return $value;
    }

    public function has($id)
    {
        return array_key_exists($id,$this->services);
    }

    /**
     * Retrieves the prefix and sid of a given service
     *
     * @param   string $id
     * @return  array
     * @credits PhpSpec\ServiceContainer
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
     * Retrieves a list of services of a given prefix
     *
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
     * Sets a object or a callback for the object creation. The same object will
     * be returned every time
     *
     * @param   string   $id
     * @param   callable $callable
     *
     * @throws \InvalidArgumentException if service is not an object or callback
     * @credits PhpSpec\ServiceContainer
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
    }

}

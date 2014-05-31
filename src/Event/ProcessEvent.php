<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Event;
use PhpGuard\Application\Plugin\PluginInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProcessEvent
 *
 */
class ProcessEvent extends Event
{
    /**
     * @var array
     */
    private $results;

    /**
     * @var PluginInterface
     */
    private $plugin;

    public function __construct(PluginInterface $plugin,array $results = array())
    {
        $this->plugin = $plugin;
        $this->results = $results;
    }

    /**
     * @return \PhpGuard\Application\Plugin\PluginInterface
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }
}

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

/**
 * Class CommandEvent
 *
 */
class CommandEvent
{
    /**
     * Command success
     */
    const SUCCEED   = 1;

    /**
     * Command fail
     */
    const FAILED    = 2;

    /**
     * Command throws an exception
     */
    const BROKEN    = 4;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var int
     */
    private $result;

    /**
     * @var \PhpGuard\Application\Plugin\PluginInterface
     */
    private $plugin;

    /**
     * @var string
     */
    private $message;

    public function __construct(PluginInterface $plugin,$result,$message=null,\Exception $exception=null)
    {
        $this->result       = $result;
        $this->plugin       = $plugin;
        $this->exception    = $exception;

        $this->createMessage($message);
    }

    public function isSucceed()
    {
        return static::SUCCEED === $this->result;
    }

    public function isFailed()
    {
        return static::FAILED === $this->result;
    }

    public function isBroken()
    {
        return static::BROKEN === $this->result;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return \PhpGuard\Application\Plugin\PluginInterface
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return int
     */
    public function getResult()
    {
        return $this->result;
    }

    private function createMessage($message)
    {
        $format = '[%s] %s';
        $message = sprintf($format,$this->plugin->getTitle(),$message);
        $this->message      = $message;

    }
}
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

/**
 * Class ResultEvent
 *
 */
class ResultEvent
{
    /**
     * Command success
     */
    const SUCCEED   = 0;

    /**
     * Command fail
     */
    const FAILED    = 100;

    /**
     * Command is broken
     */
    const BROKEN    = 200;

    /**
     * Command throws an error
     */
    const ERROR     = 300;

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

    private $arguments = array();

    private $trace = array();

    static $maps = array(
        0       => 'SUCCEED',
        100     => 'FAILED',
        200     => 'BROKEN',
        300     => 'ERROR',
    );

    public function __construct(
        $result,
        $message=null,
        array $arguments = array(),
        \Exception $exception=null,
        array $trace = array()
    )
    {
        $this->result       = $result;
        $this->exception    = $exception;
        $this->createMessage($message);
        $this->arguments = $arguments;

        if(!empty($trace)){
            // always use passed argument as trace
            $this->trace = $trace;
        }
        elseif ($exception) {
            // use exception trace
            $this->createTrace($exception->getTrace());
        }

    }

    static public function createSucceed($message, array $arguments = array())
    {
        return new self(static::SUCCEED,$message,$arguments);
    }

    static public function createFailed($message,array $arguments = array(),\Exception $e = null)
    {
        return new self(static::FAILED,$message,$arguments,$e);
    }

    static public function createBroken($message,array $arguments = array(),\Exception $e = null)
    {
        return new self(static::BROKEN,$message,$arguments,$e);
    }

    static public function createError($message,array $arguments = array(),\Exception $e = null,array $trace=array())
    {
        return new self(static::ERROR,$message,$arguments,$e,$trace);
    }

    /**
     * @param   int        $result
     * @param   string     $message
     * @param   array      $arguments
     * @param   \Exception $exception
     *
     * @return  ResultEvent
     */
    static public function create($result,$message,array $arguments=array(),\Exception $exception=null)
    {
        $map = array(
            static::SUCCEED => 'createSucceed',
            static::FAILED => 'createFailed',
            static::BROKEN => 'createBroken',
            static::ERROR => 'createError',
        );

        return call_user_func(
            array(__CLASS__,$map[$result]),
            $message,
            $arguments,
            $exception
        );
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

    /**
     * @param   string   $name
     * @param   mixed    $default
     *
     * @return  mixed
     */
    public function getArgument($name,$default=null)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name]:$default;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getTrace()
    {
        return $this->trace;
    }

    private function createMessage($message)
    {
        $format         = '[%s] %s';
        $message        = sprintf($format,'Results',$message);
        $this->message  = $message;
    }

    private function createTrace($trace)
    {
        for ($i = 0, $count = count($trace); $i < $count; $i++) {
            $file = isset($trace[$i]['file']) ? $trace[$i]['file']:'n/a';
            if($file==__FILE__){
                continue;
            }
            $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
            $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
            $function = $trace[$i]['function'];
            $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

            if($file!='n/a'){
                $file = $trace[$i]['file'];
                $file = ltrim(str_replace(getcwd(),'',$file),'\\/');
            }
            $this->trace[]= sprintf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line);
        }
    }
}
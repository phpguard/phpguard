<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Console;

use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\ConsoleFormatter;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Console\Application as BaseApplication;
use PhpSpec\Console\Command;
use PhpSpec\Extension;
use PhpSpec\Formatter;
use PhpSpec\Loader;
use PhpSpec\ServiceContainer;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var Inspector
     */
    protected $inspector;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    public function __construct()
    {
        parent::__construct('PhpGuard-Spec');
        $this->configureErrorHandler();
    }


    protected function loadConfigurationFile(ServiceContainer $container)
    {
        BaseApplication::loadConfigurationFile($container);
        $inspector = $this->inspector;
        $container->setShared('event_dispatcher.listeners.phpguard',function($c) use($inspector){
            $ext = new PhpGuardExtension();
            $ext->load($c);
            return $ext;
        });
    }

    public function setInspector(Inspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Override setup commands
     *
     * Add Bridge\RunCommand
     * @param ServiceContainer $container
     */
    protected function setupCommands(ServiceContainer $container)
    {
        BaseApplication::setupCommands($container);
        $container->setShared('console.commands.run',function($c){
            return new RunCommand();
        });
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function renderException($e, $output)
    {
        //$this->errorHandler->handleException($e);
        parent::renderException($e, $output);
    }

    private function configureErrorHandler()
    {
        @unlink($file=sys_get_temp_dir().'/phpspec_error.log');
        ini_set('display_errors', 0);
        ini_set('error_log',$file);
        register_shutdown_function(array($this,'handleShutdown'));
    }

    public function handleShutdown()
    {
        $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        $lastError = error_get_last();

        if($lastError && in_array($lastError['type'],$fatalErrors)){
            $message = 'Fatal Error '.$lastError['message'];
            $error = $lastError;
            $trace = file(sys_get_temp_dir().'/phpspec_error.log');

            $traces = array();
            for( $i=0,$count=count($trace);$i < $count; $i++ ){
                $text = trim($trace[$i]);
                if(false!==($pos=strpos($text,'PHP '))){
                    $text = substr($text,$pos+4);
                }
                $traces[] = $text;
            }
            $event = ResultEvent::createError(
                $message,
                $error,
                null,
                $traces
            );
            Filesystem::serialize(Inspector::getCacheFileName(),array($event));
        }
    }
}
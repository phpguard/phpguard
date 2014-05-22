<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge;

use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Console\IO;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Event\SuiteEvent;
use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\ServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PhpGuardExtension
 *
 */
class PhpGuardExtension implements ExtensionInterface,EventSubscriberInterface
{
    /**
     * @var IO
     */
    protected $io;

    protected $titles = array();

    protected $failed = array();

    protected $success = array();

    /**
     * @var Inspector
     */
    protected $inspector;

    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
        /* @var EventDispatcherInterface $dispatcher */
        $this->io = $container->get('console.io');
    }

    public function afterSuite(SuiteEvent $event)
    {
        $contents = array(
            'failed' => $this->failed,
            'success' => $this->success,
        );
        $contents = serialize($contents);
        file_put_contents(Inspector::getCacheFileName(),$contents,LOCK_EX);
    }

    public function afterExample(ExampleEvent $event)
    {
    }

    public function afterSpecification(SpecificationEvent $event)
    {
        $reflection = $event->getSpecification()->getClassReflection();
        $title = $event->getTitle();

        $spl = PathUtil::createSplFileInfo(getcwd(),$reflection->getFileName());
        $file = $spl->getRelativePathname();
        if($event->getResult()===ExampleEvent::PASSED){
            $this->success[$title] = $file;
        }else{
            $this->failed[$title] = $file;
        }
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            'afterExample'  => array('afterExample', -10),
            'afterSuite'    => array('afterSuite', -10),
            'afterSpecification' => array('afterSpecification',-10),
        );
    }


}
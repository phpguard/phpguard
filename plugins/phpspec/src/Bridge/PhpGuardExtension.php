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

use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Console\IO;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Event\SuiteEvent;
use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\Loader\Node\SpecificationNode;
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
     * @var ResultEvent[]
     */
    private $results = array();

    private $map = array();

    /**
     * @var CodeCoverageRunner
     */
    private $coverage;

    function __construct()
    {
        $file = Inspector::getCacheFileName();
        if(file_exists($file)){
            unlink($file);
        }

        $this->map = array(
            ExampleEvent::FAILED => ResultEvent::FAILED,
            ExampleEvent::BROKEN => ResultEvent::BROKEN,
            ExampleEvent::PASSED => ResultEvent::SUCCEED,
            ExampleEvent::PENDING => ResultEvent::FAILED,
            ExampleEvent::SKIPPED => ResultEvent::BROKEN,
        );

        $this->coverage = CodeCoverageRunner::getCached();
    }

    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
        /* @var EventDispatcherInterface $dispatcher */
        //$this->io = $container->get('console.io');
    }

    public function afterSuite(SuiteEvent $event)
    {
        Filesystem::serialize(Inspector::getCacheFileName(),$this->results);
        if($this->coverage){
            $this->coverage->saveState();
        }
    }

    public function beforeExample(ExampleEvent $event)
    {
        $example = $event->getExample();

        $name = strtr('%spec%::%example%', array(
            '%spec%' => $example->getSpecification()->getClassReflection()->getName(),
            '%example%' => $example->getFunctionReflection()->getName(),
        ));

        if($this->coverage){
            $this->coverage->start($name);
        }
    }

    public function afterExample(ExampleEvent $event)
    {
        $type = $this->map[$event->getResult()];
        $this->addResult($type,$event->getSpecification(),$event->getTitle());
        if($this->coverage){
            $this->coverage->stop();
        }
    }

    public function getResults()
    {
        return $this->results;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'beforeExample' => array('beforeExample',-10),
            'afterExample'  => array('afterExample', -10),
            'afterSuite'    => array('afterSuite', -10),
            //'afterSpecification' => array('afterSpecification',-10),
        );
    }

    private function addResult($result,SpecificationNode $spec,$title=null)
    {
        $map = array(
            ResultEvent::SUCCEED => 'Succeed: %title%',
            ResultEvent::FAILED => 'Failed: %title%',
            ResultEvent::BROKEN => 'Broken: %title%',
            ResultEvent::ERROR => 'Error: %title%',
        );
        $r = $spec->getClassReflection();
        $arguments = array(
            'file' => $r->getFileName(),
        );
        $key = md5($r->getFileName().$title);

        $format = $map[$result];
        $title = $title == null ? $spec->getTitle():$spec->getTitle().'::'.$title;
        $message = strtr($format,array(
            '%title%' => '<highlight>'.$title.'</highlight>',
        ));
        $this->results[$key] = ResultEvent::create($result,$message,$arguments);
    }
}
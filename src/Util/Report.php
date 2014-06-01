<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Util;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Report
 *
 */
class Report extends ContainerAware implements EventSubscriberInterface
{
    private $logger;

    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::started => array('summary',-500),
            ApplicationEvents::runAll => array('summary',-500),
            ApplicationEvents::postEvaluate => array('summary',-500),
        );
    }

    public function setContainer(ContainerInterface $container)
    {
        parent::setContainer($container);

        $logger = new Logger('Summary');
        $logger->pushHandler($container->get('logger.handler'));
        $this->logger = $logger;
    }

    public function summary(GenericEvent $event)
    {
        $this->container->get('ui.output')->writeln('');
        $this->container->get('ui.output')->writeln('');
        $this->container->get('logger')->addDebug('Print Summary');
        foreach($event->getProcessEvents() as $processEvent){
            $this->renderResult($processEvent);
        }
    }

    private function renderResult(ProcessEvent $event)
    {
        $logger = $this->logger;
        foreach($event->getResults() as $result){
            $status = $result->getResult();
            $format = '%s: %s';
            $message =  sprintf($format,$event->getPlugin()->getTitle(),$result->getMessage());
            switch ($status) {
                case ResultEvent::SUCCEED:
                    $logger->addSuccess($message);
                    break;
                case ResultEvent::FAILED:
                    $logger->addFail($message);
                    break;
                case ResultEvent::BROKEN:
                    $logger->addFail($message);
                    break;
                case ResultEvent::ERROR:
                    $logger->addFail($message);
                    $this->printTrace($result->getTrace());
                    break;
            }
        }
    }

    private function printTrace($trace)
    {
        $writer = $this->container->get('ui.output');
        for ($i = 0, $count = count($trace); $i < $count; $i++) {
            $output = $trace[$i];
            $output = ltrim(str_replace(getcwd(),'',$output),'\\/');
            $writer->writeln($output);
        }
    }
}
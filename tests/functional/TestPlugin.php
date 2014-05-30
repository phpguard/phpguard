<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional;


use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Plugin\Plugin;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TestPlugin extends Plugin
{
    const THROW_MESSAGE = 'Test Plugin Throws Exception';
    const RUN_ALL_MESSAGE = 'Test Plugin Run All';
    const RUN_MESSAGE = 'Test Plugin Run';
    public $runCount = 0;

    public $throwException = false;

    public function configure()
    {
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'test';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Test';
    }


    /**
     * @return ResultEvent
     */
    public function runAll()
    {
        if($this->throwException){
            throw new \RuntimeException(self::THROW_MESSAGE);
        }
        $event = new ResultEvent(ResultEvent::SUCCEED,self::RUN_ALL_MESSAGE);
        return new ProcessEvent($this,array($event));
    }

    /**
     * @param array $paths
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run(array $paths = array())
    {
        $this->logger->addDebug('Fooo bar');
        if($this->throwException){
            throw new \RuntimeException(self::THROW_MESSAGE);
        }
        $this->runCount++;
        $results = array();
        foreach($paths as $path){
            $message =  self::RUN_MESSAGE.' Modified path: '.$path;
            $event = new ResultEvent(ResultEvent::SUCCEED,$message);
            $results[] = $event;
        }
        $event = new ProcessEvent($this,$results);
        return $event;
    }

    /**
     * @param OptionsResolverInterface $resolver
     *
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // TODO: Implement setDefaultOptions() method.
    }

} 
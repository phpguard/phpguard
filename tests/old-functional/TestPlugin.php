<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;


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
     * Run all command
     *
     * @return void
     */
    public function runAll()
    {
        $this->logger->addSuccess(self::RUN_ALL_MESSAGE);
    }

    /**
     * @param array $paths
     *
     * @return void
     */
    public function run(array $paths = array())
    {
        if($this->throwException){
            throw new \RuntimeException(self::THROW_MESSAGE);
        }
        $this->runCount++;
        $this->logger->addSuccess(self::RUN_MESSAGE);
        foreach($paths as $path){
            $this->logger->addSuccess('Modified path: '.$path);
        }
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
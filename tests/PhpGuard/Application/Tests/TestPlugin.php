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
    public $runCount = 0;

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
     * Run all command
     *
     * @return void
     */
    public function runAll()
    {
        // TODO: Implement runAll() method.
    }

    /**
     * @param array $paths
     *
     * @return void
     */
    public function run(array $paths = array())
    {
        $this->runCount++;
        $this->log('Test Plugin Running');
        foreach($paths as $path){
            $this->log('Modified path: '.$path);
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
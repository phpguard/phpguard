<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\Some;

use PhpGuard\Application\Plugin\Plugin;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SomePlugin extends Plugin
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'some';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        // TODO: Implement getTitle() method.
    }

    /**
     * Run all command
     *
     * @return \PhpGuard\Application\Event\ProcessEvent
     */
    public function runAll()
    {
        // TODO: Implement runAll() method.
    }

    /**
     * @param   array $paths
     *
     * @return \PhpGuard\Application\Event\ProcessEvent
     */
    public function run(array $paths = array())
    {
        // TODO: Implement run() method.
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
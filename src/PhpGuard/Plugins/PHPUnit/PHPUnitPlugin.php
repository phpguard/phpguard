<?php

namespace PhpGuard\Plugins\PHPUnit;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Listen\Event\ChangeSetEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PHPUnitPlugin
 *
 */
class PHPUnitPlugin extends Plugin
{
    public function getName()
    {
        return 'phpunit';
    }

    public function runAll()
    {
        // TODO: Implement runAll() method.
    }

    public function run(array $paths = array())
    {
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // TODO: Implement setDefaultOptions() method.
    }
}
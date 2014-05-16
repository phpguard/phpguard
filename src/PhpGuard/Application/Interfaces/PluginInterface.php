<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PhpGuard\Application\Interfaces;

use PhpGuard\Application\Watcher;
use PhpGuard\Listen\Event\ChangeSetEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface PluginInterface
{
    public function getName();

    public function addWatcher(Watcher $watcher);

    public function runAll();

    public function run(ChangeSetEvent $event);

    public function setOptions(array $options = array());

    public function setDefaultOptions(OptionsResolverInterface $resolver);
}
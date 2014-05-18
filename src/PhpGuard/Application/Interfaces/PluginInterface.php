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
use PhpGuard\Application\Event\EvaluateEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface PluginInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return void
     */
    public function addWatcher(Watcher $watcher);

    public function runAll();

    public function run(array $paths = array());

    /**
     * @return \PhpGuard\Application\Plugin\Plugin
     */
    public function setOptions(array $options = array());

    public function setDefaultOptions(OptionsResolverInterface $resolver);

    public function getMatchedFiles(EvaluateEvent $event);

    /**
     * @return void
     */
    public function configure();
}
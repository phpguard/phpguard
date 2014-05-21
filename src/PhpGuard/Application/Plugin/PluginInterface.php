<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PhpGuard\Application\Plugin;

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
     * @param \PhpGuard\Application\Watcher $watcher
     * @return void
     */
    public function addWatcher(Watcher $watcher);

    /**
     * Run all command
     * @return void
     */
    public function runAll();

    /**
     * @param array $paths
     * @return void
     */
    public function run(array $paths = array());

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @param   bool    $value
     * @return  $this
     */
    public function setActive($value);

    /**
     * @return  bool
     */
    public function getActive();

    /**
     * @param array $options
     * @return \PhpGuard\Application\Plugin\Plugin
     */
    public function setOptions(array $options = array());

    /**
     * @param OptionsResolverInterface $resolver
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * @param EvaluateEvent $event
     * @return void
     */
    public function getMatchedFiles(EvaluateEvent $event);

    /**
     * @return void
     */
    public function configure();
}
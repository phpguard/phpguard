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
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface PluginInterface extends LoggerAwareInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * An array of plugin options
     * @return array
     */
    public function getOptions();

    /**
     * @param  \PhpGuard\Application\Watcher $watcher
     * @return void
     */
    public function addWatcher(Watcher $watcher);

    /**
     * Run all command
     * @return \PhpGuard\Application\Event\ProcessEvent
     */
    public function runAll();

    /**
     * @param array $paths
     *
     * @return \PhpGuard\Application\Event\ProcessEvent
     */
    public function run(array $paths = array());

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @param bool $value
     *                    @return  $this
     */
    public function setActive($value);

    /**
     * @return bool
     */
    public function getActive();

    /**
     * @param  array                               $options
     * @return \PhpGuard\Application\Plugin\Plugin
     */
    public function setOptions(array $options = array());

    /**
     * @param  OptionsResolverInterface $resolver
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * @param  EvaluateEvent $event
     * @return array         Matched files
     */
    public function getMatchedFiles(EvaluateEvent $event);

    /**
     * @return void
     */
    public function configure();

    /**
     * Reset watchers into an empty array
     * @return void
     */
    public function reload();

    /**
     * @param mixed $tag
     *
     * @return void
     */
    public function addTag($tag);
}

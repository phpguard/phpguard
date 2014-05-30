<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Listen\Event\ChangeSetEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PHPUnitPlugin
 *
 */
class PHPUnitPlugin extends Plugin
{
    public function __construct()
    {
        $this->setOptions(array());
    }

    public function addWatcher(Watcher $watcher)
    {
        parent::addWatcher($watcher);
        if($this->options['always_lint']){
            $options = $watcher->getOptions();
            $linters = array_keys($options['lint']);
            if(!in_array('php',$linters)){
                $linters[] = 'php';
                $options['lint'] = $linters;
                $watcher->setOptions($options);
            }
        }
    }

    public function getName()
    {
        return 'phpunit';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'PHPUnit';
    }

    public function runAll()
    {
        return $this->getInspector()->runAll();
    }

    public function run(array $paths = array())
    {
        return $this->getInspector()->run($paths);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => null,
            'all_on_start' => false,
            'all_after_pass' => false,
            'keep_failed' => false,
            'always_lint' => true,
            'run_all' => array(
                'cli' => null,
            ),
        ));
    }

    /**
     * @return \PhpGuard\Plugins\PHPUnit\Inspector
     */
    protected function getInspector()
    {
        return $this->container->get('phpunit.inspector');
    }
}
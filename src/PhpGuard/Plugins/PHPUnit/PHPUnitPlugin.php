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

use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
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
        $arguments = $this->buildArguments();
        $runner = $this->createRunner('phpunit',$arguments);
        $return = $runner->run();
        if(!$return){
            $this->logger->addFail('All tests failed');
        }else{
            $this->logger->addSuccess('All tests success');
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        foreach($paths as $path){
            $arguments = $this->buildArguments();
            $arguments[] = $path;
            $runner = $this->createRunner('phpunit',$arguments);
            $return = $runner->run();
            if(!$return){
                $success = false;
            }
        }

        if($success){
            $this->logger->addSuccess('Test success');
            if($this->options['all_after_pass']){
                $this->logger->addCommon('Run all tests after pass');
                $this->runAll();
            }
        }else{
            $this->logger->addFail('Test failed');
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => null,
            'all_on_start' => false,
            'all_after_pass' => false,
            'keep_failed' => false,
            'always_lint' => true,
        ));
    }

    private function buildArguments()
    {
        $arguments = array();
        $options = $this->options;
        if(isset($options['cli'])){
            $arguments[] = $options['cli'];
        }

        return $arguments;
    }
}
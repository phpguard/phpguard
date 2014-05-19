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

    public function getName()
    {
        return 'phpunit';
    }

    public function runAll()
    {
        $arguments = $this->buildArguments();
        $runner = $this->createRunner('phpunit',$arguments);
        $return = $runner->run();
        if(!$return){
            $this->log('<log-error>Command all tests failed</log-error>');
        }else{
            $this->log('Command all tests success');
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
            $this->log('Command test success');
            if($this->options['all_after_pass']){
                $this->log('Run all tests after pass');
                $this->runAll();
            }
        }else{
            $this->log('<log-error>Command test failed</log-error>');
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => null,
            'all_after_pass' => false,
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
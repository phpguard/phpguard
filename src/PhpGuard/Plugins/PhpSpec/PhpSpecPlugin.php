<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Plugins\PhpSpec\Command\DescribeCommand;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhpSpecPlugin extends Plugin
{
    public function configure()
    {
        if(class_exists('PhpSpec\\Console\\Application')){
            // only load command when phpspec package exists
            /* @var \PhpGuard\Application\Console\Application $application */
            $container = $this->container;
            $application = $container->get('ui.application');
            $command = new DescribeCommand();
            $command->setContainer($this->container);
            $application->add($command);
        }
    }

    public function getName()
    {
        return 'phpspec';
    }

    public function runAll()
    {
        $options = $this->options['run_all'];
        $options = array_merge($this->options,$options);
        $arguments = $this->buildArguments($options);
        $runner = $this->createRunner('phpspec',$arguments);
        $return = $runner->run();
        if($return){
            $this->log('All spec pass');
        }else{
            $this->log('<log-error>PhpSpec Run All failed</log-error>');
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        foreach($paths as $file)
        {
            $arguments = $this->buildArguments($this->options);
            $arguments[] = $file->getRelativePathName();
            $runner = $this->createRunner('phpspec',$arguments);
            $return = $runner->run();
            if(!$return){
                $success = false;
            }
        }
        if($success){
            $this->log('Run spec success');
            if($this->options['all_after_pass']){
                $this->log('Run all specs after pass');
                $this->runAll();
            }
        }else{
            $this->log('<log-error>Run spec failed</log-error>');
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'format' => 'pretty',
            'ansi' => true,
            'all_after_pass' => false,
            'run_all' => array(
                'format' => 'progress'
            )
        ));
    }

    private function buildArguments($options)
    {
        $args = array('run');
        if($options['ansi']){
            $args[] = '--ansi';
        }
        $args[] = '--format='.$options['format'];
        return $args;
    }
}
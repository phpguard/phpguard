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
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhpSpecPlugin extends Plugin
{
    private $phpSpecCommand;

    public function configure()
    {
        /* @var \PhpGuard\Application\Console\Application $application */
        $container = $this->container;
        $application = $container->get('phpguard.ui.application');
        $application->add(new DescribeCommand());
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
        $this->log('Start to run allspecs');
        $return = $runner->run();
        if($return){
            $this->log('All spec pass');
        }else{
            $this->log('PhpSpec Run All failed',array(),LogLevel::ERROR);
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        foreach($paths as $file)
        {
            $this->log(
                'Start to run <comment>phpspec</comment> for <comment>{file}</comment>',
                array('file'=>$file->getRelativePathName())
            );
            $arguments = $this->buildArguments($this->options);
            $arguments[] = $file->getRelativePathName();
            $runner = $this->createRunner('phpspec',$arguments);
            $return = $runner->run();
            if(!$return){
                $success = false;
            }
        }
        if(true===$success){
            $this->log('Spec Success');
            if($this->options['all_after_pass']){
                $this->log('Run all specs after pass');
                $this->runAll();
            }
        }else{
            $this->log('PhpSpec command failed',array(),LogLevel::ERROR);
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
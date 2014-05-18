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
        $arguments = array();
        $runner = $this->createRunner('phpunit',$arguments);
        $return = $runner->run();
        if(!$return){
            $this->log('Command run all failed');
        }else{
            $this->log('Command run all success');
        }
    }

    public function run(array $paths = array())
    {
        $success = true;
        foreach($paths as $path){
            $arguments = array();
            $arguments[] = $path;
            $runner = $this->createRunner('phpunit',$arguments);
            $return = $runner->run();
            if(!$return){
                $success = false;
            }
        }

        if($success){
            $this->log('Command success');
        }else{
            $this->log('Command failed');
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cli' => null,
        ));
    }
}
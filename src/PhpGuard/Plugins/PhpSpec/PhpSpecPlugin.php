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
use PhpGuard\Listen\Event\ChangeSetEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhpSpecPlugin extends Plugin
{
    public function getName()
    {
        return 'phpspec';
    }

    public function runAll()
    {

    }

    public function run(array $paths = array())
    {
        foreach($paths as $file)
        {
            $this->log(
                '<info>start to run <comment>phpspec</comment> for <comment>{file}</comment></info>',
                array('file'=>$file->getRelativePathName())
            );
            $command = $this->getPhpSpecCommand().' '.$file->getRelativePathName();
            passthru($command,$exit);
            if($exit>0){
                $this->log('<comment>phpspec</comment> failed');
            }else{
                $this->log('<info>done executing <comment>phpspec</comment></info>');
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'format' => 'pretty',
            'ansi' => true,
        ));
    }

    private function getPhpSpecCommand()
    {
        $options = array(
            '--no-interaction'
        );

        if($this->options['ansi']){
            $options[] = '--ansi';
        }
        $options[] = '--format='.$this->options['format'];
        return './vendor/bin/phpspec run '.implode(' ',$options);
    }
}
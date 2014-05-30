<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Util;
use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Log\Logger;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class Runner
 *
 */
class Runner extends ContainerAware
{

    /**
     * @param   ProcessBuilder $builder
     * @param   array          $options
     * @return  Process
     */
    public function run(ProcessBuilder $builder,array $options=array())
    {
        $container = $this->container;
        $writer = $container->get('ui.output');

        // normalize run options
        $resolver = new OptionsResolver();
        $this->resolveRunOptions($resolver);
        $options = $resolver->resolve($options);

        $process = $builder->getProcess();
        $process->setTty($options['tty']);
        $options['command'] = $process->getCommandLine();
        $this->getLogger()->addDebug('Begin run command',$options);

        $process->run(function($type,$output) use ($options,$writer){
            if(!$options['silent']){
                $writer->write($output);
            }
        });
        $this->getLogger()->addDebug('End run command',array(
            'exit.code' => $process->getExitCode(),
            'exit.text' => $process->getExitCodeText(),
        ));
        return $process;
    }

    public function findExecutable($name, $default = null, array $extraDirs = array())
    {
        $finder = new ExecutableFinder();
        $extraDirs = array_merge($this->getDefaultDirs(),$extraDirs);
        $executable = $finder->find($name,$default,$extraDirs);
        return is_executable($executable) ? $executable:false;
    }

    private function getDefaultDirs()
    {
        if (false !== ($dirs = $this->container->getParameter('runner.default_dirs',false)) ){
            return $dirs;
        }

        $cwd = getcwd();
        $defaultDirs = array(
            $cwd.'/bin',
            $cwd.'/vendor/bin',
            $cwd.'/app/bin',
        );
        $dirs = array();
        foreach($defaultDirs as $dir){
            if(is_dir($dir)){
                $dirs[] = $dir;
            }
        }
        $this->container->setParameter('runner.default_dirs',$dirs);
        return $dirs;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->container->get('runner.logger');
    }

    private function resolveRunOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'silent' => false,
            'tty' => $this->container->getParameter('runner.tty',false)
        ));
    }
}

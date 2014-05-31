<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Linter;

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\Logger;
use Symfony\Component\Process\Process;

/**
 * Class PhpLinter
 *
 */
class PhpLinter extends ContainerAware implements LinterInterface
{
    /**
     * @var \PhpGuard\Application\Log\Logger
     */
    protected $logger;

    public function setContainer(ContainerInterface $container)
    {
        parent::setContainer($container);
        $logger = new Logger($this->getTitle());
        $logger->pushHandler($container->get('logger.handler'));
        $this->logger = $logger;
    }

    /**
     * @param string $file
     *
     * @return bool
     * @throws LinterException
     */
    public function check($file)
    {
        $process = new Process('php -lf '.$file);
        $process->run();
        if(0===$process->getExitCode()){
            return true;
        }else{
            //$this->logger->addFail('Check syntax failed <comment>'.$process->getOutput().'</comment>');
            throw new LinterException($this,$process->getOutput());
            //return false;
        }
    }

    /**
     * Name for this linter
     *
     * @return string
     */
    public function getName()
    {
        return 'php';
    }

    /**
     * Title for this linter
     *
     * @return string
     */
    public function getTitle()
    {
        return 'PHPLinter';
    }
}
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
use PhpGuard\Application\ContainerAware;
use PhpGuard\Application\Runner;
use Symfony\Component\Process\Process;

/**
 * Class PhpLinter
 *
 */
class PhpLinter extends ContainerAware implements LinterInterface
{
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
            throw new LinterException($this,$process->getOutput());
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
        return 'PHP Linter Tools';
    }
}
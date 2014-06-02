<?php

/*
 * This file is part of the phpguard-behat project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Spec\Prophecy;

use Prophecy\Argument as BaseArgument;
use Prophecy\Argument\Token\CallbackToken as CallbackToken;
use Prophecy\Exception\Prediction\FailedPredictionException;
use Symfony\Component\Process\ProcessBuilder;


class Argument extends BaseArgument
{
    static public function runnerRun($commands)
    {
        if(is_string($commands) && false!==strpos($commands,',')){
            $commands = explode(',',$commands);
        }elseif(!is_array($commands)){
            $commands = array($commands);
        }
        $callback = function(ProcessBuilder $builder) use($commands){
            foreach($commands as $expected){
                $cmd = $builder->getProcess()->getCommandLine();
                if(false===strpos($cmd,$expected)){
                    $format = 'Expected runner to run <comment>%s</comment> but got <comment>%s</comment>';
                    $message = sprintf($format,$expected,$cmd);
                    throw new FailedPredictionException($message);
                }
            }
            return true;
        };
        return new CallbackToken($callback);
    }
} 
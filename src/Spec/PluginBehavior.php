<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Spec;


use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Event\ResultEvent;
use Prophecy\Exception\Prediction\FailedPredictionException;

class PluginBehavior extends ObjectBehavior
{
    public function getMatchers()
    {
        return array(
            'containMessage' => array($this,'resultsContainMessage')
        );
    }

    final public function resultsContainMessage($subject,$expected)
    {
        /* @var ResultEvent[] $results */
        if($subject instanceof ProcessEvent){
            $results = $subject->getResults();
        }elseif($subject instanceof ResultEvent){
            $results = array($subject);
        }else{
            $results = $subject;
        }

        $matched = false;
        $actuals = array();
        foreach($results as $key => $result){
            if(!$result instanceof ResultEvent){
                continue;
            }
            $actual = $result->getMessage();
            if(false!==strpos($actual,$expected)){
                $matched = true;
            }
            $actuals[] = sprintf('<comment>[%s] %s</comment>',$key+1,$actual);
        }

        if(!$matched){
            $format = "Expect results message contain: <comment>%s</comment> but got <comment>%s</comment>\nRecorded results message: ";
            $actuals = "\n".implode("\n",$actuals);
            $message = sprintf($format,$expected,$actual).$actuals;

            throw new FailedPredictionException($message);
        }
        return true;
    }
} 
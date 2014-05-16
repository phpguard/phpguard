<?php

namespace PhpGuard\Application\Console;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Handler\AbstractProcessingHandler;
use PhpGuard\Application\Interfaces\ContainerAwareInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Psr\Log\LogLevel;

/**
 * Class LogHandler
 *
 */
class LogHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    private $container;

    public function __construct($level = LogLevel::DEBUG,$bubble=true)
    {
        parent::__construct($level,$bubble);
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if($record['level_name']!='INFO'){
            return;
        }
        $context = $record['context'];
        $message = (string)$record['message'];
        foreach($context as $key=>$value){
            if(is_array($value)){
                continue;
            }
            $message = str_replace('{'.$key.'}',$value,$message);
        }
        $output = $this->container->get('guard.ui.output');
        $time = $record['datetime'];
        $output->writeln(sprintf('<info>[%s][%s.%s]</info> %s',$time->format('H:i:s'),$record['channel'],$record['level_name'],$message));

    }
}
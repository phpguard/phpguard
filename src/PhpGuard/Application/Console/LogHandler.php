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
        /* @var \Symfony\Component\Console\Output\OutputInterface $output */
        $output = $this->container->get('ui.output');

        $context = $record['context'];
        $message = (string)$record['message'];
        foreach($context as $key=>$value){
            if(is_array($value) || is_object($value)){
                continue;
            }
            $message = str_replace('{'.$key.'}',$value,$message);
        }

        $time = $record['datetime'];
        $format = '<info>[%s][%s] %s</info>';

        if($record['level']==LogLevel::ERROR){
            $format = '<log-error>[%s][%s] %s</log-error>';
        }

        $output->writeln(sprintf($format,$time->format('H:i:s'),$record['level_name'],$message));
    }
}
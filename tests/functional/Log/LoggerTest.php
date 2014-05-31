<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Log;


use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;

class LoggerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->getTester()->run('-vvv');
    }

    /**
     * @dataProvider getTestLogOutput
     */
    public function testLogOutput($type,$message,$expected=null,$context=array())
    {
        if(is_null($expected)){
            $expected = $message;
        }
        $handler = new ConsoleHandler(static::$container->get('ui.output'));
        $logger = new Logger('TestLog');
        $logger->pushHandler($handler);
        $method = 'add'.$type;
        call_user_func(array($logger,$method),$message,$context);
        $this->assertDisplayContains($expected);
    }

    public function getTestLogOutput()
    {
        return array(
            array('debug','debug'),
            array('info','common'),
            array('notice','common'),
            array('warning','warning'),
            array('common','common'),
            array('success','success'),
            array('fail','fail'),
            array('error','error'),
            array('critical','critical'),
            array('alert','alert'),
            array('emergency','emergency'),
            array('debug','test %foo%','test bar',array('foo'=>'bar'))
        );
    }
}
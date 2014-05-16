<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\PHPUnit;


use PhpSpec\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class PhpSpecTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group phpspec
     */
    public function testShouldPassPhpSpecTest()
    {
        try{
            $app = new Application('2.0.0');
            $app->setAutoExit(false);
            $app->setCatchExceptions(true);
            $input = new StringInput('run --ansi -fpretty');
            $return = $app->run($input,new ConsoleOutput());
            if($return==0){
                $this->assertTrue(true);
            }else{
                $this->fail('Spec testing not pass.');
            }
        }catch(\Exception $e){
            $this->fail('Spec testing not pass.');
        }

    }
}
 
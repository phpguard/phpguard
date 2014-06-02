<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Util;

use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Util\Filesystem;
use Symfony\Component\Finder\Finder;

class FilesystemTest extends TestCase
{
    public function testCopyDir()
    {
        $source = static::$cwd.'/tests/fixtures/filesystem';
        Filesystem::create()->mkdir($target = getcwd());
        Filesystem::create()->copyDir($source,$target,Finder::create());
        $this->assertFileExists($target.'/foo/test.txt');
    }

    public function testMkdir()
    {
        $cwd = getcwd();
        $dir = $cwd.'/foo/bar/hello/world';
        Filesystem::create()->mkdir($dir,0755,true);
        $this->assertTrue(is_dir($cwd.'/foo/bar'));
        $this->assertTrue(is_dir($cwd.'/foo/bar/hello/world'));
    }

    public function testSerialization()
    {
        $file = getcwd().'/test.dat';
        $data = array('foo'=>'bar','hello'=>'world');
        Filesystem::create()->serialize($file,$data);

        $unserialized = Filesystem::create()->unserialize($file);
        $this->assertEquals($data,$unserialized);
    }
}

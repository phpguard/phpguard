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

use PhpSpec\ObjectBehavior as BaseObjectBehavior;

/**
 * Class ObjectBehavior
 *
 * @package PhpGuard\Application\Spec
 * @codeCoverageIgnore
 */
abstract class ObjectBehavior extends BaseObjectBehavior
{
    static public $tmpDir;
    static public function mkdir($dir)
    {
        @mkdir($dir,0755,true);
    }

    /**
     * @param string $dir
     */
    static public function cleanDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $flags = \FilesystemIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($dir, $flags);
        $iterator = new \RecursiveIteratorIterator(
            $iterator, \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                @rmdir((string) $path);
            } else {
                @unlink((string) $path);
            }
        }

        @rmdir($dir);
    }
}

ObjectBehavior::$tmpDir = sys_get_temp_dir().'/phpguard-test';
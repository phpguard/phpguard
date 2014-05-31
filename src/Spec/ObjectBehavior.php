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
    public static $tmpDir;
}

ObjectBehavior::$tmpDir = sys_get_temp_dir().'/phpguard-test';

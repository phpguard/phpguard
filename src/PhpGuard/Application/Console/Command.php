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

use PhpGuard\Application\Interfaces\ContainerAwareInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

/**
 * Class Command
 *
 */
abstract class Command extends BaseCommand implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
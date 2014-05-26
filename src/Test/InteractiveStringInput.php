<?php

namespace PhpGuard\Application\Test;

use Symfony\Component\Console\Input\StringInput;

/**
 * Class InteractiveStringInput
 *
 * @package PhpGuard\Application\Test
 * @codeCoverageIgnore
 */
class InteractiveStringInput extends StringInput
{
    public function setInteractive($interactive)
    {
        // this function is disabled to prevent setting non interactive mode on string input after posix_isatty return false
    }
}

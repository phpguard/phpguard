<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Behat\Behat\Context\BehatContext;
use PhpGuard\Application\Behat\PhpGuardContext;

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('phpguard', new PhpGuardContext());
    }
}

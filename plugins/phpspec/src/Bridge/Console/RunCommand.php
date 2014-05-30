<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Console;

use PhpGuard\Plugins\PhpSpec\Bridge\Loader\ResourceLoader;
use PhpSpec\Console\Command\RunCommand as BaseRunCommand;
use PhpSpec\Loader\Suite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class RunCommand
 *
 */
class RunCommand extends BaseRunCommand
{
    protected function configure()
    {
        BaseRunCommand::configure();
        $this->addOption('spec-files',null,InputOption::VALUE_OPTIONAL,'Comma separated spec files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $specFiles = $input->getOption('spec-files');
        if(is_null($specFiles)){
            return $this->doRunNormally($input,$output);
        }
        $specFiles = explode(',',$specFiles);
        $container = $this->getApplication()->getContainer();
        $container->setParam('formatter.name',
            $input->getOption('format') ?: $container->getParam('formatter.name')
        );

        $container->configure();

        $locator     = $input->getOption('spec-files');
        $linenum     = null;

        $loader      = new ResourceLoader($container->get('locator.resource_manager'));
        $suite       = new Suite();
        $loader->loadSpecFiles($suite,$specFiles);
        $suiteRunner = $container->get('runner.suite');

        return $suiteRunner->run($suite);
    }

    private function doRunNormally(InputInterface $input,OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();
        $container->setParam('formatter.name',
            $input->getOption('format') ?: $container->getParam('formatter.name')
        );
        $container->configure();

        $locator = $input->getArgument('spec');
        $linenum = null;
        if (preg_match('/^(.*)\:(\d+)$/', $locator, $matches)) {
            list($_, $locator, $linenum) = $matches;
        }

        $suite       = $container->get('loader.resource_loader')->load($locator, $linenum);
        $suiteRunner = $container->get('runner.suite');

        return $suiteRunner->run($suite);
    }

}
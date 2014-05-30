<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Bridge;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Exception\ConfigurationException;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Serializable;
use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CodeCoverageRunner
 *
 */
class CodeCoverageRunner extends ContainerAware implements Serializable,EventSubscriberInterface
{
    /**
     * @var \PHP_CodeCoverage
     */
    private $coverage;

    /**
     * @var \PHP_CodeCoverage_Filter
     */
    private $filter;

    private $options = array();

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConfigEvents::POSTLOAD => array('onConfigPostLoad',-100),

            ApplicationEvents::postEvaluate => array(
                array('onPostEvaluate',-100),
                array('preCoverage',0)
            ),
            ApplicationEvents::preRunAll => array('preCoverage',10),
            ApplicationEvents::postRunAll => array('onPostRunAll',10),
        );
    }

    static public function setupContainer(ContainerInterface $container)
    {
        $container->setShared('coverage.filter',function(){
            return new \PHP_CodeCoverage_Filter();
        });

        $container->setShared('coverage',function($c){
            $filter = $c->get('coverage.filter');
            return new \PHP_CodeCoverage(null,$filter);
        });

        $container->setShared('coverage.runner',function($c){
            $runner = new CodeCoverageRunner();
            return new $runner;
        });

        $container->setShared('dispatcher.listeners.coverage',function($c){
            return $c->get('coverage.runner');
        });

        $container->setShared('coverage.report.text',function($c){
            $options = $c->get('coverage.runner')->getOptions();

            return new \PHP_CodeCoverage_Report_Text(
                $options['lower_upper_bound'],
                $options['high_lower_bound'],
                $options['show_uncovered_files'],
                /* $showOnlySummary */ false
            );
        });

        $container->setShared('coverage.report.html',function($c){
            return new \PHP_CodeCoverage_Report_HTML();
        });

        $container->setShared('coverage.report.clover',function($c){
            return new \PHP_CodeCoverage_Report_HTML();
        });
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $data = array(
            'coverage' => $this->coverage,
            'filter' => $this->filter,
            'options' => $this->options
        );

        return serialize($data);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->coverage = $data['coverage'];
        $this->filter   = $data['filter'];
        $this->options  = $data['options'];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setContainer(ContainerInterface $container)
    {
        parent::setContainer($container);
        $this->filter = $container->get('coverage.filter');
        $this->coverage = $container->get('coverage');
        $logger = new Logger('Coverage');
        $logger->pushHandler($container->get('logger.handler'));
        $this->logger = $logger;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'enabled'               => false,
            'blacklist'             => array(),
            'whitelist'             => array(),
            'blacklist_files'       => array(),
            'whitelist_files'       => array(),
            'show_uncovered_files'  => true,
            'lower_upper_bound'     => 35,
            'high_lower_bound'      => 70,
            'show_only_summary'     => false,

            'output.html'           => false,
            'output.clover'         => false,
            'output.text'           => false,
            'input.option.enabled'  => false,
        ));

        $resolver->setNormalizers(array(
            'output.html' => function($options,$value){
                if($value){
                    $dir = dirname($value);
                    if(!is_dir($dir)){
                        throw new ConfigurationException(sprintf(
                            'Can not output coverage html to : "%s". Please ensure that directory %s exists and readable',
                            $value,$dir
                        ));
                    }
                    if(!is_dir($value)){
                        mkdir($value,0755,true);
                    }
                    $value = realpath($value);
                    return $value;
                }
            }
        ));
    }

    public function start($id, $clear = false )
    {
        if($this->isEnabled()){
            $this->coverage->start($id,$clear);
        }
    }

    public function stop($append=true,$linesToBeCovered=array(),array $linesToBeUsed=array())
    {
        if($this->isEnabled()){
            $this->coverage->stop($append,$linesToBeCovered,$linesToBeUsed);
        }
    }

    public function preCoverage()
    {
        $this->logger->addDebug('Coverage saving state...');
        $enabled = $this->isEnabled() ? 'enabled':'disabled';
        $this->logger->addCommon('Coverage is <highlight>'.$enabled.'</highlight>');
        $this->saveState();
    }

    public function onConfigPostLoad()
    {
        $guard = $this->container->get('phpguard');
        $options = $guard->getOptions();
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $options = $resolver->resolve($options['coverage']);

        $filter = $this->filter;
        array_map(array($filter, 'addDirectoryToWhitelist'), $options['whitelist']);
        array_map(array($filter, 'addDirectoryToBlacklist'), $options['blacklist']);
        array_map(array($filter, 'addFileToWhitelist'), $options['whitelist_files']);
        array_map(array($filter, 'addFileToBlacklist'), $options['blacklist_files']);

        $enabled = $this->container->getParameter('coverage.enabled',false);
        $this->options['input.option.enabled'] = $enabled;

        $this->logger->addDebug('Coverage configured');
    }

    public function onPostEvaluate()
    {
        $this->logger->addDebug('Coverage Post Evaluate');
    }

    public function onPostRunAll()
    {
        $this->importCached();
        $this->logger->addDebug('coverage post run all');
        $this->process();
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getCoverage()
    {
        return $this->coverage;
    }

    public function saveState()
    {
        Filesystem::serialize(static::getCacheFile(),$this);
    }

    public function isEnabled()
    {
        return $this->options['input.option.enabled'] || $this->options['enabled'];
    }

    private function importCached()
    {
        $runner = static::getCached();
        $this->coverage = $runner->getCoverage();
        $this->filter = $runner->getFilter();
    }

    static public function getCacheFile()
    {
        $dir = PhpGuard::getCacheDir().'/coverage';
        if(!is_dir($dir)){
            mkdir($dir,0775,true);
        }
        return $dir.'/runner.dat';
    }

    /**
     * @return CodeCoverageRunner
     */
    static public function getCached()
    {
        if(file_exists(static::getCacheFile())){
            return Filesystem::unserialize(static::getCacheFile());
        }else{
            return false;
        }
    }

    private function process()
    {
        if(!$this->isEnabled()){
            return;
        }
        $options = $this->options;
        if($options['output.text']){
            $this->reportText();
        }
        if($options['output.html']){
            $this->reportHtml($options['output.html']);
        }

        if($options['output.clover']){
            $this->reportClover($options['output.clover']);
        }
    }

    private function reportText()
    {
        $this->logger->addCommon('Processing text output... please wait!');
        $options = $this->options;
        $report = new \PHP_CodeCoverage_Report_Text(
            $options['lower_upper_bound'],
            $options['high_lower_bound'],
            $options['show_uncovered_files'],
            $options['show_only_summary']
        );
        $output = $report->process($this->coverage,true);
        $this->container->get('ui.output')->writeln($output);
    }

    private function reportHtml($target)
    {
        $relative = str_replace(getcwd().DIRECTORY_SEPARATOR,'',$target);
        $this->logger->addCommon(sprintf(
            'Generating html output to: <comment>%s</comment> please wait!',
            $relative
        ));
        $report = new \PHP_CodeCoverage_Report_HTML();
        $report->process($this->coverage,$target);
    }

    private function reportClover($target)
    {
        $relative = str_replace(getcwd().DIRECTORY_SEPARATOR,'',$target);
        $this->logger->addCommon(sprintf(
            'Generating clover output to: <comment>%s</comment> please wait!',
            $relative
        ));
        $report = new \PHP_CodeCoverage_Report_Clover();
        $report->process($this->coverage,$target);
    }
}

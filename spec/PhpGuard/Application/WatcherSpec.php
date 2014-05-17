<?php

namespace spec\PhpGuard\Application;

require_once __DIR__.'/MockFileSystem.php';

use PhpGuard\Listen\Resource\FileResource;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use spec\PhpGuard\Application\MockFileSystem as mfs;

class WatcherSpec extends ObjectBehavior
{
    function let()
    {
        mfs::mkdir(mfs::$tmpDir);

    }

    function letgo()
    {
        mfs::cleanDir(mfs::$tmpDir);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Watcher');
    }

    function it_throws_when_pattern_is_not_set()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringSetOptions(array());
    }

    function it_should_generate_default_options(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('pattern'))
            ->shouldBeCalled();
        $resolver->setDefaults(array(
                'tags' => array(),
            ))
            ->shouldBeCalled()
        ;
        $this->setDefaultOptions($resolver);

        $this->setOptions(array('pattern'=>'some_pattern'));

        $options = $this->getOptions();
        $options->shouldHaveKey('pattern');
        $options->shouldHaveKey('tags');
    }

    function its_matchFile_returns_true_if_file_is_matched()
    {
        $this->setOptions(array(
            'pattern' => '#.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldReturn(true);

        $resource = new FileResource(__FILE__);
        $this->matchFile($resource)->shouldReturn(true);
    }

    function its_matchFile_should_check_with_relative_path_name()
    {
        $this->setOptions(array(
            'pattern' => '#^spec\/.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldReturn(true);

        touch($file = mfs::$tmpDir.'/foobar.php');

        $this->matchFile($file)->shouldReturn(false);
    }

    function its_matchFile_returns_false_if_file_not_exists()
    {
        $this->matchFile('/tmp/foobar.php')->shouldReturn(false);
    }
}
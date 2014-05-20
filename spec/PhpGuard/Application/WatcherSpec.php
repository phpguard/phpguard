<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Listen\Resource\FileResource;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WatcherSpec extends ObjectBehavior
{
    function let()
    {
        self::mkdir(self::$tmpDir);

    }

    function letgo()
    {
        self::cleanDir(self::$tmpDir);
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
        $this->setOptions(array('pattern'=>'some_pattern'));

        $options = $this->getOptions();
        $options->shouldHaveKey('pattern');
        $options->shouldHaveKey('tags');
        $options->shouldHaveKey('transform');
        $options->shouldHaveKey('groups');
    }

    function its_matchFile_returns_SplFileInfo_is_matched()
    {
        $this->setOptions(array(
            'pattern' => '#.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldHaveType('SplFileInfo');

        $resource = new FileResource(__FILE__);
        $this->matchFile($resource)->shouldHaveType('SplFileInfo');
    }

    function its_matchFile_should_check_with_relative_path_name()
    {
        $this->setOptions(array(
            'pattern' => '#^spec\/.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldHaveType('SplFileInfo');

        touch($file = self::$tmpDir.'/foobar.php');

        $this->matchFile($file)->shouldReturn(false);
    }

    function its_matchFile_returns_false_if_file_not_exists()
    {
        $this->matchFile('/tmp/foobar.php')->shouldReturn(false);
    }

    function its_matchFile_should_transform_file_if_defined()
    {
        $this->setOptions(array(
            'pattern' => '#^src\/(.+)\.php$#',
            'transform' => 'spec/${1}Spec.php'
        ));

        $spl = $this->matchFile(getcwd().'/src/PhpGuard/Application/Watcher.php');
        $spl->getRelativePathName()->shouldReturn('spec/PhpGuard/Application/WatcherSpec.php');
    }

    function its_matchFile_returns_false_if_transformed_file_not_exists()
    {
        $this->setOptions(array(
            'pattern' => '#^src\/(.+)\.php$#',
            'transform' => 'spec/${1}SpecFooBar.php'
        ));

        $this->matchFile(getcwd().'/src/PhpGuard/Application/Watcher.php')
            ->shouldReturn(false)
        ;
    }

    function its_hasGroup_should_check_if_group_exists()
    {
        $this->setOptions(array(
            'pattern' => 'some',
            'groups' => 'foo'
        ));
        $this->shouldHaveGroup('foo');
        $this->shouldNotHaveGroup('bar');
    }

    function its_hasTag_should_check_if_tag_exists()
    {
        $this->setOptions(array(
            'pattern' => 'some',
            'tags' => 'tag'
        ));
        $this->shouldHaveTag('tag');
        $this->shouldHaveTag(array('tag'));
        $this->shouldHaveTag(array());
        $this->shouldHaveTag(null);
        $this->shouldNotHaveTag('foo');
    }
}
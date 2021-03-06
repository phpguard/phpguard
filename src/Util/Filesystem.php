<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Util;
use Symfony\Component\Finder\Finder;

/**
 * Class Filesystem
 *
 */
class Filesystem
{
    /**
     * Serialize file
     *
     * @param string $targetFile
     * @param mixed  $data
     */
    public function serialize($targetFile,$data)
    {
        $contents = serialize($data);
        $this->putFileContents($targetFile,$contents);
    }

    /**
     * Unserialize file
     *
     * @param string $file A file to be unserialize
     *
     * @return mixed
     */
    public function unserialize($file)
    {
        $contents =$this->getFileContents($file);
        $data = unserialize($contents);

        return $data;
    }

    /**
     * @param string $from
     * @param string $to
     * @param Finder $finder
     *
     * @author Anthonius Munthi <me@itstoni.com>
     */
    public function copyDir($from,$to,Finder $finder)
    {
        /* @var \Symfony\Component\Finder\SplFileInfo $path */

        $finder->in($from);
        foreach ($finder->files() as $path) {
            $targetDir = $to.DIRECTORY_SEPARATOR.$path->getRelativePath();
            if (!is_dir($targetDir)) {
                mkdir($targetDir,0755,true);
            }
            $target = $to.DIRECTORY_SEPARATOR.$path->getRelativePathname();
            copy($path->getRealPath(),$target);
        }
    }

    public function mkdir($dir,$mode=0777,$recursive=true)
    {
        @mkdir($dir,$mode,$recursive);
    }

    /**
     * @param string $dir
     */
    public function cleanDir($dir)
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

    /**
     * @param $path
     * @return bool
     */
    public function pathExists($path)
    {
        return file_exists($path);
    }

    /**
     * @param $path
     * @return string
     */
    public function getFileContents($path)
    {
        return file_get_contents($path);
    }

    /**
     * @param $path
     * @param $content
     */
    public function putFileContents($path, $content)
    {
        file_put_contents($path, $content);
    }

    /**
     * @param $path
     * @return bool
     */
    public function isDirectory($path)
    {
        return is_dir($path);
    }

    /**
     * @param $path
     * @return bool
     */
    public function isFile($path)
    {
        return is_file($path);
    }

    /**
     * @return Filesystem
     */
    static public function create()
    {
        return new self();
    }
}

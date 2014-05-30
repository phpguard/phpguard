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
    static public function serialize($targetFile,$data)
    {
        $contents = serialize($data);
        file_put_contents($targetFile,$contents,LOCK_EX);
    }

    /**
     * Unserialize file
     *
     * @param   string $file A file to be unserialize
     *
     * @return  mixed
     */
    static public function unserialize($file)
    {
        $contents = file_get_contents($file,LOCK_EX);
        $data = unserialize($contents);

        return $data;
    }

    /**
     * @param string    $from
     * @param string    $to
     * @param Finder    $finder
     *
     * @author Anthonius Munthi <me@itstoni.com>
     */
    static public function copyDir($from,$to,Finder $finder)
    {
        /* @var \Symfony\Component\Finder\SplFileInfo $path */

        $finder->in($from);
        foreach($finder->files() as $path){
            $targetDir = $to.DIRECTORY_SEPARATOR.$path->getRelativePath();
            if(!is_dir($targetDir)){
                mkdir($targetDir,0755,true);
            }
            $target = $to.DIRECTORY_SEPARATOR.$path->getRelativePathname();
            copy($path->getRealPath(),$target);
        }
    }
}
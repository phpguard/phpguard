<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Plugin;

interface TaggableInterface
{
    /**
     * Get supported tags
     * @return array
     */
    public function getTags();

    /**
     * Check if tags supported
     * @param mixed $tags String or an array of tags
     *
     * @return bool True if tags supported
     */
    public function hasTags($tags);

    /**
     * Add a new tags
     * @param mixed $tags
     *
     * @return  $this
     */
    public function addTags($tags);
}

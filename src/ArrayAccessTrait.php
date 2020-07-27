<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.07.20 05:49:15
 */

declare(strict_types = 1);
namespace dicr\helper;

use ArrayAccess;

/**
 * Реализация интерфейса ArrayAccess.
 */
trait ArrayAccessTrait
{
    /**
     * @param string|int $offset
     * @return bool
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->{$offset});
    }

    /**
     * @param string|int $offset
     * @return mixed
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * @param string|int $offset
     * @param mixed $item
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $item) : void
    {
        $this->{$offset} = $item;
    }

    /**
     * @param $offset
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset) : void
    {
        $this->{$offset} = null;
    }
}

<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 11.07.20 10:13:19
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
    public function offsetExists($offset)
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
    public function offsetSet($offset, $item)
    {
        $this->{$offset} = $item;
    }

    /**
     * @param $offset
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }
}

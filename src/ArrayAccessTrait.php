<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor A Tarasov <develop@dicr.org>
 */

declare(strict_types = 1);
namespace dicr\helper;

/**
 * Реализация интерфейса ArrayAccess
 */
trait ArrayAccessTrait
{
    /**
     * {@inheritDoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    /**
     * {@inheritDoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * {@inheritDoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $item)
    {
        $this->{$offset} = $item;
    }

    /**
     * {@inheritDoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }
}
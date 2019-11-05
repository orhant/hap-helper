<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor A Tarasov <develop@dicr.org>
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
use function is_array;
use function is_int;

/**
 * Фильтр данных.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Filter
{
    /**
     * Парсит id
     *
     * @param mixed $id
     * @return int|null
     * @throws \InvalidArgumentException
     */
    public static function id($id)
    {
        if (! is_int($id)) {
            $id = trim($id);
            if ($id === '') {
                return null;
            }

            if (! ctype_digit($id)) {
                throw new InvalidArgumentException('id');
            }

            $id = (int)$id;
        }

        if ($id < 0) {
            throw new InvalidArgumentException('id');
        }

        if (empty($id)) {
            return null;
        }

        return $id;
    }

    /**
     * Фильтрует массив id
     *
     * @param mixed $ids
     * @return int[]
     */
    public static function ids($ids)
    {
        if ($ids === null) {
            return [];
        }

        if (! is_array($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $i => $id) {
            $id = self::id($id);
            if ($id === null) {
                unset($ids[$i]);
            } else {
                $ids[$i] = $id;
            }
        }

        $ids = array_unique($ids, SORT_NUMERIC);

        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}

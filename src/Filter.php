<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 23.12.19 20:12:45
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
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
     * @throws InvalidArgumentException
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
        $ids = (array)($ids ?: []);

        foreach ($ids as $i => &$id) {
            $id = self::id($id);
            if ($id === null) {
                unset($ids[$i]);
            }
        }

        unset($id);

        if (! empty($ids)) {
            $ids = array_unique($ids, SORT_NUMERIC);
            sort($ids, SORT_NUMERIC);
        }

        return $ids;
    }

    /**
     * Фильрует масив строк.
     *
     * @param string|array $strings
     * @return string[]
     */
    public static function strings($strings)
    {
        $strings = (array)($strings ?: []);

        foreach ($strings as $i => &$v) {
            $v = (string)$v;
            if ($v === '') {
                unset($strings[$i]);
            }
        }

        unset($v);

        if (! empty($strings)) {
            $strings = array_unique($strings);
            sort($strings, SORT_STRING);
        }

        return $strings;
    }
}

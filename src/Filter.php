<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 13.10.20 14:19:03
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;

use function array_filter;
use function array_map;
use function array_unique;
use function ctype_digit;
use function is_int;
use function is_numeric;
use function sort;
use function trim;

use const SORT_STRING;

/**
 * Фильтр данных.
 *
 * @deprecated use dicr/yii2-validate
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
    public static function id($id) : ?int
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
    public static function ids($ids) : array
    {
        $ids = (array)($ids ?: []);

        $ids = array_filter($ids, static function ($id) : bool {
            return is_numeric($id) && $id > 0;
        });

        $ids = array_map('\intval', $ids);

        $ids = array_unique($ids);

        sort($ids);

        return $ids;
    }

    /**
     * Фильтрует массив строк.
     *
     * @param string|array $strings
     * @return string[]
     */
    public static function strings($strings) : array
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

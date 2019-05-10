<?php
namespace app\helpers;

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
     * @throws \InvalidArgumentException
     * @return int|null
     */
    public static function id($id)
    {
        if (!is_int($id)) {
            $id = trim($id);
            if ($id === '') {
                return null;
            }

            if (!ctype_digit($id)) {
                throw new \InvalidArgumentException('id');
            }

            $id = (int)$id;
        }

        if ($id < 0) {
            throw new \InvalidArgumentException('id');
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
     * @param bool $throw
     * @return int[]
     */
    public static function ids($ids, bool $throw = false)
    {
        if ($ids === null) {
            return [];
        }

        if (!is_array($ids)) {
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
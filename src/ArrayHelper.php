<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.07.20 11:49:14
 */

declare(strict_types = 1);
namespace dicr\helper;

use function count;
use function is_array;

/**
 * Работа с массивами.
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * Удаляет элемент из массива.
     * В отличие от оригинала поддерживает комплексный ключ.
     *
     * @param array $array исходный массив
     * @param string|array $path ключ для удаления. Может быть строкой с путем ключа, разделенным "." или массивом.
     * @param mixed $default
     * @return mixed
     */
    public static function remove(&$array, $path, $default = null)
    {
        if (! is_array($path)) {
            $path = explode('.', $path);
        }

        while (count($path) > 1) {
            $key = array_shift($path);
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                return $default;
            }
            $array = &$array[$key];
        }

        $key = array_shift($path);
        $ret = $array[$key] ?? $default;
        unset($array[$key]);

        return $ret;
    }
}

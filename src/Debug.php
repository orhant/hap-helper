<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 15.01.20 12:42:19
 */

declare(strict_types = 1);

namespace dicr\helper;

use const YII_DEBUG;

/**
 * Класс для отладки.
 */
class Debug
{
    /**
     * Дамп значения в html.
     *
     * @param mixed $val
     * @param bool $exit выйти после дампа
     * @noinspection PhpUnused
     */
    public static function xmp($val, bool $exit = true)
    {
        if (! YII_DEBUG) {
            return;
        }

        echo '<xmp>';

        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($val);

        if ($exit) {
            exit;
        }
    }
}

<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.11.19 03:58:12
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
     */
    public static function xmp($val, bool $exit = true)
    {
        /** @noinspection PhpUndefinedConstantInspection */
        if (! YII_DEBUG) {
            return;
        }

        /** @noinspection HtmlDeprecatedTag */
        echo '<xmp>';

        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($val);

        if ($exit) {
            exit;
        }
    }
}

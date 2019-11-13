<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor A Tarasov <develop@dicr.org>
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

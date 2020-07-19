<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.07.20 18:00:41
 */

/** @noinspection ForgottenDebugOutputInspection */
declare(strict_types = 1);
namespace dicr\helper;

use function var_dump;
use const YII_DEBUG;

/**
 * Класс для отладки.
 */
class Debug
{
    /**
     * Дамп значения в html.
     *
     * @param mixed ...$vars
     */
    public static function xmp(...$vars)
    {
        if (YII_DEBUG) {
            echo '<xmp>';

            foreach ($vars as $var) {
                var_dump($var);
            }

            echo '</xmp>';
        }
    }

    /**
     * Дамп значений и выход.
     *
     * @param mixed ...$vars
     */
    public static function xe(...$vars)
    {
        if (YII_DEBUG) {
            echo '<xmp>';

            foreach ($vars as $var) {
                var_dump($var);
            }

            exit;
        }
    }
}

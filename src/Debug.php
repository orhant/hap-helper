<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 24.05.20 13:54:39
 */

declare(strict_types = 1);

namespace dicr\helper;

use function count;
use function reset;
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
     * @param array $vars
     * @noinspection PhpUnused
     */
    public static function xmp(...$vars)
    {
        if (YII_DEBUG && ! empty($vars)) {
            echo '<xmp>';
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump(count($vars) > 1 ? $vars : reset($vars));
            exit;
        }
    }
}

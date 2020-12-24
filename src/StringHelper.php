<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 25.12.20 04:16:34
 */

declare(strict_types = 1);
namespace dicr\helper;

use function mb_strtolower;
use function mb_substr;

/**
 * String Helper.
 */
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * Первая буква в нижний регистр.
     *
     * @param $string
     * @param string $encoding
     * @return string
     * @noinspection PhpMethodNamingConventionInspection
     */
    public static function mb_lcfirst($string, $encoding = 'UTF-8') : string
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $rest = mb_substr($string, 1, null, $encoding);

        return mb_strtolower($firstChar, $encoding) . $rest;
    }
}

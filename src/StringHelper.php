<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.12.19 06:16:11
 */

declare(strict_types = 1);
namespace dicr\helper;

/**
 * {@inheritdoc}
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180620
 */
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * Первая буква в нижний регистр.
     *
     * @param $string
     * @param string $encoding
     * @return string
     */
    public static function mb_lcfirst($string, $encoding = 'UTF-8')
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $rest = mb_substr($string, 1, null, $encoding);

        return mb_strtolower($firstChar, $encoding) . $rest;
    }
}

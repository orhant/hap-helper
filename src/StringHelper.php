<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.06.20 04:41:51
 */

declare(strict_types = 1);

namespace dicr\helper;

use function explode;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;

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
     * @noinspection PhpUnused
     */
    public static function mb_lcfirst($string, $encoding = 'UTF-8')
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $rest = mb_substr($string, 1, null, $encoding);

        return mb_strtolower($firstChar, $encoding) . $rest;
    }

    /**
     * Подмена переменных в строке.
     *
     * Подменяет строки шаблонных переменных вида ${vars|attribute|attribute}.
     * Значения берутся из массива vars. Пример:
     *
     * ```php
     * Html::replaceVars("Купить ${prod|name}", [
     *   'prod' => ['name' => "Автомобиль"]
     * ]);
     * ```
     *
     * @param string|null $string строка с переменными
     * @param array $vars значения для подмены (многомерный массив объектов)
     * @param array $opts опции
     * - bool $cleanVars - удалять ненайденные переменные
     * - bool $cleanText - удалять весь текст, если есть ненайденные переменные
     * @return string
     */
    public static function replaceVars($string, array $vars = [], array $opts = [])
    {
        // пропускаем пустые строки
        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        // регулярное выражение
        $regex = '~\$\{([^\}]+)\}~um';

        // находим и заменяем все переменные
        $string = preg_replace_callback($regex, static function(array $matches) use ($vars) {
            // получаем ключ
            $key = explode('|', $matches[1]);

            // комплексное название переменной
            return ArrayHelper::getValue($vars, $key) ?? $matches[0];
        }, $string);

        // удаляем весь текст если переменные не есть ненайденные переменные
        if (! empty($opts['cleanText']) && preg_match($regex, $string)) {
            return '';
        }

        // удаление ненайденных переменных
        if (! empty($opts['cleanVars'])) {
            $string = (string)preg_replace($regex, '', $string);
        }

        // заменяем несколько пробелов одним
        $string = (string)preg_replace('~[\h\t]+~uim', ' ', $string);

        return $string;
    }
}

<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.06.20 07:54:49
 */

declare(strict_types = 1);

namespace dicr\helper;

use function array_key_exists;
use function explode;
use function is_array;
use function is_object;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function trim;

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
     * Также можно применять фильтры:
     *
     * ```php
     * Html::replaceVars("Купить ${title|trim|lower|esc}", [
     *   'title' => 'Значение строки'
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
        $filters = [
            'trim' => static function(string $string) {
                return trim($string);
            },
            'esc' => static function(string $string) {
                return Html::esc($string);
            },
            'lower' => static function(string $string) {
                return mb_strtolower($string);
            },
            'upper' => static function(string $string) {
                return mb_strtoupper($string);
            },
            'ucfirst' => static function(string $string) {
                return StringHelper::mb_ucfirst($string);
            },
            'lcfirst' => static function(string $string) {
                return StringHelper::mb_lcfirst($string);
            }
        ];

        // пропускаем пустые строки
        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        // регулярное выражение
        $regex = '~\$\{([^\}]+)\}~um';

        // находим и заменяем шаблон переменной
        $string = preg_replace_callback($regex, static function(array $matches) use ($vars, $filters) {
            // получаем ключи
            $keys = explode('|', $matches[1]);

            /** @var mixed $value текущее значение */
            $value = $vars;

            // обходим ключи по порядку
            foreach ($keys as $key) {
                if (array_key_exists($key, $filters)) {
                    // если это фильтр, то применяем фильтр значения
                    $value = $filters[$key]($value);
                } elseif (is_array($value)) {
                    // переходим к следующему значению в дереве массива
                    $value = $value[$key] ?? null;
                } elseif (is_object($value)) {
                    // переходим к следующему значению в дереве объектов
                    $value = $value->{$key} ?? null;
                } else {
                    // значение не найдено
                    $value = null;
                }

                // если значение не найдено, то прекращаем поиск
                if (! isset($value)) {
                    break;
                }
            }

            // если значение не найдено, то возвращаем шаблон без изменения
            if (! isset($value)) {
                return $matches[0];
            }

            return (string)$value;
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

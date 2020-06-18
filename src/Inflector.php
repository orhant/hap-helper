<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.06.20 04:40:33
 */

declare(strict_types = 1);

namespace dicr\helper;

use function array_keys;
use function array_values;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Class Inflector
 */
class Inflector extends \yii\helpers\Inflector
{
    /** @var string[] соответствие символов транслитерации */
    public const TRANSLIT = [
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya'
    ];

    /**
     * Создает ЧПУ.
     *
     * @param string $string
     * @param string $replacement
     * @param bool $lowercase
     * @return string
     */
    public static function slug($string, $replacement = '-', $lowercase = true)
    {
        // очищаем специальные символы и пробелы
        $string = trim(preg_replace('~[[:cntrl:]]|[\x00-\x1F\x7F\xA0\s\h\t\v\r\n]+~uim', ' ', $string));
        if ($string === '') {
            return '';
        }

        // конвертируем в нижний реестр
        if ($lowercase) {
            $string = mb_strtolower($string);
        }

        // транслитерация русских букв
        $string = (string)str_replace(
            array_keys(self::TRANSLIT), array_values(self::TRANSLIT), $string
        );

        // подстановка известных символов
        $string = (string)str_replace(['+', '@'], ['plus', 'at'], $string);

        // заменяем все, которые НЕ разрешены
        $string = preg_replace('~[^a-z0-9\-_.\~]+~uim', '-', $string);

        // удаляем подстановочные в начале, в конце и задвоения
        $string = preg_replace('~(^-)|(-$)|(-{2,})~um', '', $string);

        // заменяем подстановочные на заданные
        if ($replacement !== '-') {
            $string = (string)str_replace('-', $replacement, $string);
        }

        return $string;
    }
}

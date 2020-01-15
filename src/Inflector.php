<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 15.01.20 12:42:19
 */

declare(strict_types = 1);

namespace dicr\helper;

/**
 * Inflector.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
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
        $string = trim(preg_replace('~[\x00-\x1F\x7F\xA0\s\h\v\r\n\t]+~uim', ' ', (string)$string));
        if ($string === '') {
            return '';
        }

        // конверируем в нижний реестр
        $string = mb_strtolower($string);

        // транслитерация русских букв
        $string = (string)preg_replace(array_map(static function ($ch) {
            return '~' . $ch . '~uism';
        }, array_keys(self::TRANSLIT)), array_values(self::TRANSLIT), $string);

        // подстановка известных символов
        $knownChars = [
            '~\+~' => 'plus',
            '~\@~' => 'at',
        ];

        $string = (string)preg_replace(array_keys($knownChars), array_values($knownChars), $string);

        // заменяем все, которые НЕ разрешены
        $string = preg_replace('~[^a-z0-9\-_.\~]+~uim', '-', $string);

        // удаляем подстановочные вначале, в конце и задвоения
        //$string = preg_replace (['~(^\-+)|(\-+$)~uism', '~\-{2,}~uism'], ['', '-'], $string);
        $string = preg_replace(['~(^-+)|(-+$)~uism'], [''], $string);

        // заменяем подстановочные на заданные
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $string = str_replace('-', $replacement, $string);

        return $string;
    }
}

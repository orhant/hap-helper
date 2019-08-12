<?php
namespace apkv\helpers;

/**
 * Inflector.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Inflector extends \yii\helpers\Inflector
{
	/** @var string[] соответствие символов транслитерации */
    const TRANSLIT = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    /**
     * Создает ЧПУ.
     *
     * @param string $string
     * @param string $replacement
     * @param boolean $lowercase
     * @return string
     */
    public static function slug($string, $replacement = '-', $lowercase = true)
    {
        // очищаем специальные символы
        $string = preg_replace('~[\x00-\x1F\x7F\xA0]+~uism', ' ', $string);

        // очищаем различные пробелы
	    $string = trim(preg_replace('~[\s\h\v\r\n\t]+~uism', ' ', $string));
	    if ($string === '') {
	        return '';
	    }

	    // конверируем в нижний реестр
	    $string = mb_strtolower($string);

	    // транслируем русские буквы в латинские
	    $srch = [];
	    $rpls = [];
	    foreach (self::TRANSLIT as $rus => $lat) {
	        $srch[] = '~'.$rus.'~uism';
	        $rpls[] = $lat;
	    }

	    $string = preg_replace($srch, $rpls, $string);

	    // заменяем все, которые НЕ разрешены
	    $notallowed = '~[^a-z0-9\-\_\.\~]+~uism';
	    $string = preg_replace ($notallowed, '-', $string);

	    // удаляем подстановочные вначале, в конце и задвоения
	    $string = preg_replace (['~(^\-+)|(\-+$)~uism', '~\-{2,}~uism'], ['', '-'], $string);

	    // заменяем подстановочные на заданные
	    $string = str_replace('-', $replacement, $string);

	    return $string;
    }
}
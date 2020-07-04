<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.07.20 11:50:17
 */

declare(strict_types = 1);

namespace dicr\helper;

use yii\base\InvalidArgumentException;
use function array_key_exists;
use function array_keys;
use function array_values;
use function explode;
use function idate;
use function implode;
use function is_array;
use function is_object;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_replace;
use function time;
use function trim;

/**
 * Class Inflector
 */
class Inflector extends \yii\helpers\Inflector
{
    /** @var string[] соответствие символов транслитерации */
    public const LETTERS = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',
        'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    /** @var string[] дни недели (0 - Пн) */
    public const WEEKDAYS = [
        'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'
    ];

    /** @var string[] короткие месяцы */
    public const MONTH_SHORT = [
        'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июль', 'авг', 'сен', 'окт', 'ноя', 'дек'
    ];

    /** @var string[] длинные месяцы (с большой буквы, чтобы "май" не совпадал в словаре с коротким названием) */
    public const MONTH_LONG = [
        'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь',
        'Декабрь'
    ];

    /** @var string[] родительный падеж месяцев */
    public const MONTH_GENITIVE = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября',
        'декабря'
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
            array_keys(self::LETTERS), array_values(self::LETTERS), $string
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

    /**
     * Количество товаров.
     *
     * @param int|string $count
     * @return string
     */
    public static function numProds($count)
    {
        $count = (int)$count;
        if ($count < 5 || $count > 20) {
            $mod = $count % 10;
            if ($mod === 1) {
                $word = T::t('товар');
            } elseif ($mod === 2 || $mod === 3 || $mod === 4) {
                $word = T::t('товара');
            }
        }

        return $word ?? T::t('товаров');
    }

    /**
     * Форма кол-ва моделей.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numModels($count)
    {
        $count = (int)$count;
        if ($count < 5 || $count > 20) {
            $mod = $count % 10;
            if ($mod === 1) {
                $word = T::t('модель');
            } elseif ($mod === 2 || $mod === 3 || $mod === 4) {
                $word = T::t('модели');
            }
        }

        return $word ?? T::t('моделей');
    }

    /**
     * Форматирует срок в днях.
     *
     * @param int $days срок дней (0 - сегодня, 1 - завтра)
     * @return string текстовое представление
     */
    public static function daysTerm(int $days)
    {
        if ($days < 0) {
            throw new InvalidArgumentException('days');
        }

        $daysMap = [
            0 => T::t('сегодня'),
            1 => T::t('завтра'),
            2 => T::t('послезавтра'),
            3 => T::t('через {num} дня', ['num' => 3]),
            4 => T::t('через {num} дня', ['num' => 4]),
            5 => T::t('через {num} дней', ['num' => 5]),
            6 => T::t('через {num} дней', ['num' => 6]),
            7 => T::t('через неделю'),
            8 => T::t('через {num} дней', ['num' => 8]),
            9 => T::t('через {num} дней', ['num' => 9]),
            10 => T::t('через {num}} дней', ['num' => 10]),
            11 => T::t('через {num} дней', ['num' => 11]),
            12 => T::t('через {num} дней', ['num' => 12]),
            13 => T::t('через {num} дней', ['num' => 13]),
            14 => T::t('через {num} недели', ['num' => 2]),
            15 => T::t('через {num} дней', ['num' => 15]),
            16 => T::t('через {num} дней', ['num' => 16]),
            17 => T::t('через {num} дней', ['num' => 17]),
            18 => T::t('через {num} дней', ['num' => 18]),
            19 => T::t('через {num} дней', ['num' => 19]),
            20 => T::t('через {num} дней', ['num' => 20]),
            21 => T::t('через {num} недели', ['num' => 3]),
            30 => T::t('через месяц'),
            31 => T::t('через месяц'),
            62 => T::t('через {num} месяца', ['num' => 2])
        ];

        if (isset($daysMap[$days])) {
            return $daysMap[$days];
        }

        $time = time() + 86400 * $days;

        // получаем день и месяц
        return idate('d', $time) . ' ' .
            mb_strtolower(T::t(self::MONTH_GENITIVE[idate('m', $time) - 1]));
    }

    /**
     * Группирует список дней недели
     *
     * @param array $days массив дней, в формате:
     *    [0, 1, 2, 4, 7]
     *
     * @return string[] группы дней в формате:
     *    ['Пн-Ср', 'Пт', 'Вс']
     */
    public static function groupDays(array $days)
    {
        $groupDays = [];
        $startDay = null;
        $endDay = null;

        foreach ($days as $day) {
            $day = (int)$day;

            // если это первый день, то формируем новый период
            if (! isset($endDay) || $day !== $endDay + 1) {
                if (isset($startDay)) {
                    // новый день не следует за предыдущим - сохраняем прошлый период
                    $group = T::t(self::WEEKDAYS[$startDay]);
                    if ($endDay > $startDay) {
                        $group .= '-' . T::t(self::WEEKDAYS[$endDay]);
                    }

                    $groupDays[] = $group;
                }

                // начинаем новый период
                $startDay = $day;
            }

            $endDay = $day;
        }

        // закрываем последний период
        if (isset($startDay)) {
            $group = T::t(self::WEEKDAYS[$startDay]);
            if ($endDay > $startDay) {
                $group .= '-' . T::t(self::WEEKDAYS[$endDay]);
            }

            $groupDays[] = $group;
        }

        return $groupDays;
    }

    /**
     * Конвертирует график работы в короткий формат.
     *
     * @param array $schedule график работы в формате:
     *   [
     *     0 => ['09:00', '18:00'],
     *     1 => ['09:00', '18:00'],
     *     ..
     *     5 => ['11:00', '16:00'],
     *     6 => null
     *   ]
     *
     * @return string[] короткий график работы в формате:
     *   [
     *     'Пн-Пт' => '09:00 - 18:00',
     *     'Сб' => '11:00 - 16:00',
     *     'Вс' => 'выходной'
     *   ]
     */
    public static function shortSchedule($schedule)
    {
        if (empty($schedule)) {
            return [];
        }

        /** @var array рабочие дни */
        $workdays = [];

        /** @var int[] выходные дни workTime => [days] */
        $holidays = [];

        // форматируем время работы всех дней
        for ($i = 0; $i < 7; $i ++) {
            $workTime = $schedule[$i] ?? null;

            // выходной день
            if (empty($workTime)) {
                $holidays[] = $i;
                continue;
            }

            // форматируем рабочее время
            $workdays[$workTime[0] . ' - ' . $workTime[1]][] = $i;
        }

        $schedule = [];

        // группируем рабочие дни
        foreach ($workdays as $workTime => $days) {
            $days = implode(',', static::groupDays($days));
            $schedule[$days] = $workTime;
        }

        // группируем выходные
        if (! empty($holidays)) {
            $days = implode(',', static::groupDays($holidays));
            $schedule[$days] = T::t('выходной');
        }

        return $schedule;
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

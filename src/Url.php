<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 01.08.20 01:40:38
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
use RuntimeException;
use Yii;
use yii\base\ExitException;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_values;
use function count;
use function explode;
use function filter_var;
use function implode;
use function is_array;
use function is_object;
use function ksort;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function range;
use function sprintf;
use function trim;
use function urldecode;
use function urlencode;
use const FILTER_VALIDATE_INT;
use const PHP_URL_HOST;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Url Helper.
 */
class Url extends \yii\helpers\Url
{
    /**
     * Парсит параметры запроса из строки
     *
     * @param string|array $query
     * @return array параметры в виде массива
     */
    public static function parseQuery($query) : array
    {
        if ($query === null || $query === '' || $query === []) {
            $query = [];
        } elseif (! is_array($query)) {
            $query = trim((string)$query, " \t\n\r\0\x0B?");
            if ($query === '') {
                $query = [];
            } else {
                $parsed = [];
                parse_str($query, $parsed);
                $query = $parsed;
            }
        }

        return (array)$query;
    }

    /**
     * Рекурсивное построение строки параметров.
     *
     * @param array $query
     * @param string $parentKey
     * @return string[]
     */
    private static function internalBuildQuery(array $query, string $parentKey = '')
    {
        // удаляет из параметров некорректные ключи и null-значения
        $filterParams = static function($params) use (&$filterParams) {
            $filtered = [];

            // преобразуем query, удаляя некорректные ключи и null-значения
            foreach ((array)$params as $k => $v) {
                // пропускаем некорректные ключи
                if ((string)$k === '') {
                    continue;
                }

                // считаем значения null для удаления
                if ($v === null) {
                    continue;
                }

                if (is_array($v) || is_object($v)) {
                    $v = $filterParams($v);
                } else {
                    $v = (string)$v;
                }

                $filtered[$k] = $v;
            }

            return $filtered;
        };

        // проверяет является ли массив индексным списком параметров
        $isIndexed = static function($params) {
            $params = (array)$params;
            if (empty($params)) {
                return true;
            }

            $keys = array_map(static function($key) {
                return filter_var($key, FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 0
                    ]
                ]);
            }, array_keys($params));

            return $keys === range(0, count($params) - 1);
        };

        $query = $filterParams($query);
        $isIndexed = $parentKey !== '' && $isIndexed($query);
        $parts = [];

        foreach ($query as $k => $v) {
            if ($parentKey === '') {
                $key = urlencode((string)$k);
            } else {
                $key = $isIndexed ? $parentKey . '[]' : $parentKey . '[' . urlencode((string)$k) . ']';
            }

            if (is_array($v) || is_object($v)) {
                $v = (array)$v;
                if ($v === []) {
                    $parts[] = $key . '[]';
                } else {
                    $parts = array_merge($parts, self::internalBuildQuery($v, $key));
                }
            } else {
                $parts[] = ((string)$v === '') ? $key : $key . '=' . urlencode((string)$v);
            }
        }

        return $parts;
    }

    /**
     * Конвертирует параметры запроса в строку
     *
     * @param array|object|string $query
     * @return string
     */
    public static function buildQuery($query) : string
    {
        if ($query === null || $query === '' || $query === []) {
            $query = '';
        } elseif (is_array($query) || is_object($query)) {
            //$query = preg_replace(['~%5B~i', '~%5D~i', '~\[\d+\]~'], ['[', ']', '[]'], http_build_query($query));
            $parts = self::internalBuildQuery((array)$query);
            $query = implode('&', $parts);
        }

        return (string)$query;
    }

    /**
     * Фильтрует парамеры запроса, удаляя ключи с пустыми значениями.
     *
     * @param array|string $query
     * @return array
     */
    public static function filterQuery($query) : array
    {
        return array_filter(static::parseQuery($query), static function($v) {
            if ($v === null || $v === '' || $v === []) {
                return false;
            }

            if (is_array($v)) {
                $v = self::filterQuery($v);
                if (empty($v)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Нормализует параметры запроса.
     * Конвертирует из строки в массив, сортирует по названию параметров.
     *
     * @param array|string $query
     * @return array
     */
    public static function normalizeQuery($query) : array
    {
        $query = static::parseQuery($query);
        ksort($query);

        return array_map(static function($v) {
            return is_array($v) ? self::normalizeQuery($v) : (string)$v;
        }, $query);
    }

    /**
     * Вычитание параметров рекурсивно
     *
     * Из параметров args1 вычитаются параметры args2
     *
     * @param array|string $query1
     * @param array|string $query2
     * @return array $query1 - $query2
     */
    public static function diffQuery($query1, $query2) : array
    {
        $query1 = self::parseQuery($query1);
        if (empty($query1)) {
            return [];
        }

        $query2 = self::parseQuery($query2);
        if (empty($query2)) {
            return $query1;
        }

        return static::unflatQuery(array_diff(
            static::flatQuery($query1),
            static::flatQuery($query2)
        ));
    }

    /**
     * Преобразовывает многомерные данные параметров в плоский массив параметров.
     *
     * @param array|string $query парамеры запроса
     * @return string[] одномерный массив параметров в виде ["id=1", "a[]=2", "b[3][4]=5"]
     */
    public static function flatQuery($query) : array
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        $query = self::buildQuery($query);

        // разбиваем по разделителю параметров "&" и декодируем значения параметры
        //$flatQuery = array_map('urldecode', $flatQuery);
        return array_map(static function($item) {
            $matches = null;
            return preg_match('~^([^=]+=)(.+)$~um', $item, $matches) ?
                $matches[1] . urldecode($matches[2]) : $item;
        }, (array)explode('&', $query));
    }

    /**
     * Восстанавливает парамеры запроса из плоского вида.
     *
     * @param array $flatQuery
     * @return array
     */
    public static function unflatQuery(array $flatQuery) : array
    {
        if ($flatQuery === null || $flatQuery === '' || $flatQuery === []) {
            return [];
        }

        // кодируем параметры
        //$flatQuery = array_map('urlencode', $flatQuery);
        $flatQuery = array_map(static function($item) {
            $matches = null;
            return preg_match('~^([^=]+=)(.+)$~um', $item, $matches) ?
                $matches[1] . urlencode($matches[2]) : $item;
        }, $flatQuery);

        // объединяем и парсим строку параметров
        return static::parseQuery(implode('&', $flatQuery));
    }

    /**
     * Конвертирует домен в ASCII IDN
     *
     * @param string $domain
     * @return string
     */
    public static function idnToAscii(string $domain) : string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }

        return idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * Нормализация пути
     * Удаляет лишние компоненты, заменяет корневой путь на пустой
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path) : string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // сохраняем начальный и конечный слэши
        $startSlash = (mb_strpos($path, '/') === 0);
        $endSlash = (mb_substr($path, - 1, 1) === '/');

        // разбиваем путь на компоненты
        $path = array_values(preg_split('~/+~um', $path, - 1, PREG_SPLIT_NO_EMPTY) ?: []);

        $newPath = [];
        foreach ($path as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }

            if ($p === '..' && $startSlash) {
                array_pop($newPath);
            } else {
                $newPath[] = $p;
            }
        }

        $path = implode('/', $newPath);

        if ($startSlash) {
            $path = '/' . $path;
        }

        if ($endSlash && $path !== '/') {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
     *
     * @param string $dom1 домен
     * @param string $dom2 домен
     * @return bool true, если $dom1 == $dom2 или один из них является поддоменом другого
     */
    public static function isDomainsRelated(string $dom1, string $dom2) : bool
    {
        $dom1 = static::normalizeHost($dom1);
        if (empty($dom1)) {
            throw new InvalidArgumentException('dom1');
        }

        $dom2 = static::normalizeHost($dom2);
        if (empty($dom2)) {
            throw new InvalidArgumentException('dom2');
        }

        if ($dom1 === $dom2) {
            return true;
        }

        $regex = '~.+?\.%s$~uism';

        if (preg_match(sprintf($regex, preg_quote($dom1, '~')), $dom2)) {
            return true;
        }

        if (preg_match(sprintf($regex, preg_quote($dom2, '~')), $dom1)) {
            return true;
        }

        return false;
    }

    /**
     * Нормализация имени домена, удаляет схему и путь
     * - удаляет пробелы
     * - преобразует в нижний регистр, IDN->UTF-8
     * - выделяет из ссылки, удаляя остальные компоненты
     * - удаляет www, ftp поддомены
     * - удаляет несколько разделителей "."
     *
     * @param string $name домен или ссылка
     * @return string
     * @throws InvalidArgumentException
     */
    public static function normalizeHost(string $name) : string
    {
        // убираем все пробельные символы
        $name = preg_replace('~[\s\h\t\v\r\n]+~uim', '', trim($name));
        if ($name === '') {
            return $name;
        }

        // для корректного распознавания строки как домена, парсеру необходимо наличие протокола
        if (! preg_match('~^(\w+:)?//~um', $name)) {
            $name = '//' . $name;
        }

        // парсим имя домена
        $name = trim(parse_url($name, PHP_URL_HOST));
        if (empty($name)) {
            throw new InvalidArgumentException('domain name');
        }

        // преобразуем в нижний регистр и UTF-8
        $name = mb_strtolower(static::idnToUtf8($name));

        // разбиваем домен на компоненты
        $parts = preg_split('~\.+~um', $name, - 1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            throw new InvalidArgumentException('domain name');
        }

        return implode('.', $parts);
    }

    /**
     * Конвертирует в IDN UTF-8
     *
     * @param string $domain
     * @return string
     */
    public static function idnToUtf8(string $domain) : string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }

        return idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * Проверяет является ли домен поддоменом другого
     *
     * @param string $domain
     * @param string $parent
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function isSubdomain(string $domain, string $parent) : bool
    {
        $domain = static::normalizeHost($domain);
        if (empty($domain)) {
            throw new InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new InvalidArgumentException('parent');
        }

        return ! empty(static::getSubdomain($domain, $parent));
    }

    /**
     * Возвращает поддомен домена.
     * Пример:
     * "test.mail.ru", "mail.ru" => "test"
     * "mail.ru", "mail.ru" => ""
     * "test.mail.ru", "yandex.ru" => false
     *
     * @param string $domain домен
     * @param string $parent родительский
     * @return string|null string - имя поддомена, null - если $domain не является поддоменом родительского
     */
    public static function getSubdomain(string $domain, string $parent) : ?string
    {
        $domain = static::normalizeHost($domain);
        if (empty($domain)) {
            throw new InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new InvalidArgumentException('parent');
        }

        $matches = null;
        if (! preg_match(sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent, '~')), $domain, $matches)) {
            return null;
        }

        return $matches[1] ?? '';
    }

    /**
     * Редиректит на правильный URL страницы, если текущий не совпадает.
     *
     * @param array|string $url
     */
    public static function redirectIfNeed($url) : void
    {
        $url = self::to($url);

        if (Yii::$app->request->url !== $url) {
            try {
                Yii::$app->end(0, Yii::$app->response->redirect($url, 301));
            } catch (ExitException $ex) {
                throw new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
            }
        }
    }
}

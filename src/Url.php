<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 15.01.20 12:42:20
 */

declare(strict_types = 1);

namespace dicr\helper;

use InvalidArgumentException;
use function array_diff;
use function array_map;
use function array_pop;
use function array_values;
use function explode;
use function http_build_query;
use function implode;
use function is_array;
use function is_string;
use function ksort;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function sprintf;
use function trim;
use function urldecode;
use function urlencode;
use const PHP_URL_HOST;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Url Helper.
 */
class Url extends \yii\helpers\Url
{
    /**
     * Нормализирует параметры запроса.
     * Конвертирует из строки в массив, сортирует по названию параметров.
     *
     * @param array|string $query
     * @return array
     */
    public static function normalizeQuery($query)
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        if (! is_array($query)) {
            $query = static::parseQuery($query);
        }

        foreach ($query as $k => &$v) {
            if (is_array($v)) {
                $v = self::normalizeQuery($v);
            }
        }

        unset($v);
        ksort($query);

        return $query;
    }

    /**
     * Парсит параметры запроса из строки
     *
     * @param string|array $query
     * @return array
     */
    public static function parseQuery($query)
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        if (is_array($query)) {
            return $query;
        }

        $query = trim($query, '?');
        if ($query === '') {
            return [];
        }

        $parsed = null;
        parse_str($query, $parsed);

        return $parsed;
    }

    /**
     * Фильтрует парамеры запроса, удаляя ключи с пустыми значениями.
     *
     * @param array|string $query
     * @return array
     * @noinspection PhpUnused
     */
    public static function filterQuery($query)
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        if (! is_array($query)) {
            $query = static::parseQuery($query);
        }

        foreach ($query as $k => &$v) {
            if ($v === null || $v === '' || $v === []) {
                unset($query[$k]);
            } elseif (is_array($v)) {
                $v = self::filterQuery($v);
                if (empty($v)) {
                    unset($query[$k]);
                }
            }
        }

        return $query;
    }

    /**
     * Вычитание параметров рекурсивно
     *
     * Из параметров args1 вычитаются параметры args2
     *
     * @param array|string $query1
     * @param array|string $query2
     * @return array $query1 - $query2
     * @noinspection PhpUnused
     */
    public static function diffQuery($query1, $query2)
    {
        if ($query1 === null || $query1 === '' || $query1 === [] || $query2 === null || $query2 === '' || $query2 === []) {
            return $query1;
        }

        return static::unflatQuery(array_diff(static::flatQuery($query1), static::flatQuery($query2)));
    }

    /**
     * Преобразовывает многомерные данные параметров в плоский массив параметров.
     *
     * @param array|string $query парамеры запроса
     * @return string[] одномерный массив параметров в виде ["id=1", "a[]=2", "b[3][4]=5"]
     */
    public static function flatQuery($query)
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        if (! is_string($query)) {
            $query = static::buildQuery($query);
        }

        // разбиваем по разделителю параметров "&"
        $flatQuery = explode('&', $query);

        // декодируем парметры
        //$flatQuery = array_map('urldecode', $flatQuery);
        $flatQuery = array_map(static function ($item) {
            $matches = null;
            return preg_match('~^([^=]+=)(.+)$~um', $item, $matches) ?
                $matches[1] . urldecode($matches[2]) : $item;
        }, $flatQuery);

        return $flatQuery;
    }

    /**
     * Восстанавливает парамеры запроса из плоского вида.
     *
     * @param array $flatQuery
     * @return array
     */
    public static function unflatQuery(array $flatQuery)
    {
        if ($flatQuery === null || $flatQuery === '' || $flatQuery === []) {
            return [];
        }

        // кодируем параметры
        //$flatQuery = array_map('urlencode', $flatQuery);
        $flatQuery = array_map(static function ($item) {
            $matches = null;
            return preg_match('~^([^=]+=)(.+)$~um', $item, $matches) ?
                $matches[1] . urlencode($matches[2]) : $item;
        }, $flatQuery);

        // объединяем и парсим строку параметров
        return static::parseQuery(implode('&', $flatQuery));
    }

    /**
     * Конвертирует параметры запроса в строку
     *
     * @param array $query
     * @return string
     */
    public static function buildQuery(array $query)
    {
        if ($query === null || $query === '' || $query === []) {
            return '';
        }

        return preg_replace(['~%5B~i', '~%5D~i', '~\[\d+]~'], ['[', ']', '[]'], http_build_query($query));
    }

    /**
     * Конверирует домен в ASCII IDN
     *
     * @param string $domain
     * @return string
     */
    public static function idnToAscii(string $domain)
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
    public static function normalizePath(string $path)
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // сохраняем начальный и конечный слэши
        $startSlash = (mb_strpos($path, '/') === 0);
        $endSlash = (mb_substr($path, - 1, 1) === '/');

        // разбиваем путь на компоненты
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
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
     * @return boolean true, если $dom1 == $dom2 или один из них является поддоменом другого
     */
    public static function isDomainsRelated(string $dom1, string $dom2)
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
    public static function normalizeHost(string $name)
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
    public static function idnToUtf8(string $domain)
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
     * @return boolean
     * @throws InvalidArgumentException
     */
    public static function isSubdomain(string $domain, string $parent)
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
     * @return string|false string - имя поддомена,
     *         false - если $domain не является поддоменом родительского
     */
    public static function getSubdomain(string $domain, string $parent)
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
            return false;
        }

        return $matches[1] ?? '';
    }
}

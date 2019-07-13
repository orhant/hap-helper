<?php
namespace dicr\helper;

class Url extends \yii\helpers\Url
{
    /**
     * Парсит параметры запроса из строки
     *
     * @param string $query
     * @return array
     */
    public static function parseQuery(string $query)
    {
        $query = trim($query, '? ');
        if ($query == '') {
            return [];
        }

        $parsed = null;
        parse_str($query, $parsed);

        return $parsed;
    }

    /**
     * Конвертирует параметры запроса в строку
     *
     * @param array $query
     * @return string
     */
    public static function buildQuery(array $query)
    {
        return empty($query) ? '' : http_build_query($query);
    }

    /**
     * Нормализирует параметры запроса.
     * Конвертирует из строки в массив, сортирует по названию параметров.
     *
     * @param array|string $query
     * @return array
     */
    public static function normalizeQuery($query)
    {
        if (is_string($query)) {
            $query = static::parseQuery($query);
        } else {
            $query = (array) $query;
        }

        if (empty($query)) {
            $query = [];
        } else {
            foreach ($query as $k => $v) {
                if (is_array($v)) {
                    $query[$k] = self::normalizeQuery($v);
                }
            }

            ksort($query);
        }

        return $query;
    }

    /**
     * Фильтрует парамеры запроса, удаляя ключи с пустыми значениями.
     *
     * @param array $query
     * @return array
     */
    public static function filterQuery(array $query)
    {
        foreach ($query as $k => $v) {
            if (is_array($v)) {
                $query[$k] = self::filterQuery($v);
                if (empty($query[$k])) {
                    unset($query[$k]);
                } else {
                    $v = trim($v);
                    if ($v === '') {
                        unset($query[$k]);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Преобразовывает многомерные данные параметров в плоский массив параметров.
     *
     * @param array $args парамеры запроса
     * @return string[] одномерный массив параметров в виде ["id=1", "a[]=2", "b[3][4]=5"]
     */
    public static function flatQuery(array $args)
    {
        return preg_split('~\&~uism', static::buildQuery($args), -1, PREG_SPLIT_NO_EMPTY);
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
        if ($domain == '') {
            return '';
        }

        return idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
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
        if ($domain == '') {
            return '';
        }

        return idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
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
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function normalizeHost(string $name)
    {
        // убираем все пробельные символы
        $name = preg_replace('~[\s\h\t\v\r\n]+~uism', '', trim($name));
        if ($name === '') {
            return $name;
        }

        // для корректного распознавания строки как домена, парсеру необходимо наличие протокола
        if (! preg_match('~^(\w+\:)?\/\/~uism', $name)) {
            $name = '//' . $name;
        }

        // парсим имя домена
        $name = trim(parse_url($name, PHP_URL_HOST));
        if (empty($name)) {
            throw new \InvalidArgumentException('domain name');
        }

        // преобразуем в нижний регистр и UTF-8
        $name = mb_strtolower(static::idnToUtf8($name));

        // разбиваем домен на компоненты
        $parts = preg_split('~\.+~uism', $name, - 1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            throw new \InvalidArgumentException('domain name');
        }

        return implode('.', $parts);
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
        if ($path == '') {
            return '';
        }

        // сохраняем начальный и конечный слэши
        $startSlash = (mb_substr($path, 0, 1) === '/');
        $endSlash = (mb_substr($path, - 1, 1) === '/');

        // разбиваем путь на компоненты
        $path = array_values(preg_split('~\/+~uism', $path, - 1, PREG_SPLIT_NO_EMPTY) ?: []);

        $newPath = [];
        foreach ($path as $p) {
            if ($p == '' || $p == '.') {
                continue;
            }

            if ($p == '..' && $startSlash) {
                array_pop($newPath);
            } else {
                $newPath[] = $p;
            }
        }

        $path = implode('/', $newPath);

        if ($startSlash) {
            $path = '/' . $path;
        }

        if ($endSlash && $path != '/') {
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
            throw new \InvalidArgumentException('dom1');
        }

        $dom2 = static::normalizeHost($dom2);
        if (empty($dom2)) {
            throw new \InvalidArgumentException('dom2');
        }

        if ($dom1 == $dom2) {
            return true;
        }

        $regex = '~.+?\.%s$~uism';

        if (preg_match(sprintf($regex, preg_quote($dom1)), $dom2)) {
            return true;
        }

        if (preg_match(sprintf($regex, preg_quote($dom2)), $dom1)) {
            return true;
        }

        return false;
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
            throw new \InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new \InvalidArgumentException('parent');
        }

        $matches = null;
        if (! preg_match(sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent)), $domain, $matches)) {
            return false;
        }

        return $matches[1] ?? '';
    }

    /**
     * Проверяет является ли домен поддоменом другого
     *
     * @param string $domain
     * @param string $parent
     * @throws \InvalidArgumentException
     * @return boolean
     */
    public static function isSubdomain(string $domain, string $parent)
    {
        $domain = static::normalizeHost($domain);
        if (empty($domain)) {
            throw new \InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new \InvalidArgumentException('parent');
        }

        return !empty(static::getSubdomain($domain, $parent));
    }
}
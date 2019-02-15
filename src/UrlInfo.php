<?php
namespace dicr\helper;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Ссылка
 *
 * @property string $scheme
 * @property string $user
 * @property string $pass
 * @property string $host
 * @property int $port
 * @property string $path
 * @property array $query
 * @property string $fragment
 *
 * @property-read string $hostInfo user:pass@host:port
 * @property-read string $requestUri строка запроса (путь?параметры#фрагмент)
 * @property-read bool $isAbsolute признак абсолютной ссылки
 * @property-read array $attributes
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180621
 */
class UrlInfo extends BaseObject
{
    /** @var array стандартные сервисы и порты */
    const SERVICES = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ssh' => 22,
        'smb' => 445
    ];

    /** @var array специальные схемы, которым не обязателен хост */
    const SCHEME_NONHTTP = [
        'javascript', 'mailto', 'tel'
    ];

    /** @var string схема */
    private $_scheme = '';

    /** @var string логин */
    private $_user = '';

    /** @var string пароль */
    private $_pass = '';

    /** @var string сервер (домен в utf8) */
    private $_host = '';

    /** @var int порт */
    private $_port = 0;

    /** @var string путь */
    private $_path = '';

    /** @var array параметры key => $val */
    private $_query = [];

    /** @var string фрагмент */
    private $_fragment = '';

    /**
     * Конструктор
     *
     * @param string|array $url
     * @throws \InvalidArgumentException
     */
    public function __construct($url = [])
    {
        if (is_array($url)) {
            parent::__construct($url);
        } elseif (is_string($url)) {
            $config = parse_url($url);
            if ($config === false) {
                throw new \InvalidArgumentException('url: ' . $url);
            }
            parent::__construct($config);
        } else {
            throw new \InvalidArgumentException('url');
        }
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\BaseObject::init()
     */
    public function init()
    {

        parent::init();

        if ($this->host == '' && $this->_scheme != '' && !in_array($this->_scheme, self::SCHEME_NONHTTP)) {
            throw new InvalidConfigException('host');
        }

        // если указан порт, то должен быть указан хост
        if (! empty($this->_port)) {
            if ($this->_host == '') {
                throw new InvalidConfigException('host');
            }
        }
    }

    /**
     * Возвращает аттрибуты модели
     *
     * @return array
     */
    public function getAttributes()
    {
        return [
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->pass,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment
        ];
    }

    /**
     * Возвращает схему
     *
     * @return string
     */
    public function getScheme()
    {
        $scheme = $this->_scheme;

        // угадываем схему по номеру порта
        if (empty($scheme) && !empty($this->_port)) {
            $scheme = self::getSchemeByPort($this->_port);
        }

        return $scheme;
    }

    /**
     * Устанавливает схему
     *
     * @param string $scheme
     * @return self
     */
    public function setScheme(string $scheme)
    {
        $this->_scheme = strtolower(trim($scheme));
        return $this;
    }

    /**
     * Возвращает логин
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Устанавливает пользователя
     *
     * @param string $user
     * @return self
     */
    public function setUser(string $user)
    {
        $this->_user = trim($user);
        return $this;
    }

    /**
     * Возвращает пароль
     *
     * @return string
     */
    public function getPass()
    {
        return $this->_pass;
    }

    /**
     * Устанавливает пароль
     *
     * @param string $pass
     * @return self
     */
    public function setPass(string $pass)
    {
        $this->_pass = $pass;
        return $this;
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
     * Возвращает хост
     *
     * @param bool $toAscii преобразовать из UTF-8 в ASCII IDN
     * @return string хост
     */
    public function getHost(bool $toAscii=false)
    {
        return $toAscii ? static::idnToAscii($this->_host) : $this->_host;
    }

    /**
     * Устанавливает хост
     *
     * @param string $host
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setHost(string $host)
    {
        $this->_host = static::normalizeHost($host);
        return $this;
    }

    /**
     * Возвращает порт
     *
     * @return int порт
     */
    public function getPort()
    {
        $port = $this->_port;

        if (empty($port) && !empty($this->_scheme)) {
            $port = self::getPortByScheme($this->_scheme);
        }

        return $port;
    }

    /**
     * Нормализация порта.
     * Проверяет на допустимый диапазон 0 .. 65535.
     *
     * @param int $port
     * @throws \InvalidArgumentException
     * @return int порт
     */
    public static function normalizePort(int $port)
    {
        if ($port < 0 || $port > 65535) {
            throw new \InvalidArgumentException('port');
        }

        return $port;
    }

    /**
     * Устанавливает порт
     *
     * @param int $port
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setPort(int $port)
    {
        $this->_port = static::normalizePort($port);
        return $this;
    }

    /**
     * Возвращает путь
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
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
     * Устанавливает путь
     *
     * @param string $path
     * @return self
     */
    public function setPath(string $path)
    {
        $this->_path = static::normalizePath($path);
        return $this;
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
     * Возвращает параметры
     *
     * @param bool $toString преобразовать в строку
     * @return array|string параметры запроса
     */
    public function getQuery(bool $toString = false)
    {
        return $toString ? static::buildQuery($this->_query) : $this->_query;
    }

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
            ksort($query);
        }

        return $query;
    }

    /**
     * Устанавливает параметры запроса
     *
     * @param array|string $query
     * @return self
     */
    public function setQuery($query)
    {
        $this->_query = static::normalizeQuery($query);
        return $this;
    }

    /**
     * Возвращает фрагмент
     *
     * @return string|null фрагмент
     */
    public function getFragment()
    {
        return $this->_fragment;
    }

    /**
     * Устанавливает фрагмент
     *
     * @param string $fragment
     * @return self
     */
    public function setFragment(string $fragment)
    {
        $this->_fragment = trim($fragment, ' #');
        return $this;
    }

    /**
     * Возвращает hostinfo: user:pass@host:port часть URL
     *
     * @param bool $toAscii преобразовать домен из UTF-8 в ASCII
     * @return string
     */
    public function getHostInfo(bool $toAscii=false)
    {
        $hostInfo = '';

        if ($this->user != '') {
            $hostInfo .= $this->user;
            if ($this->pass != '') {
                $hostInfo .= ':' . $this->pass;
            }
            $hostInfo .= '@';
        }

        if ($this->host != '') {
            $hostInfo .= $this->getHost($toAscii);
        }


        if (! empty($this->port) && ($this->scheme != 'http' || $this->port != 80) &&
            ($this->scheme != 'https' || $this->port != 443)) {
            $hostInfo .= ':' . $this->port;
        }

        if (!empty($this->_port)) {
            // исключаем добавление стандартных портов
            $port = static::getPortByScheme($this->_scheme);
            if ($port != $this->_port) {
                $hostInfo .= ':' . $this->port;
            }
        }

        return $hostInfo;
    }

    /**
     * Возвращает строку запроса
     *
     * @return string|null путь?параметры
     */
    public function getRequestUri()
    {
        $uri = '';
        if ($this->path != '/') {
            $uri .= $this->path;
        }

        // добавляем параметры
        if (! empty($this->query)) {
            $uri .= '?' . $this->getQuery(true);
        }

        return $uri;
    }

    /**
     * Создает экземпляр из строки
     *
     * @param string $url адрес URL
     * @return \dicr\helper\UrlInfo|false
     */
    public static function fromString(string $url)
    {
        try {
            return new static($url);
        } catch (\Exception $ex) {
            // none
        }
        return false;
    }

    /**
     * Возвращает строковое представление
     *
     * @param bool $toAscii преобразовать домен из UTF в ASCII IDN
     * @return string полный url
     */
    public function toString(bool $toAscii = false)
    {
        $url = '';

        if ($this->scheme != '') {
            $url .= $this->scheme . ':';
        }

        if ($this->host != '') {
            if ($this->scheme != '' && ! in_array($this->scheme, ['tel','mailto'])) {
                $url .= '//';
            }
            $url .= $this->getHostInfo($toAscii);
        }

        $url .= $this->getRequestUri();

        if ($this->fragment != '') {
            $url .= '#' . $this->fragment;
        }

        return $url;
    }

    /**
     * Возвращает строковое представление.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Возвращает признак абсолютной ссылки
     *
     * @return boolean
     */
    public function getIsAbsolute()
    {
        return $this->scheme !== '' || $this->host != '';
    }

    /**
     * Возвращает абсолютный URL по базовому
     *
     * @param self $base базовый абсолютный URL
     * @return self полный URL
     */
    public function toAbsolute(self $base)
    {
        if (empty($base)) {
            throw new \InvalidArgumentException('base');
        }

        /** @var self $full */
        $full = clone $this;

        if (empty($full->scheme)) {
            $full->scheme = $base->scheme;

            if (empty($full->hostInfo)) {
                $full->user = $base->user;
                $full->pass = $base->pass;
                $full->host = $base->host;
                $full->port = $base->port;

                if (empty($full->path)) {
                    $full->path = $base->path;

                    if (empty($full->query)) {
                        $full->query = $base->query;

                        if (empty($full->fragment)) {
                            $full->fragment = $base->fragment;
                        }
                    }
                } else
                    if (mb_substr($full->path, 0, 1) != '/') {
                        $basePath = $base->path;
                        if (mb_substr($basePath, - 1, 1) !== '/') {
                            $basePath = preg_split('~\/+~uism', $basePath, - 1, PREG_SPLIT_NO_EMPTY);
                            array_pop($basePath);
                            $basePath = '/' . implode('/', $basePath);
                        }

                        $full->path = static::normalizePath($basePath . '/' . $full->path);
                    }
            }
        }

        return $full;
    }

    //////////////// Домены

    /**
     * Возвращает поддомен домена.
     * Пример:
     * "test.mail.ru", "mail.ru" => "test"
     * "mail.ru", "mail.ru" => ""
     * "test.mail.ru", "yandex.ru" => false
     *
     * @param string $parent родительский
     * @return string|false string - имя поддомена,
     *         false - если $domain не является поддоменом родительского
     */
    public function getSubdomain(string $parent)
    {
        $parent = trim($parent);

        if (empty($parent)) {
            return false;
        }

        $parent = mb_strtolower(idn_to_utf8($parent));

        if ($this->host == '') {
            return false;
        }

        $regex = sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent));
        $matches = null;
        if (! preg_match($regex, $this->host, $matches)) {
            return false;
        }

        return $matches[1] ?? '';
    }

    /**
     * Проверяет является ли поддоменом $parent
     *
     * @param string $parent родительский домен
     * @return boolean true если $domain != $parent и являестся поддоменом $parent
     */
    public function isSubdomain(string $parent)
    {
        $parent = trim($parent);
        if (empty($parent)) {
            throw new \InvalidArgumentException('empty parent');
        }

        if ($this->host == '') {
            return false;
        }

        return ! empty($this->getSubdomain($parent));
    }

    /**
     * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
     *
     * @param string $domain сравниваемый домен
     * @return boolean true, если $domain1 == $domain2 или один из них является поддоменом другого
     */
    public function isDomainRelated(string $domain)
    {
        $domain = trim($domain);
        if (empty($domain)) {
            throw new \InvalidArgumentException('empty domain');
        }

        $domain = mb_strtolower(idn_to_utf8($domain));

        if ($this->host == '') {
            return false;
        }

        if ($this->host == $domain) {
            return true;
        }

        $regex = '~.+?\.%s$~uism';

        if (preg_match(sprintf($regex, preg_quote($domain)), $this->host)) {
            return true;
        }

        if (preg_match(sprintf($regex, preg_quote($this->host)), $domain)) {
            return true;
        }

        return false;
    }

    /**
     * Проверяет является ли сравниваемая ссылка
     * на том же сайто что и данная.
     * Ссылка на том же сайте, если она относительная данной или
     * у нее одинаковые схемы, хосты, либо хост является поддоменом данной.
     *
     * @param self $other базовый url
     * @param array $options опции тестирования
     *        - subdoms - считать поддомены тем же сайтом = false
     *        - subpath - считать только ссылки в заданом пути (на уровень ниже) = false
     * @return bool true если тот же сайт
     */
    public function isSameSite(UrlInfo $other, array $options = [])
    {
        if (empty($other)) {
            throw new \InvalidArgumentException('other');
        }

        $subdoms = ! empty($options['subdoms']); // разрешать поддомены
        $subpath = ! empty($options['subpath']); // разрешать только подкаталоги в пути

        // достраиваем ссылки друг по другу
        $u1 = $this->toAbsolute($other);
        $u2 = $other->toAbsolute($this);

        // сравниваем схемы
        if ($u1->scheme != $u2->scheme) {
            return false;
        }

        // сравниваем аккаунты
        if ($u1->user != $u2->user || $u1->pass != $u2->pass) {
            return false;
        }

        // сравниваем хосты
        if ($u1->host != $u2->host && (! $subdoms || ! $u2->isSubdomain($u1->host))) {
            return false;
        }

        // сравниваем порты
        if ($u1->port != $u2->port) {
            return false;
        }

        // проверяем путь
        if ($subpath && $u1->path != '' && ($u2->path == '' || mb_strpos($u2->path, $u1->path) !== 0)) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет совпадение маски правила robots.txt с данным URL
     *
     * @param string $mask маска может содержать специальные символы '*' и '$' как в robots.txt
     * @throws \LogicException url не абсолютный
     * @return boolean true если совпадает
     */
    public function matchRobotsMask(string $mask)
    {
        $mask = trim($mask);
        if ($mask === '') {
            return false;
        }

        $regex = '~^' . str_replace(['\*','\$'], ['.*','$'], preg_quote($mask, '~')) . '~us';
        return (bool) preg_match($regex, $this->getRequestUri());
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
        return idn_to_ascii($domain);
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
        return idn_to_utf8($domain);
    }

    /**
     * Возвращает схему по номеру порта
     *
     * @param int $port
     * @return string
     */
    public static function getSchemeByPort(int $port) {
        foreach (self::SERVICES as $scheme => $p) {
            if ($p == $port) {
                return $scheme;
            }
        }
        return '';
    }

    /**
     * Возвращает номер порта по схеме сервиса
     *
     * @param string $scheme
     * @return int
     */
    public function getPortByScheme(string $scheme) {
        foreach (self::SERVICES as $sch => $port) {
            if ($sch == $scheme) {
                return $port;
            }
        }
        return 0;
    }
}

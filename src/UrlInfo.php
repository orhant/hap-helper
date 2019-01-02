<?php 
namespace dicr\helper;

use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Ссылка
 * 
 * @property-read string $scheme
 * @property-read string $user
 * @property-read string $pass
 * @property-read string $host
 * @property-read int $port
 * @property-read string $path
 * @property-read array $query
 * @property-read string $fragment
 * 
 * @property-read string $hostInfo user:pass@host:port
 * @property-read string $requestUri строка запроса (путь?параметры#фрагмент)
 * @property-read bool $isAbsolute признак абсолютной ссылки
 * @property-read array $attributes
 * 
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180621
 */
class UrlInfo extends BaseObject {
	
	/** @var string схема */
	private $scheme = '';
	
	/** @var string логин */
	private $user = '';
	
	/** @var string пароль */
	private $pass = '';
	
	/** @var string сервер (домен в utf8) */
	private $host = '';
	
	/** @var int порт */
	private $port = 0;
	
	/** @var string путь */
	private $path = '';
	
	/** @var array параметры key => $val */
	private $query = [];
	
	/** @var string фрагмент */
	private $fragment = '';
	
	/**
	 * Конструктор
	 * 
	 * @param string|array $url
	 */
	public function __construct($url) {
		if (is_string($url)) {
			$url = parse_url($url);
		}
		parent::__construct($url);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \yii\base\BaseObject::init()
	 */
	public function init() {
		parent::init();
		
		// хоста у ссылок mailto, tel нет (только путь)
		if ($this->scheme != '') {
			if ($this->host == '' && !in_array($this->scheme, ['mailto', 'tel'])) {
				throw new InvalidConfigException('host');
			}
		}
		
		// если указан порт, то должен быть указан хост
		if (!empty($this->port)) {
			if ($this->host == '') {
				throw new InvalidConfigException('host');
			}
		}
	}
	
	
	/**
	 * Возвращает схему
	 * 
	 * @return string
	 */
	public function getScheme() {
		return $this->scheme;
	}
	
	/**
	 * Устанавливает схему
	 * 
	 * @param string $scheme
	 * @return self
	 */
	public function setScheme(string $scheme) {
		$this->scheme = strtolower(trim($scheme));
		return $this;
	}
	
	/**
	 * Возвращает логин
	 * 
	 * @return string
	 */
	public function getUser() {
		return $this->user;
	}
	
	/**
	 * Устанавливает пользователя
	 * 
	 * @param string $user
	 * @return self
	 */
	public function setUset(string $user) {
		$this->user = trim($user);
		return $this;
	}

	/**
	 * Возвращает пароль
	 * 
	 * @return string
	 */
	public function getPass() {
		return $this->pass;
	}
	
	/**
	 * Устанавливает пароль
	 * 
	 * @param string $pass
	 * @return self
	 */
	public function setPass(string $pass) {
		$this->pass = $pass;
		return $this;
	}
	
	/**
	 * Конверирует домен в ASCII IDN
	 * 
	 * @param string $domain
	 * @return string
	 */
	public static function idnToAscii(string $domain) {
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
	public static function idnToUtf8(string $domain) {
		$domain = trim($domain);
		if ($domain == '') {
			return '';
		}
		return idn_to_utf8($domain);
	}
	
	/**
	 * Возвращает хост
	 * 
	 * @param bool $toAscii преобразовать из UTF8 в IDN ASCII
	 * @return string хост
	 */
	public function getHost(bool $toAscii=false) {
		return $toAscii ? static::idnToAscii($this->host) : $this->host;
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
	public static function normalizeHost(string $name) {
		// убираем все пробельные символы
		$name = preg_replace('~[\s\h\t\v\r\n]+~uism', '', trim($name));
		if ($name === '') {
			return $name;
		}
		
		// для корректного распознавания строки как домена, парсеру необходимо наличие протокола
		if (!preg_match('~^(\w+\:)?\/\/~uism', $name)) {
			$name = '//'.$name;
		}
		
		// парсим имя домена
		$name = trim(parse_url($name, PHP_URL_HOST));
		if (empty($name)) {
			throw new \InvalidArgumentException('domain name');
		}
		
		// преобразуем в нижний регистр и UTF-8
		$name = mb_strtolower(static::idnToUtf8($name));
		
		// разбиваем домен на компоненты
		$parts = preg_split('~\.+~uism', $name, -1, PREG_SPLIT_NO_EMPTY);
		if (empty($parts)) {
			throw new \InvalidArgumentException('domain name');
		}
		
		return implode('.', $parts);
	}
	
	/**
	 * Устанавливает хост 
	 * 
	 * @param string $host
	 * @throws \InvalidArgumentException
	 * @return self
	 */
	public function setHost(string $host) {
		$this->host = static::normalizeHost($host);
		return $this;
	}
	
	/**
	 * Возвращает порт
	 * 
	 * @param bool $guess если порт пустой, то определить по схеме
	 * @return int порт
	 */
	public function getPort(bool $guess=false) {
		if (!empty($this->port)) {
			return $this->port;
		}
		
		if ($guess) {
			if ($this->scheme == 'http') {
				return 80;
			}
			
			if ($this->scheme === 'https') {
				return 443;
			}
		}
		
		return 0;
	}

	/**
	 * Нормализация порта.
	 * Проверяет на допустимый диапазон 0 .. 65535.
	 * 
	 * @param int $port
	 * @throws \InvalidArgumentException
	 * @return int порт
	 */
	public static function normalizePort(int $port) {
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
	public function setPort(int $port) {
		$this->port = static::normalizePort($port);
		return $this;
	}
	
	/**
	 * Возвращает путь
	 * 
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * Нормализация пути
	 * Удаляет лишние компоненты, заменяет корневой путь на пустой
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function normalizePath(string $path) {
		$path = trim($path);
		if ($path == '') {
			return '';
		}
		
		// сохраняем начальный и конечный слэши
		$startSlash = (mb_substr($path, 0, 1) === '/');
		$endSlash = (mb_substr($path, -1, 1) === '/');

		// разбиваем путь на компоненты
		$path = array_values(preg_split('~\/+~uism', $path, -1, PREG_SPLIT_NO_EMPTY));
		if (empty($path)) {
			return '';
		}
		
		// если путь начинался с корня, то удаляем вначале все переходы на верхний путь
		if ($startSlash) {
			while (reset($path) == '..') {
				array_shift($path);
			}
		}
		
		foreach ($path as $i => $p) {
			// удаляем пустые компоненты "." в середине пути
			if ($p == '.') {
				unset($path[$i]);
			}
		}
		
		if (empty($path)) {
			return '';
		}
		
		$path = implode('/', $path);
		if ($startSlash) {
			$path = '/'.$path;
		}
		
		if ($endSlash) {
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
	public function setPath(string $path) {
		$this->path = static::normalizePath($path);
		return $this;
	}
	
	/**
	 * Конвертирует параметры запроса в строку
	 * 
	 * @param array $query
	 * @return string
	 */
	public static function buildQuery(array $query) {
		return empty($query) ? '' : http_build_query($query);
	}
	
	/**
	 * Возвращает параметры 
	 * 
	 * @param bool $toString преобразовать в строку
	 * @return array параметры запроса
	 */
	public function getQuery(bool $toString=false) {
		return $toString ? static::buildQuery($this->query) : $this->query; 
	}

	/**
	 * Парсит параметры запроса из строки
	 *
	 * @param string $query
	 * @return array
	 */
	public static function parseQuery(string $query) {
		$query = trim($query, '? ');
		if ($query == '') {
			return [];
		}
		parse_str($query, $query);
		return $query;
	}
	
	/**
	 * Нормализирует параметры запроса.
	 * Конвертирует из строки в массив, сортирует по названию параметров.
	 * 
	 * @param array|string $query
	 * @return array
	 */
	public static function normalizeQuery($query) {
		if (is_string($query)) {
			$query = static::parseQuery($query);
		} else {
			$query = (array)$query;
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
	public function setQuery($query) {
		$this->query = static::normalizeQuery($query);
		return $this;
	}

	/**
	 * Возвращает фрагмент
	 * 
	 * @return string|null фрагмент
	 */
	public function getFragment() {
		return $this->fragment;
	}
	
	/**
	 * Устанавливает фрагмент
	 * 
	 * @param string $fragment
	 * @return self
	 */
	public function setFragment(string $fragment) {
		$this->fragment = trim($fragment, ' #');
		return $this;
	}
	
	/**
	 * Парсит и создает из строки
	 *
	 * @param string $url
	 * @return static|false
	 */
	public static function fromSting(string $url) {
		$url = trim($url);
		$config = parse_url($url);
		if ($config === false) throw new Exception('invalid url: '.$url);
		return new static($config);
	}
	
	/**
	 * Возвращает hostinfo:  user:pass@host:port часть URL
	 *
	 * @param bool $toIDN преобразовать домен в IDN
	 * @return string
	 */
	public function getHostInfo(bool $toIDN=false) {
		
		$hostInfo = '';
		
		if ($this->user != '') {
			$hostInfo .= $this->user;
			if ($this->pass != '') {
				$hostInfo .= ':'.$this->pass;
			}
			$hostInfo .= '@';
		}
		
		if ($this->host != '') {
			$hostInfo .= $this->getHost($toIDN);
		}
		
		if (!empty($this->port) &&
			($this->scheme != 'http' || $this->port != 80) &&
			($this->scheme != 'https' || $this->port != 443)
		) {
			$hostInfo .= ':' . $this->port;
		}
				
		return $hostInfo;
	}
	
	/**
	 * Возвращает строку запроса
	 * 
	 * @return string|null путь?параметры
	 */
	public function getRequestUri() {
		$uri = $this->path;
		
		// добавляем параметры
		if (!empty($this->query)) {
			$uri .= '?'.$this->getQuery(true);
		}
		
		return $uri;
	}
	
	/**
	 * Возвращает строковое представление
	 *
	 * @param bool toIDN преобразовать домен в IDN
	 * @return string полный url
	 */
	public function toString(bool $toIDN=false) {
		$url = '';
		
		if ($this->scheme != '') {
			$url .= $this->scheme . ':';
		}
		
		if ($this->host != '') {
			if ($this->scheme != '' && !in_array($this->scheme, ['tel', 'mailto'])) {
				$url .= '//';
			}
			$url .= $this->getHostInfo($toIDN);
		}
		
		$url .= $this->getRequestUri();
		
		if ($this->fragment != '') {
			$url .= '#'.$this->fragment;
		}
		
		return $url;
	}
	
	/**
	 * Возвращает строковое представление.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
	
	/**
	 * Возвращает аттрибуты модели
	 * 
	 * @return array[]
	 */
	public function getAttributes() {
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
	 * Возвращает признак абсолютной ссылки
	 *
	 * @return boolean
	 */
	public function getIsAbsolute() {
		return $this->scheme !== '' || $this->host != '';
	}
	
	/**
	 * Возвращает абсолютный URL по базовому
	 *
	 * @param self базовый абсолютный URL
	 * @return self полный URL
	 */
	public function toAbsolute(self $base) {
		if (empty($base)) {
			throw new \InvalidArgumentException('base');
		}
		
		if (!$base->isAbsolute) {
			throw new \InvalidArgumentException('base not absolute');
		}
		
		/*
		 $full = clone $base;
		 
		 if ($this->getHostInfo() != '') {
		 $full->user = $this->user;
		 $full->pass = $this->pass;
		 $full->host = $this->host;
		 $full->port = $this->port;
		 $full->path = $this->path;
		 $full->query = $this->query;
		 $full->fragment = $this->fragment;
		 } else if ($this->path != '') {
		 if (mb_substr($this->path, 0, 1) == '/') {	// абсолютный путь
		 $full->path = $this->path;
		 } else if (mb_substr($full->path, -1, 1) == '/') {	// относительный путь в директории
		 $full->path = '/'.implode('/', $full->path).'/'.$this->path;
		 } else { // относительный путь с заменой текущего файла
		 $full->path = preg_split('~\/+~uism', $full->path, -1, PREG_SPLIT_NO_EMPTY);
		 array_pop($full->path);
		 $full->path = '/'.implode('/', $full->path).'/'.$this->path;
		 }
		 $full->query = $this->query;
		 $full->fragment = $this->fragment;
		 } else if (!empty($this->query)) {
		 $full->query = $this->query;
		 $full->fragment = $this->fragment;
		 } else if ($this->fragment != '') {
		 $full->fragment = $this->fragment;
		 }
		 */
		
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
				} else if (mb_substr($full->path, 0, 1) != '/') {
					$baseSlash = (mb_substr($base->path, -1, 1) == '/');
					$basePath = preg_split('~\/+~uism', $base->path, -1, PREG_SPLIT_NO_EMPTY);
					if (!$baseSlash) {
						array_pop($basePath);
					}
					
					$fullSlash = (mb_substr($full->path, -1, 1) == '/');
					$fullPath = preg_split('~\/~uism', $full->path, -1, PREG_SPLIT_NO_EMPTY);
					
					while (reset($fullPath) == '..') {
						array_pop($basePath);
						array_shift($fullPath);
					}
					
					$full->path = '';
					
					if (!empty($basePath)) {
						$full->path .= '/'.implode('/', $basePath);
					}
					
					if (!empty($fullPath)) {
						$full->path .= '/'.implode('/', $fullPath);
					}
					
					if ($fullSlash) {
						$full->path .= '/';
					}
					
					if ($full->path == '/') {
						$full->path = '';
					}
				}
			}
		}
		
		return $full;
	}
	
	//////////////// Домены
	
	/**
	 * Возвращает поддомен домена.
	 *
	 * Пример:
	 * 	"test.mail.ru", "mail.ru" => "test"
	 * 	"mail.ru", "mail.ru" => ""
	 * 	"test.mail.ru", "yandex.ru" => false
	 *
	 * @param string $parent родительский
	 * @return string|false
	 * 	string - имя поддомена,
	 * 	false - если $domain не является поддоменом родительского
	 */
	public function getSubdomain(string $parent) {
		$parent = trim($parent);
		if (empty($parent)) {
			throw new \InvalidArgumentException('empty parent');
		}
		
		$parent = mb_strtolower(idn_to_utf8($parent));

		if ($this->host == '') {
			return false;
		}
		
		$regex = sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent));
		$matches = null;
		if (!preg_match($regex, $this->host, $matches)) {
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
	public function isSubdomain(string $parent) {
		$parent = trim($parent);
		if (empty($parent)) {
			throw new \InvalidArgumentException('empty parent');
		}

		if ($this->host == '') {
			return false;
		}
		
		return !empty($this->getSubdomain($parent));
	}
	
	/**
	 * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
	 *
	 * @param string $domain сравниваемый домен
	 * @return boolean true, если $domain1 == $domain2 или один из них является поддоменом другого
	 */
	public function isDomainRelated(string $domain) {
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
	 * Проверяет является ли данная ссылка на том же сайте что и базовая $base.
	 * Причем текущая ссылка может быть относительной.
	 *
	 * @param self $base базовый url
	 * @param array $options опции тестирования
	 * - subdoms - считать поддомены тем же сайтом = false
	 * - subpath - считать только ссылки в заданом пути (на уровень ниже) = false
	 * @return bool true если тот же сайт
	 */
	public function isSameSite(self $base, array $options=[]) {
		if (empty($base)) {
			throw new \InvalidArgumentException('base');
		}
		
		$subdoms = !empty($options['subdoms']);
		$subpath = !empty($options['subpath']);

		// сраниваем схемы, потому что у mailto: схема указана без указания хоста
		if ($this->scheme != '' && $base->scheme != '' && $this->scheme != $base->scheme) {
			return false;
		}
		
		// если у обоих указан домен тогда сравниваем hostinfo
		if ($this->host != '' && $base->host != '') {
			
			// сравниваем домены с учетом поддомена
			if ($this->host !== $base->host && (!$subdoms || !$this->isDomainRelated($base->host))) {
				return false;
			}
			
			// логин, пароль и порт должны либо у обоих отсутствовать либо быть одинаковые
			if ($this->user !== $base->user || $this->pass !== $base->pass || $this->port !== $base->port) {
				return false;
			}
		}
		
		// проверяем путь
		if ($subpath && $base->path != '' && ($this->path == '' || mb_strpos($this->path, $base->path) !== 0)) {
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
	public function matchRobotsMask(string $mask) {
		$mask = trim($mask);
		if ($mask === '') {
			return false;
		}
		
		$regex = '~^'.str_replace(['\*', '\$'], ['.*', '$'], preg_quote($mask, '~')).'~us';
		return preg_match($regex, $this->getRequestUri());
	}
}

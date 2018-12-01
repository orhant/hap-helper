<?php 
namespace dicr\helper;

use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Утилиты URL
 *  
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180301
 * @deprecated use \dicr\model\UrlInfo
 */
class UrlHelper {
	
	/**
	 * Нормализует путь. Удаляет лишние DIRECTORY_SEPARATOR.
	 *
	 * @param string $path путь для нормализации
	 * @return string|null нормализованный путь
	 */
	public static function normalizePath(string $path=null) {
		if (!isset($path) || $path === '') return null;
		$path = preg_split('~[\/]+~uism', $path, -1, PREG_SPLIT_NO_EMPTY);
		return !empty($path) ? implode(DIRECTORY_SEPARATOR, $path) : null;
	}

	/**
	 * Нормализует строку параметров, удаляя пустые параметры и сортируя по ключу.
	 * 
	 * @param array|string $params строка или массив параметров
	 * @param array $options опции
	 * - deleteEmptyStrings - удалять пустые строки (&var1&var2=)
	 *   
	 * @return array нормализованные параметры key => val
	 */
	public static function normalizeParams($params=[], array $options=[]) {
		$deleteEmptyStrings = ArrayHelper::getValue($options, 'deleteEmptyString', false);
		
		// преобразуем к массиву
		if (is_string($params)) {
			$params = trim($params);
			if ($params === '') $params = [];
			else parse_str($params, $params);
		}
		
		if (!is_array($params)) {
			throw new Exception('invalid params');
		}
		
		// обходим массив, удаляя пустые
		foreach ($params as $key => $val) {
			if (is_array($val)) {
				$val = self::normalizeParams($val);
				if (empty($val)) unset($params[$key]);
				else $params[$key] = $val;
			} else if (is_scalar($val)) {
				if ($deleteEmptyStrings) {
					$val = trim($val);
					if ($val === '') unset($params[$key]);
					else $params[$key] = $val;
				}
			} else {
				// неизвестный формат параметра
				unset($params[$key]);
			}
		}
		
		ksort($params);
		return $params;
	}
	
	/**
	 * Нормализация имени домена
	 * 
	 * @param string $domain название домена
	 * @return NULL|string
	 */
	public static function normalizeDomain(string $domain) {
		// убираем все пробельные символы
		$name = preg_replace('~[\s\h\t\v\r\n]+~uism', '', $domain);
		if ($name === '') return null;
		
		// преобразуем в нижний регистр
		$name = mb_strtolower($name);
		
		// для корректного распознавания строки как домена, парсеру необходимо наличие протокола
		if (!preg_match('~^(\w+\:)?\/\/~uism', $name)) $name = '//'.$name;
		
		// парсим имя домена
		$name = trim(parse_url($name, PHP_URL_HOST));
		if ($name === '') return null;
		
		// разбиваем домен на компоненты
		$parts = preg_split('~\.+~uism', $name, -1, PREG_SPLIT_NO_EMPTY);
		
		// удаляем первый www, ftp
		while (!empty($parts) && in_array($parts[0], ['www', 'ftp'])) array_shift($parts);
		if (count($parts) < 2) return null;

		$name = implode('.', $parts);
		
		// преобразуем в UTF-8
		return idn_to_utf8(implode('.', $parts));
	}

	/**
	 * Пересобирает URL, декодируя домен из ASCII IDN.
	 *
	 * @param string $url
	 */
	public static function normalizeUrl(string $url) {
		$url = trim($url);
		if (empty($url)) return null;
		$parts = parse_url($url);
		if (!empty($parts['host'])) $parts['host'] = static::normalizeDomain($parts['host']);
		return static::buildUrl($parts);
	}

	/**
	 * Возвращает поддомен домена.
	 * 
	 * Пример:
	 * 	getSubdomain("test.mail.ru", "mail.ru") => "test"
	 * 	getSubdomain("mail.ru", "mail.ru") => ""
	 * 	getDubdomain("test.mail.ru", "yandex.ru") => false
	 * 
	 * @param string $domain домен
	 * @param string $parent родительский
	 * @return string|false 
	 * 	string - имя поддомена, 
	 * 	false - если $domain не является поддоменом родительского   
	 */
	public static function getSubdomain(string $domain, string $parent) {
		$domain = trim($domain);
		if (empty($domain)) throw new \InvalidArgumentException('empty domain');
		$parent = trim($parent);
		if (empty($parent)) throw new \InvalidArgumentException('empty parent');
		$regex = sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent));
		$matches = null;
		if (!preg_match($regex, $domain, $matches)) return false;
		return $matches[1] ?? '';
	}
	
	/**
	 * Проверяет является ли $domain поддоменом $parent
	 * 
	 * @param string $domain домен для проверки
	 * @param string $parent родительский домен
	 * @return boolean true если $domain != $parent и $domain явялестся поддоменом
	 */
	public static function isSubdomain(string $domain, string $parent) {
		$domain = trim($domain);
		if (empty($domain)) throw new \InvalidArgumentException('empty domain');
		$parent = trim($parent);
		if (empty($parent)) throw new \InvalidArgumentException('empty parent');
		return $domain != $parent && static::getSubdomain($domain, $parent) !== false;
	}

	/**
	 * Проверяет имеют ли домены взаимоотношения родитель-поддомен
	 * 
	 * @param string $domain1 домен1
	 * @param string $domain2 домен2
	 * @return boolean true, если $domain1 == $domain2 или один из них является поддоменом другого
	 */
	public static function isDomainsRelative(string $domain1, string $domain2) {
		$domain1 = trim($domain1);
		if (empty($domain1)) throw new \InvalidArgumentException('empty domain1');
		$domain2 = trim($domain2);
		if (empty($domain2)) throw new \InvalidArgumentException('empty domain2');
		return $domain1 == $domain2 || static::getSubdomain($domain1, $domain2) || static::getSubdomain($domain2, $domain1);
	}
	
	/**
	 * Возвращает user:pass@host:port часть URL
	 * 
	 * @param string|array $url
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function getHostInfo($url) {
		if (empty($url)) return '';

		if (is_string($url)) {
			$_url = parse_url($url);
			if ($_url == false) throw new \InvalidArgumentException('invalid url: '.$url);
			$url = $_url;
		}
		
		$hostInfo = '';
		if (isset($url['host'])) {
			if (isset($url['user'])) {
				$hostInfo .= $url['user'];
				if (isset($url['pass'])) $hostInfo .= ':'.$url['pass'];
				$hostInfo .= '@';
			}
			$hostInfo .= $url['host'];
			if (isset($url['port'])) $hostInfo .= ':'.$url['port'];
		}
		
		return $hostInfo;
	}
	
	
	/**
	 * Строит URL из частей (обратная parse_url).
	 * 
	 * @param array $parts [scheme, user, pass, host, port, path, query]
	 * @return string|null полный url из частей
	 */
	public static function buildUrl(array $parts) {
		$url = '';

		if (isset($parts['host'])) {
			if (isset($parts['scheme'])) $url .= $parts['scheme'] . ':';
			$url .= '//';
			$url .= static::getHostInfo($parts);
		}
		
		if (isset($parts['path'])) $url .= '/' . ltrim($parts['path'], '/');
		if (isset($parts['query'])) $url .= '?'.$parts['query'];
		if (isset($parts['fragment'])) $url .= '#'.$parts['fragment'];
		
		return $url;
	}
	
	/**
	 * Проверяет является ли ссылки с одного сайта (одинаковые scheme, user, pass, host и port).
	 * Причем одна из ссылок может быть относительной (часть инфы отсутствовать).
	 *
	 * @param string|array $siteUrl
	 * @param string|array $otherUrl
	 * @param bool $allowSubdoms поддомены считать тем же сайтом
	 */
	public static function isSameSite($url1, $url2, bool $allowSubdoms=false) {
		if (empty($url1) || empty($url2)) return true;
		
		// преобразуем в urlunfo
		if (!is_array($url1)) {
			$url = parse_url($url1);
			if (empty($url)) throw new \InvalidArgumentException('invalid url: '.$url1);
			$url1 = $url;
		}
		
		// преобразуем в urlunfo
		if (!is_array($url2)) {
			$url = parse_url($url2);
			if (empty($url)) throw new \InvalidArgumentException('invalid url: '.$url2);
			$url2 = $url;
		}
		
		// сравниваем схемы отдельно от hostInfo, например javascript:
		if (!empty($url1['scheme']) && !empty($url2['scheme']) && $url1['scheme'] !== $url2['scheme']) {
			return false;
		}
		
		// сравниваем информацию хоста user:pass@host:port
		if (!empty($url1['host']) && !empty($url2['host'])) {
			
			// сравниваем логин/пароль
			if (($url1['user'] ?? '') != ($url2['user'] ?? '') || ($url1['pass'] ?? '') != ($url2['pass'] ?? '')) return false;
			
			// сравниваем домены
			if ($url1['host'] != $url2['host']) {
				if (!$allowSubdoms) return false;
				if (!static::isDomainsRelative($url1['host'], $url2['host'])) return false;
			}
			
			// сравниваем порты
			if (($url1['pot'] ?? '') != ($url2['port'] ?? '')) return false;
		}
		
		return true;
	}
	
	/**
	 * Достраивает полный URL по базовому
	 *
	 * @param array|string $baseUrl базовый URL - массив parse_url или срока
	 * @param array|string $relativeUrl относительный URL - массив parse_url или срока
	 * @throws \InvalidArgumentException некорректные ссылки
	 *
	 * @return array urlInfo полный URL
	 */
	public static function getFullUrl($baseUrl, $relativeUrl) {
		if (empty($baseUrl)) throw new \InvalidArgumentException('пустой baseUrl');
		
		$url = $baseUrl;
		if (!is_array($url)) {
			$url = parse_url($url);
			if ($url === false) throw new \InvalidArgumentException('некорректный url: ' . $baseUrl);
		}
		
		$rel = $relativeUrl;
		if (!is_array($rel)) {
			$rel = parse_url($rel);
			if ($rel === false) throw new \InvalidArgumentException('некорректный url: ' . $relativeUrl);
		}
		
		// проверяем является ли относительная ссылка того же сайта
		if (!static::isSameSite($rel, $url)) return $relativeUrl;
		
		// дополняем относительную ссылку информацией
		if (!isset($rel['scheme'])) {
			if (!isset($rel['host'])) {
				if (!isset($rel['user']) && !isset($rel['pass'])) {
					if (!isset($rel['port'])) {
						if (empty($rel['path'])) {
							if (!isset($rel['query'])) {
								if (!isset($rel['fragment'])) {
									if (isset($url['fragment'])) $rel['fragment'] = $url['fragment'];
								}
								
								if (isset($url['query'])) $rel['query'] = $url['query'];
							}
							
							if (isset($url['path'])) $rel['path'] = $url['path'];
							
						} else if (mb_substr($rel['path'], 0, 1) !== '/') {
							
							if (!isset($url['path'])) {
								if (!empty($rel['path'])) $rel['path'] = '/'.$rel['path'];
							} else {
								$path = $url['path'];
								if (mb_substr($path, -1, 1) != '/') $path = dirname($path);
								$rel['path'] = rtrim($path, '/') . '/' . $rel['path'];
							}
						}
						
						if (isset($url['port'])) $rel['port'] = $url['port'];
					}
					
					if (isset($url['user'])) $rel['user'] = $url['user'];
					if (isset($url['pass'])) $rel['pass'] = $url['pass'];
				}
				
				if (isset($url['host'])) $rel['host'] = $url['host'];
			}
			
			if (isset($url['scheme'])) $rel['scheme'] = $url['scheme'];
		}
		
		return $rel;
	}
	
}
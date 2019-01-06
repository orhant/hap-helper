<?php
namespace dicr\tests;

use PHPUnit\Framework\TestCase;
use dicr\helper\UrlInfo;

/**
 * UrlInfo Test
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class UrlInfoTest extends TestCase {

	const TEST_NORMALIZE_HOST = [
		''	=> '',
		'site.ru' => 'site.ru',
		'//site.ru' => 'site.ru',
		'ftp://site.ru' => 'site.ru',
		'xn--80aswg.xn--p1ai/' => 'сайт.рф',
		'http://xn--80aswg.xn--p1ai' => 'сайт.рф',
		'site.ru?test=1' => 'site.ru',
		'site.ru/path/to' => 'site.ru'
	];

	/**
	 * Test UrlInfo::normalizeHost
	 */
	public function testNormalizeDomain() {
		foreach (self::TEST_NORMALIZE_HOST as $dom => $res) {
			self::assertEquals($res, UrlInfo::normalizeHost($dom));
		}
	}

	const TEST_NORMALIZE_PATH = [
		'' => '',
		'/' => '/',
	    '//./' => '/',
	    './/' => '/',
	    '../../' => '../../',
	    '/../..' => '/',
	    '/../../' => '/',
	    '/../../../path' => '/path',
		'/path/./../' => '/',
		'/path' => '/path',
		'path/' => 'path/',
		'path/to/test' => 'path/to/test',
		'path/./' => 'path/',
	];

	/**
	 * Test UrlInfo::normalizePath
	 */
	public function testNormalizePath() {
		foreach (self::TEST_NORMALIZE_PATH as $path => $res) {
			self::assertEquals($res, UrlInfo::normalizePath($path));
		}
	}

	const TEST_NORMALIZE_QUERY = [
		'' => [],
		'?' => [],
		'&' => [],
		'a' => ['a' => ''],
		'a=' => ['a' => ''],
		'a[3]=5' => ['a' => [3 => 5]],
		'?a=1&a=2' => ['a' => 2],
		'a[]=1&a[]=2' => ['a' => [1,2]],
		'b=1&a=2' => ['a' => 2, 'b' => 1],
		'?&&&c=3' => ['c' => 3]
	];

	/**
	 * Test UrlInfo::normalizeQuery
	 */
	public function testNormalizeQuery() {
		foreach (self::TEST_NORMALIZE_QUERY as $src => $res) {
			self::assertEquals($res, UrlInfo::normalizeQuery($src), 'src: '.$src);
		}
	}

	const TEST_SUBDOMAIN = [
		['mail.ru', 'mail.ru', ''],
		['mail.ru', 'test.mail.u', false],
		['test.mail.ru', 'mail.ru', 'test'],
		['test.mail.ru', 'yandex.ru', false],
	];

	/**
	 * Тест определения поддомена
	 */
	public function testSubdomain() {
		$urlInfo = new UrlInfo();
		foreach (self::TEST_SUBDOMAIN as list($domain, $parent, $result)) {
			$urlInfo->host = $domain;
			self::assertEquals($result, $urlInfo->getSubdomain($parent), $domain . '|' . $parent);
		}
	}

	const TEST_SAMESITE = [
	    ['mailto:test@dupad.ru', '//test@dupad.ru', false],
        ['//test@dupad.ru', 'mailto:test@dupad.ru', false],
        ['//test@dupad.ru', '//@dupad.ru', false],
        ['/path', '/path/', true],
        ['//dupad.ru/path', '/path/', true],
	    ['//dupad.ru', '/', true],
        ['//dupad.ru', '//dupad.ru:80', true],
        ['//dupad.ru:443', 'https://dupad.ru', true],
	    ['//dupad.ru:80', '//dupad.ru:81', false],
        ['https://dupad.ru', '/', true],
        ['https://dupad.ru', '//dupad.ru', true],
        ['https://dupad.ru', 'http://dupad.ru', false],
        ['//user@dupad.ru', '//dupad.ru', false],
        ['//user@dupad.ru', '//user@dupad.ru', true],
        ['//user:pass@dupad.ru', '//user@dupad.ru', false],
        ['//user:pass@dupad.ru', '//user:pass@dupad.ru', true],
        ['//user:pass@dupad.ru', '//user:pass@dupad.ru:80', true],
        ['http://user:pass@dupad.ru', '//user:pass@dupad.ru:80', true],
        ['https://user:pass@dupad.ru:83', '/page', true],
        ['//dupad.ru', '//test.dupad.ru', true],
        ['//dupad.ru', '//login@test.dupad.ru', false],
        ['//dupad.ru', '//test.dupad.ru:23', false],
        ['//test1.dupad.ru', '//test2.dupad.ru', false],
        ['https://dupad.ru', '//test.dupad.ru', true],
    ];

	/**
	 * Тестирование функции sameSite
	 */
	public function testSameSite() {
        foreach (self::TEST_SAMESITE as list($url1, $url2, $res)) {
            $urlInfo1 = new UrlInfo($url1);
			$urlInfo2 = new UrlInfo($url2);

			self::assertEquals($res, $urlInfo1->isSameSite($urlInfo2, [
			    'subdoms' => true
			]), $url1.'|'.$url2);
		}
	}

	const TEST_ABSOLUTE = [
		// коротки IDN домен с минимальным количеством параметров
		'http://сайт.рф' => [
			'' => 'http://сайт.рф',
			'#test' => 'http://сайт.рф#test',
			'?b=c&a=b' => 'http://сайт.рф?a=b&b=c',
			'/path/' => 'http://сайт.рф/path/',
			'../path' => 'http://сайт.рф/path',
			'../../' => 'http://сайт.рф',
			'..' => 'http://сайт.рф',
		],

		// полный basepath с файлом в пути
		'http://site.ru/path/to.php?prod=1#link' => [
			'' => 'http://site.ru/path/to.php?prod=1#link',		// пустая
			'#qwe' => 'http://site.ru/path/to.php?prod=1#qwe',	// fragment
			'?a=b' => 'http://site.ru/path/to.php?a=b',			// query
			'/new/index' => 'http://site.ru/new/index',			// absolute path
			'new/index#zzz' => 'http://site.ru/path/new/index#zzz',	// relative path
			'//mail.ru?a=b' => 'http://mail.ru?a=b',			// host
			'https://site.ru/' => 'https://site.ru',			// scheme
			'//ok.ru' => 'http://ok.ru'							// other host with same scheme
		],

		// basepath с директорией в конце
		'http://site.ru/path/to/' => [
			'/new/index' => 'http://site.ru/new/index',		// absolute path
			'new/index#zzz' => 'http://site.ru/path/to/new/index#zzz',	// relative path
			'./new/path' => 'http://site.ru/path/to/new/path',
			'../new/path' => 'http://site.ru/path/new/path',
			'../../new/path' => 'http://site.ru/new/path',
			'../../../new/path' => 'http://site.ru/new/path',
			'../../../new/path/' => 'http://site.ru/new/path/',
			'../' => 'http://site.ru/path/',
			'..' => 'http://site.ru/path'
		],

	    'https://up-advert.ru/anketirovali/' => [
			''	=> 'https://up-advert.ru/anketirovali/',
			'/' => 'https://up-advert.ru',
			'#' => 'https://up-advert.ru/anketirovali/',
			'#a' => 'https://up-advert.ru/anketirovali/#a',
			'tel:+7 342 2 111 563' => 'tel:+7 342 2 111 563',
			'mailto:info@up-advert.ru' => 'mailto:info@up-advert.ru',
			'//s.w.org' => 'https://s.w.org',
			'https://up-advert.ru/wp-json/' => 'https://up-advert.ru/wp-json/',
			'https://up-advert.ru/?p=136' => 'https://up-advert.ru?p=136',
			'/seo/' => 'https://up-advert.ru/seo/'
		]
	];

	/**
	 * Test UrlInfo::toAbsolute
	 */
	public function testAbsolute() {
		foreach (self::TEST_ABSOLUTE as $base => $tests) {
			$baseUrl = new UrlInfo($base);
			foreach ($tests as $src => $res) {
				$srcUrl = new UrlInfo($src);
				$resUrl = $srcUrl->toAbsolute($baseUrl);
				self::assertEquals($res, $resUrl->toString(), 'SRC: '.$src);
			}
		}
	}
}
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
class UrlInfoTest extends TestCase
{
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
	public function testNormalizeHost()
	{
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
	public function testNormalizePath()
	{
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
	public function testNormalizeQuery()
	{
		foreach (self::TEST_NORMALIZE_QUERY as $src => $res) {
			self::assertEquals($res, UrlInfo::normalizeQuery($src), 'src: '.$src);
		}
	}

	const TEST_SUBDOMAIN = [
		['site.ru', 'site.ru', ''],
		['site.ru', 'test.site.u', false],
		['test.site.ru', 'site.ru', 'test'],
		['test.site.ru', 'site2.ru', false],
	];

	/**
	 * Тест определения поддомена
	 */
	public function testSubdomain()
	{
		$urlInfo = new UrlInfo();
		foreach (self::TEST_SUBDOMAIN as list($domain, $parent, $result)) {
			$urlInfo->host = $domain;
			self::assertEquals($result, $urlInfo->getSubdomain($parent), $domain . '|' . $parent);
		}
	}

	const TEST_SAMESITE = [
	    ['mailto:test@site.ru', '//test@site.ru', false],
        ['//test@site.ru', 'mailto:test@site.ru', false],
        ['//test@site.ru', '//@site.ru', false],
        ['/path', '/path/', true],
        ['//site.ru/path', '/path/', true],
	    ['//site.ru', '/', true],
        ['//site.ru', '//site.ru:80', true],
        ['//site.ru:443', 'https://site.ru', true],
	    ['//site.ru:80', '//site.ru:81', false],
        ['https://site.ru', '/', true],
        ['https://site.ru', '//site.ru', true],
        ['https://site.ru', 'http://site.ru', false],
        ['//user@site.ru', '//site.ru', false],
        ['//user@site.ru', '//user@site.ru', true],
        ['//user:pass@site.ru', '//user@site.ru', false],
        ['//user:pass@site.ru', '//user:pass@site.ru', true],
        ['//user:pass@site.ru', '//user:pass@site.ru:80', true],
        ['http://user:pass@site.ru', '//user:pass@site.ru:80', true],
        ['https://user:pass@site.ru:83', '/page', true],
        ['//site.ru', '//test.site.ru', true],
        ['//site.ru', '//login@test.site.ru', false],
        ['//site.ru', '//test.site.ru:23', false],
        ['//test1.site.ru', '//test2.site.ru', false],
        ['https://site.ru', '//test.site.ru', true],
    ];

	/**
	 * Тестирование функции sameSite
	 */
	public function testSameSite()
	{
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
			'//site2.ru?a=b' => 'http://site2.ru?a=b',			// host
			'https://site.ru/' => 'https://site.ru',  			// scheme
			'//site2.ru' => 'http://site2.ru'					// other host with same scheme
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

	    'https://site.ru/anketirovali/' => [
			''	=> 'https://site.ru/anketirovali/',
			'/' => 'https://site.ru',
			'#' => 'https://site.ru/anketirovali/',
			'#a' => 'https://site.ru/anketirovali/#a',
			'tel:+7 342 2 111 563' => 'tel:+7 342 2 111 563',
			'mailto:info@site.ru' => 'mailto:info@site.ru',
			'//s.w.org' => 'https://s.w.org',
			'https://site.ru/wp-json/' => 'https://site.ru/wp-json/',
			'https://site.ru/?p=136' => 'https://site.ru?p=136',
			'/seo/' => 'https://site.ru/seo/'
		]
	];

	/**
	 * Test UrlInfo::toAbsolute
	 */
	public function testAbsolute()
	{
		foreach (self::TEST_ABSOLUTE as $base => $tests) {
			$baseUrl = new UrlInfo($base);
			foreach ($tests as $src => $res) {
				$srcUrl = new UrlInfo($src);
				$resUrl = $srcUrl->toAbsolute($baseUrl);
				self::assertEquals($res, $resUrl->toString(), 'SRC: '.$src);
			}
		}
	}

	/** @var array non-http links */
	const TEST_NONHTTP = [
	    'javascript:' => 'javascript:',
	    'javascript:void(0)' => 'javascript:void(0)',
	    'mailto:' => 'mailto:',
	    'mailto:test@site.ru' => 'mailto:test@site.ru',
	    'tel:' => 'tel:',
	    'tel:123-45-67' => 'tel:123-45-67'
	];

	/**
	 * Тесирует ссылки с не http-протоколом
	 */
	public function testNonHttp()
	{
	    foreach (self::TEST_NONHTTP as $src => $dst) {
	        $url = UrlInfo::fromString($src);
	        if ($dst === false) {
	            self::assertFalse($url, $src);
	        } else {
	            self::assertSame($dst, (string)$url, $src);
	        }
	    }
	}
}
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
		'/' => '',
		'//./' => '',
		'.//' => '',
		'/../../../path' => '/path',
		'/path/../../' => '/path/../../',
		'/path' => '/path',
		'path/' => 'path/',
		'path/to/test' => 'path/to/test',
		'path/./' => 'path/',
		'../../' => '../../'
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
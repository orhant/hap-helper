<?php
namespace dicr\tests;

use PHPUnit\Framework\TestCase;
use dicr\helper\PathInfo;

/**
 * PathInfo Test.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class PathInfoTest extends TestCase
{
	const TEST_NORMALIZE = [
		'' => '',

	    '/' => '/',
	    '//./' => '/',
	    '/../..' => '/',
	    '/../../' => '/',
	    '/../../../path' => '/path',

	    '.' => '.',
	    './/' => '.',
	    '../././/../' => '../..',
	    './../' => './..',
	    './../path/../..' => './../..',
	    '..' => '..',
	    '..//.' => '..',
	    '../../' => '../..',
	    '....' => '....',

	    'path' => 'path',
		'path/' => 'path',
		'path/to/test' => 'path/to/test',
		'path/./' => 'path',
	    '/path/./../' => '/',
		'/path' => '/path',
	    './path' => './path',
	    '../path/' => '../path',
	    './../path' => './../path',
	    '././path' => './path',
	    '../../path' => '../../path',
	    '/../../path' => '/path'
	];

	/**
	 * Test UrlInfo::normalizePath
	 */
	public function testNormalizePath()
	{
		foreach (self::TEST_NORMALIZE as $path => $res) {
			self::assertEquals($res, PathInfo::normalize($path), 'path: ' . $path);
		}
	}

	const TEST_PARENT = [
	    '' => '..',
	    '/' => '/',
	    '/path' => '/',
	    '/./path' => '/',
	    '/../path' => '/',
	    '.' => './..',
	    './../' => './../..',
	    '..' => '../..',
	    '.././' => '../..',
	    '././path' => '.',
	    '../path' => '..',
	    'path' => '',
	    'path/../' => '..',
	    'path/.' => '',
        'path/../..' => '../..'
	];

	public function testParent()
	{
		foreach (self::TEST_PARENT as $path => $res) {
			self::assertEquals($res, PathInfo::parent($path), 'parent: ' . $path);
		}
	}
}

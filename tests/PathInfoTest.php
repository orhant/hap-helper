<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 15.01.20 12:42:19
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\helper\PathInfo;
use PHPUnit\Framework\TestCase;

/**
 * PathInfo Test.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class PathInfoTest extends TestCase
{
    public const TEST_NORMALIZE = [
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
            self::assertSame($res, PathInfo::normalize($path), 'path: ' . $path);
        }
    }

    public const TEST_PARENT = [
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
            self::assertSame($res, PathInfo::parent($path), 'parent: ' . $path);
        }
    }
}

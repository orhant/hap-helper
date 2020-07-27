<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.07.20 05:49:15
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
use yii\base\BaseObject;
use function array_slice;
use function count;

/**
 * Модель файлового пути.
 *
 * @property-read string $path весь путь
 * @property-read bool $isAbsolute признак абсолютного
 * @property-read string|null $absolute абсолютный путь
 * @property-read string $parent родительский путь
 * @property-read string $file имя файла с расширением без пути
 * @property-read string $name имя без расширения
 * @property-read string $ext расширение файла
 */
class PathInfo extends BaseObject
{
    /** @var string */
    private $_path;

    /** @var string */
    private $_parent;

    /** @var string */
    private $_file;

    /** @var string имя файла без расширения */
    private $_name;

    /** @var string расширение */
    private $_ext;

    /**
     * Конструктор
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->_path = self::normalize($path);
        parent::__construct();
    }

    /**
     * Нормализует путь. Вырезает лишние / и ..
     *
     * @param string $path
     * @return string
     */
    public static function normalize(string $path) : string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        $parts = [];
        $isAbsolute = mb_strpos($path, DIRECTORY_SEPARATOR) === 0;

        foreach (preg_split('~/~um', $path, - 1, PREG_SPLIT_NO_EMPTY) as $part) {
            $partsEnd = end($parts);

            if ($part === '..') {
                if (empty($parts)) {
                    if (! $isAbsolute) {
                        $parts[] = $part;
                    }
                } elseif ($partsEnd === '..' || $partsEnd === '.') {
                    $parts[] = $part;
                } else {
                    array_pop($parts);
                }
            } elseif ($part === '.') {
                if (! $isAbsolute && empty($parts)) {
                    $parts[] = $part;
                }
            } else {
                $parts[] = $part;
            }
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
    }

    /**
     * Проверяет является ли путь абсолютным
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolute(string $path) : bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        return mb_strpos($path, '/') === 0;
    }

    /**
     * Возвращает абсолютный путь.
     *
     * @param string $path относительный путь
     * @return string|null абсолютный путь или null если не существует
     */
    public static function absolute(string $path) : ?string
    {
        $realPath = realpath($path);
        return $realPath === false ? null : (string)$realPath;
    }

    /**
     * Возвращает родительский путь
     *
     * @param string $path
     * @param int $levels
     * @return string
     */
    public static function parent(string $path, int $levels = 1) : string
    {
        if ($levels < 0) {
            throw new InvalidArgumentException('levels');
        }

        if (empty($levels)) {
            return $path;
        }

        $isAbsolute = self::isAbsolute($path);

        /** @var string[] $parts */
        $parts = preg_split('~/~um', self::normalize($path), - 1, PREG_SPLIT_NO_EMPTY);
        $partsEnd = end($parts);

        if (empty($parts)) {
            if (! $isAbsolute) {
                $parts = array_fill(count($parts), $levels, '..');
            }
        } elseif ($partsEnd === '..' || $partsEnd === '.') {
            $parts = array_merge($parts, array_fill(count($parts), $levels, '..'));
        } elseif ($parts < $levels) {
            $parts = $isAbsolute ? [] : array_fill(0, $levels - count($parts), '..');
        } else {
            $parts = array_slice($parts, 0, count($parts) - $levels);
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $path
     * @param string $relative
     * @return string
     */
    public static function child(string $path, string $relative) : string
    {
        return static::normalize($path . '/' . $relative);
    }

    /**
     * Возвращает имя файла (basename).
     *
     * @param string $path
     * @return string
     */
    public static function file(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @param string $path
     * @return string
     */
    public static function name(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Возвращает расширение файла.
     *
     * @param string $path
     * @return string
     */
    public static function ext(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Возвращает путь
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->_path;
    }

    /**
     * Возвращает признак абсолютного пути
     *
     * @return bool
     */
    public function getIsAbsolute() : bool
    {
        return self::isAbsolute($this->_path);
    }

    /**
     * Возвращает абсолютный путь
     *
     * @return string|null абсолютный путь или null если не существует
     */
    public function getAbsolute() : ?string
    {
        return self::absolute($this->_path);
    }

    /**
     * Возвращает родительский путь
     *
     * @param int $levels
     * @return string
     */
    public function getParent(int $levels = 1) : string
    {
        if (! isset($this->_parent)) {
            $this->_parent = self::parent($this->_path, $levels);
        }

        return $this->_parent;
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $relative
     * @return string
     */
    public function getChild(string $relative) : string
    {
        return self::child($this->_path, $relative);
    }

    /**
     * Возвращает файл (basename)
     *
     * @return string
     */
    public function getFile() : string
    {
        if (! isset($this->_file)) {
            $this->_file = self::file($this->_path);
        }

        return $this->_file;
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @return string
     */
    public function getName() : string
    {
        if (! isset($this->_name)) {
            $this->_name = self::name($this->_path);
        }

        return $this->_name;
    }

    /**
     * Возвращает расширение
     *
     * @return string
     */
    public function getExt() : string
    {
        if (! isset($this->_ext)) {
            $this->_ext = self::ext($this->_path);
        }

        return $this->_ext;
    }

    /**
     * Конвертирование в строку
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->_path;
    }
}

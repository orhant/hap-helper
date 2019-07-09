<?php
namespace dicr\helper;

use yii\base\BaseObject;

/**
 * Модель файлового пути.
 *
 * @property-read string $path весь путь
 * @property-read bool $isAbsolute признак абсолютного
 * @property-read string|null $absolute абослютный путь
 * @property-read string $parent родительский путь
 * @property-read string $file имя файла с расширением без пути
 * @property-read string $name имя без расширения
 * @property-read string $ext расширение файла
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
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
    }

    /**
     * Нормализирует путь. Вырезает лишние / и ..
     *
     * @param string $path
     * @return string
     */
    public static function normalize(string $path)
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        $parts = [];
        $isAbsolute = mb_substr($path, 0, 1) == DIRECTORY_SEPARATOR;

        foreach (preg_split('~\/~uism', $path, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if ($part === '..') {
                if (empty($parts)) {
                    if (!$isAbsolute) {
                        $parts[] = $part;
                    }
                } elseif (end($parts) === '..' || end($parts) === '.') {
                    $parts[] = $part;
                } else {
                    array_pop($parts);
                }
            } elseif ($part === '.') {
                if (!$isAbsolute && empty($parts)) {
                    $parts[] = $part;
                }
            } else {
                $parts[] = $part;
            }
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
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
     * Проверяет является ли путь абсолютным
     *
     * @param string $path
     * @return boolean
     */
    public static function isAbsolute(string $path)
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        return mb_substr($path, 0, 1) === '/';
    }

    /**
     * Возвращает признак абсолютного пути
     *
     * @return boolean
     */
    public function getIsAbsolute()
    {
        return self::isAbsolute($this->_path);
    }

    /**
     * Возвращает абсолютный путь.
     *
     * @param string $path относительный путь
     * @return string|null абсолютный путь или null если не существует
     */
    public static function absolute(string $path)
    {
        return realpath($path);
    }

    /**
     * Возвращает абсолютный путь
     *
     * @return string|null абсолютный путь или null если не существует
     */
    public function getAbsolute()
    {
        return self::absolute($this->_path);
    }

    /**
     * Возвращает родительский путь
     *
     * @param string $path
     * @param int $levels
     * @return string
     */
    public static function parent(string $path, int $levels = 1)
    {
        if ($levels < 0) {
            throw new \InvalidArgumentException('levels');
        } elseif (empty($levels)) {
            return $path;
        }

        /** @var bool $isAbsolute */
        $isAbsolute = self::isAbsolute($path);

        /** @var string[] $parts */
        $parts = preg_split('~\/~uism', self::normalizePath($path), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($parts)) {
            if (!$isAbsolute) {
                $parts = array_fill(count($parts), $levels, '..');
            }
        } elseif (end($parts) === '..' || end($parts) === '.') {
            $parts = array_merge($parts, array_fill(count($parts), $levels, '..'));
        } elseif ($parts < $levels) {
            $parts = $isAbsolute ? [] : array_fill(0, $levels - count($parts), '..');
        } else {
            $parts = array_slice($parts, 0, count($parts) - $levels);
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
    }

    /**
     * Возвращает родительский путь
     *
     * @param int $levels
     * @return string
     */
    public function getParent(int $levels = 1)
    {
        if (!isset($this->_parent)) {
            $this->_parent = self::parent($this->_path, $levels);
        }

        return $this->_parent;
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $path
     * @param string $relative
     * @return string
     */
    public static function child(string $path, string $relative)
    {
        return self::normalizePath($path . '/' . $relative);
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $relative
     * @return string
     */
    public function getChild(string $relative)
    {
        return self::child($this->_path, $relative);
    }

    /**
     * Возвращает имя файла (basename).
     *
     * @param string $path
     */
    public static function file(string $path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Возвращает файл (basename)
     *
     * @return string
     */
    public function getFile()
    {
        if (!isset($this->_file)) {
            $this->_file = self::file($this->_path);
        }

        return $this->_file;
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @param string $path
     * @return string
     */
    public static function name(string $path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @return string
     */
    public function getName()
    {
        if (!isset($this->_name)) {
            $this->_name = self::name($this->_path);
        }

        return $this->_name;
    }

    /**
     * Возвращает расширение файла.
     *
     * @param string $path
     * @return string
     */
    public static function ext(string $path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Возвращает расширение
     *
     * @return string
     */
    public function getExt()
    {
        if (!isset($this->_ext)) {
            $this->_ext = self::ext($this->_path);
        }

        return $this->_ext;
    }

    /**
     * Конверирование в строку
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_path;
    }
}
<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 29.08.20 04:01:30
 */

declare(strict_types = 1);

namespace dicr\helper;

use RuntimeException;
use yii\base\Exception;
use yii\base\Model;

use function array_search;
use function array_shift;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;

/**
 * Модель данных для структуры JSON:
 * - позволяет сопоставлять названия аттрибутов с названиями полей в JSON
 * - позволяет создавать вложенные объекты
 * - позволяет определить пользовательскую функцию для конвертирования значения
 *
 * @property array $json конфигурация объекта в виде JSON
 */
abstract class JsonEntity extends Model
{
    /**
     * Карта соответствия названий аттрибутов названиям полей данных JSON.
     *
     * Необходимо определить только те аттрибуты, название которых отличается
     * от названия полей в данных.
     *
     * По умолчанию составляет карту CamelCase => snake_case
     *
     * @return array [attribute => json field]
     */
    public function attributeFields(): array
    {
        /** @var string[] $fields кжш значения */
        static $fields = [];

        $class = static::class;
        if (! isset($fields[$class])) {
            $fields[$class] = [];

            foreach ($this->attributes() as $attribute) {
                $field = Inflector::camel2id($attribute, '_');
                if ($field !== $attribute) {
                    $fields[$class][$attribute] = $field;
                }
            }
        }

        return $fields[$class];
    }

    /**
     * Классы дочерних объектов для конвертирования из JSON в значения характеристик.
     *
     * @return array [attribute => string|array[1]|callable $type]
     * Возвращает типы объектов аттрибутов:
     * - string $class - класс объекта JsonEntity в который конвертируются данные
     * - array [$class] - класс объекта JsonEntity элемента массива
     * - callable $function($data, string $attribute): ?JsonEntity - функция для конвертирования данных в значение
     *     аттрибута
     */
    public function attributeEntities(): array
    {
        return [];
    }

    /**
     * Рекурсивно конвертирует значение аттрибута в данные JSON.
     *
     * @param string $attribute название характеристики
     * @param mixed $value значение характеристики
     * @return mixed значение данных для JSON
     */
    public function value2data(string $attribute, $value)
    {
        // пустое значение заменяем на null
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if ($value instanceof self) {
            return $value->getJson();
        }

        // массив обходим рекурсивно
        if (is_array($value)) {
            foreach ($value as $key => &$val) {
                $val = $this->value2data($attribute, $val);
                if ($val === null) {
                    unset($value[$key]);
                }
            }

            unset($val);
        }

        return $value;
    }

    /**
     * Конвертирует данные JSON в значение аттрибута.
     *
     * @param string $attribute название аттрибута
     * @param mixed $data данные
     * @return mixed
     * @throws Exception
     */
    public function data2value(string $attribute, $data)
    {
        if ($data === null || $data === '' || $data === []) {
            // пустые значение заменяем на null
            return null;
        }

        // если задано конвертирование значения аттрибута
        $entities = $this->attributeEntities();
        $class = $entities[$attribute] ?? null;

        if ($class !== null) {
            if (is_string($class)) {
                /** @var self $entity создаем вложенный объект JsonEntity */
                $entity = new $class();
                $entity->setJson($data);
                $data = $entity;
            } elseif (is_array($class) && ! empty($class[0])) {
                // массив объектов JsonEntity
                $entityClass = array_shift($class);

                foreach ($data as &$v) {
                    /** @var self $entity */
                    $entity = new $entityClass();
                    $entity->setJson($v);
                    $v = $entity;
                }

                unset($v);
            } elseif (is_callable($class)) {
                // функция для конвертирования
                $data = $class($data, $attribute);
            } else {
                throw new RuntimeException('Неизвестный тип объекта аттрибута: ' . $attribute . ': ' . $class);
            }
        }

        return $data;
    }

    /**
     * Конфигурация объекта из данных JSON.
     *
     * @param array $json данные конфигурации
     * @param bool $skipUnknown пропускать неизвестные аттрибуты (иначе exception)
     * @throws Exception
     */
    public function setJson(array $json, bool $skipUnknown = true)
    {
        // карта соответствия полей данных аттрибутам
        $map = $this->attributeFields();
        $attributes = $this->attributes();

        $data = [];

        // обходим все данные
        foreach ($json as $field => $d) {
            // получаем название аттрибута по имени поля данных в карте аттрибутов
            $attribute = (string)(array_search($field, $map, true) ?: $field);

            // конвертируем и устанавливаем значение
            $data[$attribute] = $this->data2value($attribute, $d);

            if (! $skipUnknown && ! in_array($attribute, $attributes, true)) {
                throw new Exception('Неизвестный аттрибут: ' . $attribute);
            }
        }

        if (! empty($data)) {
            $this->setAttributes($data, false);
        }
    }

    /**
     * Возвращает JSON данные объекта.
     *
     * @return array данные JSON
     */
    public function getJson(): array
    {
        $json = [];
        $map = $this->attributeFields();

        foreach ($this->getAttributes() as $attribute => $value) {
            // получаем значение данных
            $d = $this->value2data($attribute, $value);

            // пропускаем пустые значения
            if ($d !== null) {
                // получаем название поля данных
                $field = (string)($map[$attribute] ?? $attribute);

                // сохраняем значение данных
                $json[$field] = $d;
            }
        }

        return $json;
    }
}

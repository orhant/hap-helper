<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor A Tarasov <develop@dicr.org>
 */

declare(strict_types = 1);
namespace dicr\helper;

use yii\base\Model;
use yii\helpers\Json;

/**
 * Html helper.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Html extends \yii\helpers\Html
{
    /**
     * Синоним encode.
     *
     * @param string|null $str
     * @return string
     * @see self::encode
     */
    public static function esc($str)
    {
        return static::encode((string)$str);
    }

    /**
     * Конвертирует html в текст для XML.
     *
     * @param string $html
     * @return string plain-текст
     */
    public static function toText(string $html)
    {
        // декодируем html-символы &entity;
        $html = static::decode($html);

        // убираем теги
        $html = strip_tags($html);

        // меняем контрольные символы на пробелы
        $html = preg_replace('~[[:cntrl:]]+~uim', ' ', $html);

        return trim($html);
    }

    /**
     * Деэкранирует из html.
     *
     * @param string $str
     * @return string
     */
    public static function decode($str)
    {
        return html_entity_decode(html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'utf-8'));
    }

    /**
     * Множесво HTML meta тегов.
     *
     * @param string $type тип контента: 'name', 'property', ...
     * @param string[] $values значения key => content
     *
     * @return string
     */
    public static function metas(string $type, array $values)
    {
        ob_start();

        foreach ($values as $key => $val) {
            echo self::meta([
                $type => $key,
                'content' => $val
            ]);
        }

        return ob_get_clean();
    }

    /**
     * Html meta tag
     *
     * @param array $options
     * @return string
     */
    public static function meta(array $options)
    {
        return self::tag('meta', '', $options);
    }

    /**
     * StyleSheet- ссылка.
     *
     * @param string $href
     * @param array $options
     * @return string
     */
    public static function cssLink(string $href, array $options = [])
    {
        if (! isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }

        return static::link(array_merge($options, [
            'href' => $href
        ]));
    }

    /**
     * Html tag link
     *
     * @param array $options
     * @return string
     */
    public static function link(array $options)
    {
        return self::tag('link', '', $options);
    }

    /**
     * Множество HTML link
     *
     * @param string[] $links ассоциативный массив rel => href
     * @return string
     */
    public static function links(array $links)
    {
        ob_start();

        foreach ($links as $rel => $href) {
            echo static::link(['rel' => $rel, 'href' => $href]);
        }

        return ob_get_clean();
    }

    /**
     * Подключение скрипта.
     *
     * @param string $src
     * @return string
     */
    public static function jsLink(string $src)
    {
        return self::tag('script', '', ['src' => $src]);
    }

    /**
     * Рендерит булевое значение.
     *
     * @param \yii\base\Model $model
     * @param string $attribute
     * @return string
     */
    public static function activeFlag(Model $model, string $attribute)
    {
        return static::flag($model->{$attribute});
    }

    /**
     * Рендерит булевое значение флага.
     *
     * @param mixed $value
     * @return string
     */
    public static function flag($value)
    {
        return static::tag('i', '', [
            'class' => [$value ? 'fas' : 'far', 'fa-star']
        ]);
    }

    /**
     * Иконка FontAwesome старой версии (класс "fa fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function fa(string $name, array $options = [])
    {
        return static::tag('i', '', array_merge($options, [
            'class' => 'fa fa-' . $name
        ]));
    }

    /**
     * Иконка FontAwesome новой версии (класс "fas fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function fas(string $name, array $options = [])
    {
        return static::tag('i', '', array_merge($options, [
            'class' => 'fas fa-' . $name
        ]));
    }

    /**
     * Генерирует скрипт подключения jQuery плагина.
     *
     * @param string $target
     * @param string $name плагин
     * @param array $options опции плагина
     * @return string html
     */
    public static function plugin(string $target, string $name, array $options = [])
    {
        return self::script('$(function() {
            $("' . $target . '").' . $name . '(' . Json::encode($options) . ');
        });');
    }
}

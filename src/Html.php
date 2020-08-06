<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 07.08.20 01:36:42
 */

declare(strict_types = 1);

namespace dicr\helper;

use yii\base\Model;
use yii\helpers\Json;
use function array_merge;
use function html_entity_decode;
use function strtolower;
use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Html helper.
 */
class Html extends \yii\bootstrap4\Html
{
    /**
     * Синоним encode.
     *
     * @param string|int|float|null $str
     * @return string
     */
    public static function esc($str) : string
    {
        return static::encode((string)$str);
    }

    /**
     * Де-экранирует из html.
     *
     * @param string|float|null $str
     * @return string
     */
    public static function decode($str) : string
    {
        return html_entity_decode(html_entity_decode((string)$str, ENT_QUOTES | ENT_HTML5, 'utf-8'));
    }

    /**
     * Конвертирует html в текст для XML.
     *
     * @param string|float|null $html
     * @return string plain-текст
     */
    public static function toText($html) : string
    {
        // декодируем html-символы &entity;
        $text = static::decode((string)$html);

        // убираем теги
        $text = strip_tags($text);

        // меняем контрольные символы на пробелы
        $text = (string)preg_replace('~[[:cntrl:]]+~uim', ' ', $text);

        return trim($text);
    }

    /**
     * Проверяет содержит ли html текст, кроме пустых тегов.
     *
     * @param string|float|null $html
     * @return bool
     */
    public static function hasText($html) : bool
    {
        return static::toText((string)$html) !== '';
    }

    /**
     * Множество HTML meta тегов.
     *
     * @param string $type тип контента: 'name', 'property', ...
     * @param string[] $values значения key => content
     * @return string
     */
    public static function metas(string $type, array $values) : string
    {
        ob_start();

        foreach ($values as $key => $val) {
            echo static::meta([
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
    public static function meta(array $options) : string
    {
        return static::tag('meta', '', $options);
    }

    /**
     * StyleSheet- ссылка.
     *
     * @param string $href
     * @param array $options
     * @return string
     */
    public static function cssLink(string $href, array $options = []) : string
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
    public static function link(array $options) : string
    {
        return static::tag('link', '', $options);
    }

    /**
     * Множество HTML link
     *
     * @param string[] $links ассоциативный массив rel => href
     * @return string
     */
    public static function links(array $links) : string
    {
        ob_start();

        foreach ($links as $rel => $href) {
            echo static::link(compact('rel', 'href'));
        }

        return ob_get_clean();
    }

    /**
     * Подключение скрипта.
     *
     * @param string $src
     * @return string
     */
    public static function jsLink(string $src) : string
    {
        return static::tag('script', '', ['src' => $src]);
    }

    /**
     * Рендерит булево значение флага.
     *
     * @param mixed $value
     * @return string
     */
    public static function flag($value) : string
    {
        return static::tag('i', '', [
            'class' => [$value ? 'fas' : 'far', 'fa-star']
        ]);
    }

    /**
     * Рендерит булево значение.
     *
     * @param Model $model
     * @param string $attribute
     * @return string
     */
    public static function activeFlag(Model $model, string $attribute) : string
    {
        return static::flag($model->{$attribute});
    }

    /**
     * Иконка FontAwesome старой версии (класс "fa fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function fa(string $name, array $options = []) : string
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
    public static function fas(string $name, array $options = []) : string
    {
        return static::tag('i', '', array_merge($options, [
            'class' => 'fas fa-' . $name
        ]));
    }

    /**
     * Иконка FontAwesome новой версии (класс "far fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function far(string $name, array $options = []) : string
    {
        return static::tag('i', '', array_merge($options, [
            'class' => 'far fa-' . $name
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
    public static function plugin(string $target, string $name, array $options = []) : string
    {
        return static::script('$(function() {
            $("' . $target . '").' . $name . '(' . Json::encode($options) . ');
        });');
    }

    /**
     * Самозакрывающийся тэг XML.
     *
     * @param string $name
     * @param string|int|float|null $content
     * @param array $options
     * @return string
     */
    public static function xml(string $name, $content = '', array $options = []) : string
    {
        $name = strtolower($name);
        $content = (string)$content;

        return '<' . $name . static::renderTagAttributes($options) .
            ($content === '' ? '/>' : '>' . $content . '</' . $name . '>');
    }
}

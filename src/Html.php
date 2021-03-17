<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 17.03.21 05:57:29
 */

declare(strict_types = 1);

namespace dicr\helper;

use Yii;
use yii\base\Model;
use yii\helpers\Json;

use function array_filter;
use function html_entity_decode;
use function preg_replace;

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
    public static function esc($str): string
    {
        $str = (string)$str;

        return $str === '' ? '' :
            (string)preg_replace('~[[:cntrl:]]+~uim', ' ', static::encode($str));
    }

    /**
     * Де-экранирует из html.
     *
     * @param string|float|null $content
     * @return string
     */
    public static function decode($content): string
    {
        $content = (string)$content;

        return $content === '' ? '' :
            html_entity_decode(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'utf-8'));
    }

    /**
     * Конвертирует html в текст для XML.
     *
     * @param string|float|null $html
     * @return string plain-текст
     */
    public static function toText($html): string
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }

        // декодируем html-символы &entity;
        $text = static::decode($html);

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
    public static function hasText($html): bool
    {
        $html = (string)$html;

        return $html !== '' && static::toText($html) !== '';
    }

    /**
     * Элемент div
     *
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function div(string $content, array $options = []): string
    {
        return static::tag('div', $content, $options);
    }

    /**
     * Множество HTML meta тегов.
     *
     * @param string $type тип контента: 'name', 'property', ...
     * @param string[] $values значения key => content
     * @return string
     */
    public static function metas(string $type, array $values): string
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
    public static function meta(array $options): string
    {
        return static::tag('meta', '', $options);
    }

    /**
     * StyleSheet- ссылка.
     *
     * @param string $href
     * @param array $options
     * @return string
     * @deprecated use #cssFile
     */
    public static function cssLink(string $href, array $options = []): string
    {
        return parent::cssFile($href, $options);
    }

    /**
     * Html tag link
     *
     * @param array $options
     * @return string
     */
    public static function link(array $options): string
    {
        return static::tag('link', '', $options);
    }

    /**
     * Множество HTML link
     *
     * @param string[] $links ассоциативный массив rel => href
     * @return string
     */
    public static function links(array $links): string
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
     * @param array $options
     * @return string
     * @deprecated use #jsFile
     */
    public static function jsLink(string $src, array $options = []): string
    {
        return parent::jsFile($src, $options);
    }

    /**
     * Разметка schema.org LD+JSON
     *
     * @param array $schema
     * @return string
     */
    public static function schema(array $schema): string
    {
        $schema = array_filter(
            $schema, static fn($val): bool => $val !== null && $val !== '' && $val !== []
        );

        return ! empty($schema) ? static::tag('script', Json::encode($schema), [
            'type' => 'application/ld+json'
        ]) : '';
    }

    /**
     * Рендерит булево значение флага.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public static function flag($value, array $options = []): string
    {
        static::addCssClass($options, [$value ? 'fas' : 'far', 'fa-star']);

        return static::tag('i', '', $options);
    }

    /**
     * Рендерит булево значение.
     *
     * @param Model $model
     * @param string $attribute
     * @param array $options
     * @return string
     */
    public static function activeFlag(Model $model, string $attribute, array $options = []): string
    {
        return static::flag($model->{$attribute}, $options);
    }

    /**
     * Иконка FontAwesome старой версии (класс "fa fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function fa(string $name, array $options = []): string
    {
        static::addCssClass($options, 'fa fa-' . $name);

        return static::tag('i', '', $options);
    }

    /**
     * Иконка FontAwesome новой версии (класс "fas fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function fas(string $name, array $options = []): string
    {
        static::addCssClass($options, 'fas fa-' . $name);

        return static::tag('i', '', $options);
    }

    /**
     * Иконка FontAwesome новой версии (класс "far fa-$name").
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function far(string $name, array $options = []): string
    {
        static::addCssClass($options, 'far fa-' . $name);

        return static::tag('i', '', $options);
    }

    /**
     * Генерирует скрипт подключения jQuery плагина.
     *
     * @param string $target
     * @param string $name плагин
     * @param array $clientOptions опции плагина
     * @return string html
     */
    public static function plugin(string $target, string $name, array $clientOptions = []): string
    {
        return static::script('$(function() {
            $("' . $target . '").' . $name . '(' . Json::encode($clientOptions) . ');
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
    public static function xml(string $name, $content = '', array $options = []): string
    {
        $content = (string)$content;

        return '<' . $name . static::renderTagAttributes($options) .
            ($content === '' ? '/>' : '>' . $content . '</' . $name . '>');
    }

    /**
     * Ссылка tel:
     *
     * @param string $text текст ссылки
     * @param ?string $tel телефон
     * @param array $options
     * @return string
     */
    public static function tel(string $text, ?string $tel = null, array $options = []): string
    {
        return static::a(
            static::esc($text),
            'tel:' . preg_replace('~[\D]+~', '', $tel ?: $text),
            $options
        );
    }

    /**
     * @inheritDoc
     */
    public static function mailto($text, $email = null, $options = []): string
    {
        return static::a(
            static::esc($text),
            'mailto:' . static::esc($email ?: $text),
            $options
        );
    }

    /**
     * Возвращает параметры запроса в meta-тегах:
     * - meta property="route"
     * - meta property="params"
     *
     * @param ?array $url
     * @return string
     */
    public static function request(?array $url = null): string
    {
        $route = $url[0] ?? Yii::$app->controller->route ?? '';

        $params = Url::buildQuery(Url::normalizeQuery(Url::filterQuery(
            [0 => null] + ($url ?? Yii::$app->request->queryParams)
        )));

        return
            static::meta(['property' => 'route', 'content' => $route]) .
            static::meta(['property' => 'params', 'content' => $params]);
    }

    /**
     * link rel="canonical"
     *
     * @param string|array|null $url
     * @return string
     */
    public static function canonical($url = null): string
    {
        if ($url === null) {
            $url = Url::normalizeQuery(Url::filterQuery(
                [
                    0 => '/' . (Yii::$app->controller->route ?? ''),
                    'sort' => null,
                    'page' => null,
                    'limit' => null
                ] + Yii::$app->request->queryParams
            ));
        }

        return static::link([
            'rel' => 'canonical',
            'href' => Url::to($url, true)
        ]);
    }
}

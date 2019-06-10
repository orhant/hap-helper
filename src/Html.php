<?php
namespace dicr\helper;

use yii\base\Model;

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
        return parent::encode((string)$str);
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
     * Html tag link
     *
     * @param array $options
     * @return string
     */
    public static function link(array $options)
    {
        return Html::tag('link', '', $options);
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
            echo self::link(['rel' => $rel, 'href' => $href]);
        }

        return ob_get_clean();
    }

    /**
     * Рендерит булевое значение флага.
     *
     * @param mixed $value
     * @return string
     */
    public static function flag($value)
    {
        return Html::tag('i', '', [
            'class' => [$value ? 'fas' : 'far', 'fa-star']
        ]);
    }

    /**
     * Рендерит булевое значение.
     * @param \yii\base\Model $model
     * @param string $attribute
     * @return string
     */
    public static function activeFlag(Model $model, string $attribute)
    {
        return static::flag($model->{$attribute});
    }
}
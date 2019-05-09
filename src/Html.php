<?php
namespace dicr\helper;

/**
 * Html helper.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Html extends \yii\helpers\Html
{
    /**
     * @inheritdoc
     */
    public static function esc($str)
    {


        if ($str === null || $str === '') {
            return '';
        }

        return parent::encode((string) $str);
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
     * @param string $rel
     * @param string $href
     * @return string
     */
    public static function link(string $rel, string $href)
    {
        return Html::tag('link', '', [
            'rel' => $rel,
            'href' => $href
        ]);
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
            echo self::link($rel, $href);
        }

        return ob_get_clean();
    }
}
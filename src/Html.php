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
        return self::tag('meta', $options);
    }
}
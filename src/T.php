<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.06.20 18:29:30
 */

declare(strict_types = 1);

namespace dicr\helper;

use Yii;
use yii\i18n\PhpMessageSource;

/**
 * Транслятор текста.
 */
class T extends PhpMessageSource
{
    /** @var self */
    private static $instance;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->sourceLanguage = 'ru';
        $this->basePath = '@dicr/helper/messages';

        parent::init();

        if (Yii::$app !== null) {
            Yii::$app->i18n->translations['dicr/helper'] = $this;
        }

        self::$instance = $this;
    }

    /**
     * Перевод текста.
     *
     * @param string $msg
     * @param array $params
     * @return string
     */
    public static function t(string $msg, array $params = [])
    {
        if (self::$instance === null) {
            new self();
        }

        return Yii::t('dicr/helper', $msg, $params);
    }
}

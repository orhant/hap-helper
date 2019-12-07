<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.11.19 03:58:12
 */

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

use yii\db\Connection;
use yii\web\Application;

error_reporting(- 1);
ini_set('display_errors', '1');

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

new Application([
    'id' => 'testApp',
    'basePath' => __DIR__,
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'aliases' => [
        '@dicr/helper' => dirname(__DIR__) . '/src',
        '@dicr/tests' => dirname(__DIR__) . '/tests',
        '@web' => '@app/web',
        '@webroot' => __DIR__
    ],
    'components' => [
        'db' => [
            'class' => Connection::class,
            'dsn' => 'sqlite::memory:',
        ],
    ],
]);

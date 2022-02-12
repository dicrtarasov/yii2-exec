<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 04.09.20 03:01:32
 */

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

/** @var string */
const YII_ENV = 'dev';

/** @var bool */
const YII_DEBUG = true;

require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');

new yii\console\Application([
    'id' => 'test',
    'basePath' => __DIR__,
    'components' => [
        'cache' => yii\caching\ArrayCache::class,
        'urlManager' => [
            'hostInfo' => 'https://dicr.org'
        ],
        'exec' => dicr\exec\LocalExec::class
    ]
]);

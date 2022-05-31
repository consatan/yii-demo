<?php
if (!defined('YII_ENV')) {
    define('YII_ENV', 'test');
}

YII_ENV === 'test' or die('only running in test environment');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ .'/../vendor/autoload.php';

<?php

return [
    'id' => 'images-processor-bot',
    'basePath' => __DIR__,
    'bootstrap' => ['log'],
    'controllerNamespace' => 'controllers',
    'enableCoreCommands' => false,
    'components' => [
        /*'cache' => [
            'class' => 'yii\caching\FileCache',
        ],*/
        'parser' => [
            'class' => 'components\Parser'
        ],
        'beanstalk' => [
            'class' => 'components\Beanstalk'
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => require(__DIR__ . '/url.php')
];
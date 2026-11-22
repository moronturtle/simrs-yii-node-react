<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dev-only-change-me',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET api/ping' => 'site/ping',
                'POST api/auth/login' => 'api/auth/login',
                'GET api/auth/me' => 'api/auth/me',
                'GET api/admin/ping' => 'api/admin/ping',
                'GET api/visits' => 'api/visit/index',
                'POST api/visits' => 'api/visit/create',
                'GET api/visits/<id:\d+>' => 'api/visit/view',
                'PATCH api/visits/<id:\d+>/status' => 'api/visit/update-status',
                'GET api/visits/<visit_id:\d+>/examination' => 'api/examination/view',
                'POST api/visits/<visit_id:\d+>/examination' => 'api/examination/create',
                'GET api/visits/<visit_id:\d+>/actions' => 'api/visit-action/index',
                'POST api/visits/<visit_id:\d+>/actions' => 'api/visit-action/create',
                'GET api/visits/<visit_id:\d+>/prescriptions' => 'api/prescription/index',
                'POST api/visits/<visit_id:\d+>/prescriptions' => 'api/prescription/create',
                'GET api/prescriptions' => 'api/prescription/index',
                'GET api/prescriptions/<id:\d+>' => 'api/prescription/view',
                'POST api/prescriptions/<id:\d+>/dispense' => 'api/prescription/dispense',
                'GET api/patients' => 'api/patient/index',
                'POST api/patients' => 'api/patient/create',
                'GET api/patients/<id:\d+>' => 'api/patient/view',
                'PUT api/patients/<id:\d+>' => 'api/patient/update',
                'PATCH api/patients/<id:\d+>' => 'api/patient/update',
                'DELETE api/patients/<id:\d+>' => 'api/patient/delete',
                'GET api/doctors' => 'api/doctor/index',
                'POST api/doctors' => 'api/doctor/create',
                'GET api/doctors/<id:\d+>' => 'api/doctor/view',
                'PUT api/doctors/<id:\d+>' => 'api/doctor/update',
                'PATCH api/doctors/<id:\d+>' => 'api/doctor/update',
                'DELETE api/doctors/<id:\d+>' => 'api/doctor/delete',
                'GET api/polis' => 'api/poli/index',
                'POST api/polis' => 'api/poli/create',
                'GET api/polis/<id:\d+>' => 'api/poli/view',
                'PUT api/polis/<id:\d+>' => 'api/poli/update',
                'PATCH api/polis/<id:\d+>' => 'api/poli/update',
                'DELETE api/polis/<id:\d+>' => 'api/poli/delete',
                'GET api/schedules' => 'api/schedule/index',
                'POST api/schedules' => 'api/schedule/create',
                'GET api/schedules/<id:\d+>' => 'api/schedule/view',
                'PUT api/schedules/<id:\d+>' => 'api/schedule/update',
                'PATCH api/schedules/<id:\d+>' => 'api/schedule/update',
                'DELETE api/schedules/<id:\d+>' => 'api/schedule/delete',
                'GET api/actions' => 'api/medical-action/index',
                'POST api/actions' => 'api/medical-action/create',
                'GET api/actions/<id:\d+>' => 'api/medical-action/view',
                'PUT api/actions/<id:\d+>' => 'api/medical-action/update',
                'PATCH api/actions/<id:\d+>' => 'api/medical-action/update',
                'DELETE api/actions/<id:\d+>' => 'api/medical-action/delete',
                'GET api/medicines' => 'api/medicine/index',
                'POST api/medicines' => 'api/medicine/create',
                'GET api/medicines/<id:\d+>' => 'api/medicine/view',
                'PUT api/medicines/<id:\d+>' => 'api/medicine/update',
                'PATCH api/medicines/<id:\d+>' => 'api/medicine/update',
                'DELETE api/medicines/<id:\d+>' => 'api/medicine/delete',
                'OPTIONS api/<route:.*>' => 'site/options',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;

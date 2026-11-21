<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'pgsql:host=db;port=5432;dbname=simrs',
    'username' => getenv('DB_USER') ?: 'simrs',
    'password' => getenv('DB_PASSWORD') ?: 'simrs_password',
    'charset' => 'utf8',
];

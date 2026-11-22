<?php

declare(strict_types=1);

namespace app\services;

use yii\base\Exception;

class ServiceException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}

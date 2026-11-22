<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Poli;

class PoliController extends BaseCrudController
{
    protected array $searchableFields = ['code', 'name'];

    protected function modelClass(): string
    {
        return Poli::class;
    }
}

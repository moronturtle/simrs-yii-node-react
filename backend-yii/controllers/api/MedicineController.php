<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Medicine;

class MedicineController extends BaseCrudController
{
    protected array $searchableFields = ['code', 'name', 'unit'];

    protected function modelClass(): string
    {
        return Medicine::class;
    }
}

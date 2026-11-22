<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Doctor;

class DoctorController extends BaseCrudController
{
    protected array $searchableFields = ['name', 'specialization', 'phone'];

    protected function modelClass(): string
    {
        return Doctor::class;
    }
}

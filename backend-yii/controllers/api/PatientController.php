<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\Patient;

class PatientController extends BaseCrudController
{
    protected array $searchableFields = ['medical_record_no', 'nik', 'name', 'phone'];

    protected function modelClass(): string
    {
        return Patient::class;
    }

    protected function writeRoles(): array
    {
        return ['ADMIN', 'PENDAFTARAN'];
    }
}

<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

class BaseRecord extends ActiveRecord
{
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('updated_at')) {
            $this->updated_at = date('Y-m-d H:i:s');
        }

        return true;
    }
}

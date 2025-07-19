<?php

namespace App\Traits\Audits;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use Illuminate\Support\Arr;

trait AuditTrait
{
    public static function auditCustomEvent($model, $event, $message)
    {
        $model->auditEvent = $event;
        $model->isCustomEvent = true;
        $model->auditCustomOld = [];
        $model->auditCustomNew = ['message' => $message];

        Event::dispatch(AuditCustom::class, [$model]);

        $model->isCustomEvent = false;

        return $model;
    }

    public static function setAuditMessage($field, $data, $attribute)
    {
        $newValue = Arr::get($data, 'new_values.' . $attribute);
        $oldValue = Arr::get($data, 'old_values.' . $attribute);

        if (empty($newValue)) {
            return __(':old was removed from :field', ['old' => static::getAuditValue($oldValue), 'field' => $field]);
        } else if (empty($oldValue)) {
            return __(':field set to :new', ['field' => $field, 'new' => static::getAuditValue($newValue)]);
        } else {
            return __(':field changed from :old to :new', ['field' => $field, 'old' => static::getAuditValue($oldValue), 'new' => static::getAuditValue($newValue)]);
        }
    }

    public static function getAuditValue($str) {
        if (is_array($str)) {
            $str = implode(', ', $str);
        }

        return '<em>"' . $str . '"</em>';
    }
}

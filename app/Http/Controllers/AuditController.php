<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function auditDataTable(Request $request, string $modelName, string $modelId): JsonResponse
    {
        $modelFQN = 'App\\Models\\' . $modelName;

        if (in_array(SoftDeletes::class, class_uses_recursive($modelFQN))) {
            $builder = $modelFQN::withTrashed();
        } else {
            $builder = $modelFQN::query();
        }

        $auditCollection = $modelFQN::getAudits($request, $builder->find($modelId));

        $visibleFields = app('editColumn')->getVisibleFields('audit_log');

        return response()->json([
            'data' => $auditCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }
}

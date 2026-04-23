<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(
        string $module,
        string $action,
        string $description,
        ?int   $recordId  = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'user_id'    => $user?->id,
            'user_name'  => $user?->name,
            'module'     => $module,
            'action'     => $action,
            'record_id'  => $recordId,
            'description'=> $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}

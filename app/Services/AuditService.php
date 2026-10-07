<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): AuditLog {
        if (!config('security.audit_logging', true)) {
            return new AuditLog();
        }

        // Sanitize sensitive values before logging
        $sensitiveKeys = ['password', 'secret', 'secret_key', 'token', 'api_key'];
        $sanitize = function (?array $data) use ($sensitiveKeys) {
            if (!$data) return null;
            foreach ($sensitiveKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = '********';
                }
            }
            return $data;
        };

        return AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'old_values' => $sanitize($oldValues),
            'new_values' => $sanitize($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public static function logLogin(
        string $guard,
        string $email,
        ?int $userId,
        string $status
    ): LoginLog {
        return LoginLog::create([
            'guard' => $guard,
            'email' => $email,
            'user_id' => $userId,
            'status' => $status,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /** Record an activity document without allowing log storage issues to break the main action. */
    public static function log(string $action, string $entity, string $description, array $data = []): void
    {
        try {
            $actor = Auth::guard('admin')->user()
                ?? Auth::guard('web')->user()
                ?? Auth::guard('sanctum')->user();
            $logData = array_diff_key($data, [
                'password' => true,
                'password_confirmation' => true,
                'actor_id' => true,
                'actor_type' => true,
                'actor_name' => true,
            ]);

            ActivityLog::create([
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $data['entity_id'] ?? null,
                'description' => $description,
                'actor_id' => $actor?->getAuthIdentifier() ?? ($data['actor_id'] ?? null),
                'actor_type' => $actor ? get_class($actor) : ($data['actor_type'] ?? null),
                'actor_name' => $actor?->name ?? ($data['actor_name'] ?? null),
                'data' => $logData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}

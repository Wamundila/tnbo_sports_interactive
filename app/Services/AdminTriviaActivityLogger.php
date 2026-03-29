<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\TriviaActivityLog;

class AdminTriviaActivityLogger
{
    public function log(Admin $admin, string $eventName, string $referenceType, int $referenceId, array $metadata = []): void
    {
        TriviaActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => (string) $admin->id,
            'event_name' => $eventName,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

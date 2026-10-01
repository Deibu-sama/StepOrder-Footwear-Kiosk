<?php

namespace App\Services;

use Illuminate\Support\Str;

class ActivityLogService
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function record(string $action, array $data = []): void
    {
        $actor = session('steporder_admin', []);

        $payload = array_merge([
            'action' => $action,
            'actor_email' => $actor['email'] ?? 'Kiosk',
            'actor_role' => $actor['role'] ?? 'system',
            'created_at' => now()->toIso8601String(),
        ], $data);

        $this->firestore->create(
            'activity_logs',
            $payload,
            'log_'.Str::lower(Str::random(20))
        );
    }

    public function list(): array
    {
        $rows = $this->firestore->list('activity_logs');

        usort($rows, fn ($a, $b) =>
            strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''))
        );

        return $rows;
    }
}

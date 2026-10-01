<?php

namespace App\Services;

use Illuminate\Support\Str;

class StaffService
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function list(): array
    {
        $rows = $this->firestore->list('staff');

        usort($rows, fn ($a, $b) =>
            strcmp(strtolower((string)($a['name'] ?? '')), strtolower((string)($b['name'] ?? '')))
        );

        return $rows;
    }

    public function find(string $id): ?array
    {
        return $this->firestore->find('staff', $id);
    }

    public function findByEmail(string $email): ?array
    {
        $email = Str::lower(trim($email));

        foreach ($this->list() as $staff) {
            if (Str::lower((string)($staff['email'] ?? '')) === $email) {
                return $staff;
            }
        }

        return null;
    }

    public function create(array $data): string
    {
        $data['email'] = Str::lower(trim($data['email']));
        $data['role'] = 'cashier';
        $data['active'] = true;
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();

        return $this->firestore->create(
            'staff',
            $data,
            'staff_'.Str::lower(Str::random(18))
        );
    }

    public function update(string $id, array $data): ?array
    {
        if (isset($data['email'])) {
            $data['email'] = Str::lower(trim($data['email']));
        }

        $data['updated_at'] = now()->toIso8601String();

        return $this->firestore->update('staff', $id, $data);
    }

    public function delete(string $id): void
    {
        $this->firestore->delete('staff', $id);
    }
}

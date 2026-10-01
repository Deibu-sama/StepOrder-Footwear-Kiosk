<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $staff,
        private readonly ActivityLogService $activity
    ) {}

    public function index(Request $request)
    {
        $query = Str::lower(trim($request->string('q')->toString()));
        $status = $request->string('status')->toString();

        $staff = array_values(array_filter($this->staff->list(), function ($row) use ($query, $status) {
            if (($row['role'] ?? 'cashier') !== 'cashier') {
                return false;
            }

            if ($query) {
                $haystack = Str::lower(
                    (string)($row['name'] ?? '') . ' ' .
                    (string)($row['email'] ?? '')
                );

                if (!Str::contains($haystack, $query)) {
                    return false;
                }
            }

            if ($status === 'active' && !($row['active'] ?? false)) {
                return false;
            }

            if ($status === 'disabled' && ($row['active'] ?? false)) {
                return false;
            }

            return true;
        }));

        return view('admin.staff.index', compact('staff', 'query', 'status'));
    }

    public function create()
    {
        return view('admin.staff.create', [
            'mode' => 'create',
            'staff' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);

        if ($this->staff->findByEmail($data['email']) || strtolower($data['email']) === strtolower((string)env('ADMIN_EMAIL'))) {
            return back()->withInput($request->except('password'))->withErrors([
                'email' => 'That email address is already in use.',
            ]);
        }

        $id = $this->staff->create($data);

        $this->activity->record('CASHIER_CREATED', [
            'staff_id' => $id,
            'staff_email' => $data['email'],
            'details' => 'Cashier account created for '.$data['name'].'.',
        ]);

        return redirect('/admin/staff')->with('success', 'Cashier account created successfully.');
    }

    public function edit(string $id)
    {
        $staff = $this->staff->find($id);
        abort_unless($staff && ($staff['role'] ?? '') === 'cashier', 404);

        return view('admin.staff.edit', [
            'mode' => 'edit',
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $staff = $this->staff->find($id);
        abort_unless($staff && ($staff['role'] ?? '') === 'cashier', 404);

        $data = $this->validated($request, true);

        $existing = $this->staff->findByEmail($data['email']);
        if ($existing && ($existing['id'] ?? '') !== $id) {
            return back()->withInput($request->except('password'))->withErrors([
                'email' => 'That email address is already in use.',
            ]);
        }

        if (strtolower($data['email']) === strtolower((string)env('ADMIN_EMAIL'))) {
            return back()->withInput($request->except('password'))->withErrors([
                'email' => 'That email address belongs to the administrator account.',
            ]);
        }

        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
            'active' => $request->boolean('active'),
        ];

        if (filled($data['password'] ?? null)) {
            $updates['password_hash'] = Hash::make($data['password']);
        }

        $this->staff->update($id, $updates);

        $this->activity->record('CASHIER_UPDATED', [
            'staff_id' => $id,
            'staff_email' => $data['email'],
            'details' => 'Cashier account updated.',
        ]);

        return redirect('/admin/staff')->with('success', 'Cashier account updated successfully.');
    }

    public function toggle(string $id)
    {
        $staff = $this->staff->find($id);
        abort_unless($staff && ($staff['role'] ?? '') === 'cashier', 404);

        $active = !($staff['active'] ?? false);

        $this->staff->update($id, [
            'active' => $active,
        ]);

        $this->activity->record($active ? 'CASHIER_ENABLED' : 'CASHIER_DISABLED', [
            'staff_id' => $id,
            'staff_email' => $staff['email'] ?? '',
            'details' => 'Cashier access '.($active ? 'enabled.' : 'disabled.'),
        ]);

        return back()->with('success', 'Cashier access '.($active ? 'enabled.' : 'disabled.'));
    }

    public function destroy(string $id)
    {
        $staff = $this->staff->find($id);
        abort_unless($staff && ($staff['role'] ?? '') === 'cashier', 404);

        $email = $staff['email'] ?? '';

        $this->staff->delete($id);

        $this->activity->record('CASHIER_DELETED', [
            'staff_id' => $id,
            'staff_email' => $email,
            'details' => 'Cashier account deleted.',
        ]);

        return redirect('/admin/staff')->with('success', 'Cashier account deleted.');
    }

    private function validated(Request $request, bool $editing = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:160'],
            'password' => [$editing ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}

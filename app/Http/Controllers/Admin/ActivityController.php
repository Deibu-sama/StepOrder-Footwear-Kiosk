<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request)
    {
        $query = Str::lower(trim($request->string('q')->toString()));
        $action = $request->string('action')->toString();

        $logs = $this->activity->list();

        if ($query) {
            $logs = array_values(array_filter($logs, function ($log) use ($query) {
                return Str::contains(
                    Str::lower(
                        (string)($log['order_number'] ?? '') . ' ' .
                        (string)($log['actor_email'] ?? '') . ' ' .
                        (string)($log['staff_email'] ?? '') . ' ' .
                        (string)($log['details'] ?? '')
                    ),
                    $query
                );
            }));
        }

        if ($action) {
            $logs = array_values(array_filter(
                $logs,
                fn ($log) => ($log['action'] ?? '') === $action
            ));
        }

        $actions = [];
        foreach ($this->activity->list() as $log) {
            $name = $log['action'] ?? 'UNKNOWN';
            $actions[$name] = ($actions[$name] ?? 0) + 1;
        }
        arsort($actions);

        $soldUnits = 0;
        $releasedUnits = 0;
        foreach ($logs as $log) {
            if (($log['action'] ?? '') === 'ITEMS_SOLD') {
                $soldUnits += (int)($log['unit_count'] ?? 0);
            }
            if (($log['action'] ?? '') === 'ITEMS_RELEASED') {
                $releasedUnits += (int)($log['unit_count'] ?? 0);
            }
        }

        return view('admin.activity.index', compact(
            'logs',
            'query',
            'action',
            'actions',
            'soldUnits',
            'releasedUnits'
        ));
    }
}

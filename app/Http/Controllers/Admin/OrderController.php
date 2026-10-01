<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly ActivityLogService $activity
    ) {}

    public function pos()
    {
        $orders = $this->firestore->list('orders');

        $pending = [];
        $paid = [];
        $completed = [];

        foreach ($orders as $order) {
            $status = $order['status'] ?? 'pending';

            if ($status === 'pending') {
                $pending[] = $order;
            } elseif ($status === 'paid') {
                $paid[] = $order;
            } elseif ($status === 'completed') {
                $completed[] = $order;
            }
        }

        $sort = fn ($a, $b) => strcmp(
            (string)($b['created_at'] ?? ''),
            (string)($a['created_at'] ?? '')
        );

        usort($pending, $sort);
        usort($paid, $sort);
        usort($completed, $sort);

        return view('admin.pos.index', compact('pending', 'paid', 'completed'));
    }

    public function index(Request $request)
    {
        $orders = $this->firestore->list('orders');
        $q = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        if ($q) {
            $orders = array_values(array_filter($orders, function ($order) use ($q) {
                return Str::contains(
                    Str::lower(
                        (string)($order['order_number'] ?? '') . ' ' .
                        (string)($order['customer_name'] ?? '')
                    ),
                    Str::lower($q)
                );
            }));
        }

        if ($status) {
            $orders = array_values(array_filter(
                $orders,
                fn ($order) => ($order['status'] ?? 'pending') === $status
            ));
        }

        usort($orders, fn ($a, $b) =>
            strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''))
        );

        return view('admin.orders.index', compact('orders', 'q', 'status'));
    }

    public function show(string $id)
    {
        $order = $this->firestore->find('orders', $id);
        abort_unless($order, 404);

        return view('admin.orders.show', compact('order'));
    }

    public function status(Request $request, string $id)
    {
        $order = $this->firestore->find('orders', $id);
        abort_unless($order, 404);

        $newStatus = $request->string('status')->toString();
        $oldStatus = $order['status'] ?? 'pending';
        $actor = session('steporder_admin', []);
        $actorRole = $actor['role'] ?? 'cashier';

        abort_unless(
            in_array($newStatus, ['pending', 'paid', 'completed', 'cancelled'], true),
            422
        );

        if ($newStatus === 'paid' && $oldStatus !== 'pending') {
            return back()->with('error', 'Only pending orders can be marked as paid.');
        }

        if ($newStatus === 'completed' && $oldStatus !== 'paid') {
            return back()->with('error', 'An order must be paid before it can be released.');
        }

        if ($newStatus === 'pending' && $oldStatus !== 'pending') {
            return back()->with('error', 'Completed status changes cannot be rolled back.');
        }

        if ($newStatus === 'cancelled') {
            if ($oldStatus === 'completed' || $oldStatus === 'cancelled') {
                return back()->with('error', 'Completed or cancelled orders cannot be cancelled again.');
            }

            if ($oldStatus === 'paid' && $actorRole !== 'admin') {
                return back()->with('error', 'Only an administrator can cancel an already-paid order.');
            }

            if ($oldStatus !== 'cancelled') {
                $this->restoreOrderStock($order);
            }
        }

        $updates = [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($newStatus === 'paid') {
            $now = now()->toIso8601String();
            $updates['paid_at'] = $now;
            $updates['paid_by_email'] = $actor['email'] ?? 'Unknown';
            $updates['paid_by_role'] = $actorRole;
        }

        if ($newStatus === 'completed') {
            $now = now()->toIso8601String();
            $updates['completed_at'] = $now;
            $updates['released_at'] = $now;
            $updates['released_by_email'] = $actor['email'] ?? 'Unknown';
            $updates['released_by_role'] = $actorRole;
        }

        if ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = now()->toIso8601String();
            $updates['cancelled_by_email'] = $actor['email'] ?? 'Unknown';
            $updates['cancelled_by_role'] = $actorRole;
        }

        $this->firestore->update('orders', $id, $updates);

        $items = $order['items'] ?? [];
        $unitCount = array_sum(array_map(
            fn ($item) => (int)($item['quantity'] ?? 0),
            $items
        ));

        if ($newStatus === 'paid') {
            $this->activity->record('PAYMENT_RECEIVED', [
                'order_id' => $id,
                'order_number' => $order['order_number'] ?? $id,
                'items' => $items,
                'unit_count' => $unitCount,
                'total' => (float)($order['total'] ?? 0),
                'details' => 'Payment received for order.',
            ]);

            $this->activity->record('ITEMS_SOLD', [
                'order_id' => $id,
                'order_number' => $order['order_number'] ?? $id,
                'items' => $items,
                'unit_count' => $unitCount,
                'total' => (float)($order['total'] ?? 0),
                'details' => 'Items recorded as sold.',
            ]);
        }

        if ($newStatus === 'completed') {
            $this->activity->record('ITEMS_RELEASED', [
                'order_id' => $id,
                'order_number' => $order['order_number'] ?? $id,
                'items' => $items,
                'unit_count' => $unitCount,
                'total' => (float)($order['total'] ?? 0),
                'details' => 'Items released to customer.',
            ]);
        }

        if ($newStatus === 'cancelled') {
            $this->activity->record('ORDER_CANCELLED', [
                'order_id' => $id,
                'order_number' => $order['order_number'] ?? $id,
                'items' => $items,
                'unit_count' => $unitCount,
                'total' => (float)($order['total'] ?? 0),
                'details' => 'Order cancelled and stock restored.',
            ]);
        }

        return back()->with(
            'success',
            'Order #'.($order['order_number'] ?? $id).' marked '.strtoupper($newStatus).'.'
        );
    }

    private function restoreOrderStock(array $order): void
    {
        foreach (($order['items'] ?? []) as $item) {
            $product = $this->firestore->find('products', $item['product_id'] ?? '');

            if (!$product) {
                continue;
            }

            $variants = $product['variants'] ?? [];

            foreach ($variants as &$variant) {
                if (
                    ($variant['size'] ?? '') === ($item['size'] ?? '')
                    && ($variant['color'] ?? '') === ($item['color'] ?? '')
                ) {
                    $variant['stock'] = (int)($variant['stock'] ?? 0) + (int)($item['quantity'] ?? 0);
                }
            }

            unset($variant);

            $this->firestore->update('products', $product['id'], [
                'variants' => $variants,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

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

        abort_unless(
            in_array($newStatus, ['pending', 'paid', 'completed', 'cancelled'], true),
            422
        );

        $oldStatus = $order['status'] ?? 'pending';

        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            $this->restoreOrderStock($order);
        }

        $updates = [
            'status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $updates['paid_at'] = now()->toIso8601String();
        }

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $updates['completed_at'] = now()->toIso8601String();
            if (empty($order['paid_at'])) {
                $updates['paid_at'] = now()->toIso8601String();
            }
        }

        if ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = now()->toIso8601String();
        }

        $this->firestore->update('orders', $id, $updates);

        return back()->with('success', 'Order #'.($order['order_number'] ?? $id).' marked '.strtoupper($newStatus).'.');
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

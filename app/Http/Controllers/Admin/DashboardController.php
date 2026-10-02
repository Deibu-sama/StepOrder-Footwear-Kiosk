<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\PendingOrderService;
use App\Services\SettingsService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly SettingsService $settings,
        private readonly PendingOrderService $pendingOrders
    ) {}

    public function index()
    {
        $products = $this->firestore->list('products');
        $orders = $this->firestore->list('orders');
        $lowStockThreshold = (int)$this->settings->all()['low_stock_threshold'];
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $activeProducts = array_values(array_filter(
            $products,
            fn ($product) => ($product['status'] ?? 'active') === 'active'
        ));

        $todayOrders = array_values(array_filter(
            $orders,
            fn ($order) => str_starts_with((string)($order['created_at'] ?? ''), $today)
        ));

        $pendingOrders = array_values(array_filter(
            $orders,
            fn ($order) => ($order['status'] ?? 'pending') === 'pending'
        ));

        $pendingOrderWarnings = $this->pendingOrders->stale(
            $pendingOrders,
            (int)($this->settings->all()['pending_order_warning_hours'] ?? 24)
        );
        $pendingExpiryDays = (int)($this->settings->all()['pending_order_expiry_days'] ?? 7);

        $paidOrders = array_values(array_filter(
            $orders,
            fn ($order) => in_array(($order['status'] ?? ''), ['paid', 'completed'], true)
        ));

        $todaySales = array_sum(array_map(
            fn ($order) => in_array(($order['status'] ?? ''), ['paid', 'completed'], true)
                ? (float)($order['total'] ?? 0)
                : 0,
            $todayOrders
        ));

        $todayTransactions = count(array_filter(
            $todayOrders,
            fn ($order) => in_array(($order['status'] ?? ''), ['paid', 'completed'], true)
        ));

        $lowStockVariants = [];
        $outOfStockVariants = [];

        foreach ($products as $product) {
            foreach (($product['variants'] ?? []) as $variant) {
                $stock = (int)($variant['stock'] ?? 0);

                if ($stock === 0) {
                    $outOfStockVariants[] = [
                        'product_id' => $product['id'],
                        'product' => $product['name'] ?? 'Unknown',
                        'size' => $variant['size'] ?? '',
                        'color' => $variant['color'] ?? '',
                        'stock' => 0,
                    ];
                } elseif ($stock <= $lowStockThreshold) {
                    $lowStockVariants[] = [
                        'product_id' => $product['id'],
                        'product' => $product['name'] ?? 'Unknown',
                        'size' => $variant['size'] ?? '',
                        'color' => $variant['color'] ?? '',
                        'stock' => $stock,
                    ];
                }
            }
        }

        $recentOrders = $orders;
        usort($recentOrders, fn ($a, $b) =>
            strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''))
        );
        $recentOrders = array_slice($recentOrders, 0, 8);

        $productSales = [];
        foreach ($orders as $order) {
            if (!in_array(($order['status'] ?? ''), ['paid', 'completed'], true)) {
                continue;
            }

            foreach (($order['items'] ?? []) as $item) {
                $id = $item['product_id'] ?? null;
                if (!$id) {
                    continue;
                }

                if (!isset($productSales[$id])) {
                    $productSales[$id] = [
                        'id' => $id,
                        'name' => $item['name'] ?? 'Unknown',
                        'units' => 0,
                        'sales' => 0,
                    ];
                }

                $productSales[$id]['units'] += (int)($item['quantity'] ?? 0);
                $productSales[$id]['sales'] += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0);
            }
        }

        $topProducts = array_values($productSales);
        usort($topProducts, fn ($a, $b) => $b['units'] <=> $a['units']);
        $topProducts = array_slice($topProducts, 0, 5);

        return view('admin.dashboard.index', compact(
            'products',
            'activeProducts',
            'orders',
            'todayOrders',
            'pendingOrders',
            'pendingOrderWarnings',
            'pendingExpiryDays',
            'paidOrders',
            'todaySales',
            'todayTransactions',
            'lowStockVariants',
            'outOfStockVariants',
            'recentOrders',
            'topProducts'
        ));
    }
}

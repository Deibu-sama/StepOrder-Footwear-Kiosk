<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore, private readonly SettingsService $settings) {}

    public function index(Request $request)
    {
        $products = $this->firestore->list('products');
        $lowStockThreshold = (int)$this->settings->all()['low_stock_threshold'];
        $q = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $rows = [];
        $totalUnits = 0;
        $outOfStock = 0;
        $lowStock = 0;

        foreach ($products as $product) {
            if ($q && !Str::contains(
                Str::lower(($product['name'] ?? '') . ' ' . ($product['sku'] ?? '')),
                Str::lower($q)
            )) {
                continue;
            }

            $variants = $product['variants'] ?? [];
            $productUnits = 0;
            $productOut = 0;
            $productLow = 0;

            foreach ($variants as $variant) {
                $stock = (int)($variant['stock'] ?? 0);
                $productUnits += $stock;
                $totalUnits += $stock;

                if ($stock === 0) {
                    $outOfStock++;
                    $productOut++;
                } elseif ($stock <= $lowStockThreshold) {
                    $lowStock++;
                    $productLow++;
                }
            }

            $inventoryStatus = $productUnits === 0
                ? 'out'
                : ($productLow > 0 ? 'low' : 'healthy');

            if ($status && $inventoryStatus !== $status) {
                continue;
            }

            $rows[] = [
                'id' => $product['id'],
                'name' => $product['name'] ?? 'Unnamed Product',
                'sku' => $product['sku'] ?? '—',
                'category' => $product['category_name'] ?? '—',
                'gender' => $product['gender'] ?? 'Unisex',
                'image_url' => $product['image_url'] ?? '',
                'variants' => count($variants),
                'units' => $productUnits,
                'out' => $productOut,
                'low' => $productLow,
                'status' => $inventoryStatus,
            ];
        }

        usort($rows, fn ($a, $b) => $a['units'] <=> $b['units']);

        return view('admin.inventory.index', compact(
            'rows',
            'q',
            'status',
            'totalUnits',
            'outOfStock',
            'lowStock'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\FirestoreService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly SettingsService $settings,
        private readonly ActivityLogService $activity
    ) {}

    public function index()
    {
        $purchases = $this->firestore->list('purchases');
        usort($purchases, fn ($a, $b) => strcmp(
            (string)($b['purchase_date'] ?? $b['created_at'] ?? ''),
            (string)($a['purchase_date'] ?? $a['created_at'] ?? '')
        ));

        $settings = $this->settings->all();
        $eligibleInputVat = round(array_sum(array_map(
            fn ($purchase) => (float)($purchase['eligible_input_vat'] ?? 0),
            $purchases
        )), 2);

        return view('admin.purchases.index', compact('purchases', 'eligibleInputVat', 'settings'));
    }

    public function create()
    {
        $products = array_values(array_filter(
            $this->firestore->list('products'),
            fn ($product) => ($product['status'] ?? 'active') === 'active'
        ));
        usort($products, fn ($a, $b) => strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));

        return view('admin.purchases.create', [
            'products' => $products,
            'settings' => $this->settings->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:160'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'input_vat_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'input_vat_eligible' => ['nullable', 'boolean'],
            'vat_inclusive' => ['nullable', 'boolean'],
            'purchase_items' => ['required', 'array', 'min:1', 'max:100'],
            'purchase_items.*.product_id' => ['required', 'string', 'max:100'],
            'purchase_items.*.size' => ['required', 'string', 'max:80'],
            'purchase_items.*.color' => ['required', 'string', 'max:80'],
            'purchase_items.*.quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'purchase_items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $eligible = $request->boolean('input_vat_eligible');
        $vatInclusive = $request->boolean('vat_inclusive');
        if ($eligible && trim((string)($data['invoice_number'] ?? '')) === '') {
            return back()->withInput()->withErrors([
                'invoice_number' => 'Enter the supplier invoice number before claiming this purchase as input-VAT eligible.',
            ]);
        }

        $settings = $this->settings->all();
        $vatRate = max(0, (float)($settings['purchase_vat_rate'] ?? 12));
        $items = [];
        $subtotal = 0.0;
        $totalUnits = 0;

        foreach ($data['purchase_items'] as $line) {
            $product = $this->firestore->find('products', $line['product_id']);
            abort_unless($product, 422, 'A selected product no longer exists.');

            $variantExists = false;
            foreach (($product['variants'] ?? []) as $variant) {
                if (($variant['size'] ?? '') === $line['size'] && ($variant['color'] ?? '') === $line['color']) {
                    $variantExists = true;
                    break;
                }
            }
            if (!$variantExists) {
                return back()->withInput()->withErrors([
                    'purchase_items' => 'A selected product option is no longer available. Refresh the form and try again.',
                ]);
            }

            $quantity = (int)$line['quantity'];
            $unitCost = round((float)$line['unit_cost'], 2);
            $lineSubtotal = round($quantity * $unitCost, 2);
            $subtotal += $lineSubtotal;
            $totalUnits += $quantity;
            $items[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'] ?? 'Unnamed Product',
                'sku' => $product['sku'] ?? '',
                'size' => $line['size'],
                'color' => $line['color'],
                'quantity' => $quantity,
                'unit_cost_entered' => $unitCost,
                'line_subtotal' => $lineSubtotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $calculatedVat = isset($data['input_vat_amount']) && $data['input_vat_amount'] !== ''
            ? round((float)$data['input_vat_amount'], 2)
            : ($vatRate > 0
                ? ($vatInclusive
                    ? round($subtotal * $vatRate / (100 + $vatRate), 2)
                    : round($subtotal * $vatRate / 100, 2))
                : 0.0);
        $eligibleInputVat = $eligible ? $calculatedVat : 0.0;
        $purchaseTotal = $vatInclusive ? $subtotal : round($subtotal + $calculatedVat, 2);

        // Snapshot the purchase and update stock/costs using the net-of-credit cost where VAT is recoverable.
        foreach ($items as &$item) {
            $product = $this->firestore->find('products', $item['product_id']);
            $variants = $product['variants'] ?? [];
            $oldUnits = array_sum(array_map(fn ($v) => (int)($v['stock'] ?? 0), $variants));
            $oldUnitCost = max(0, (float)($product['cost_price'] ?? 0));
            $enteredCost = (float)$item['unit_cost_entered'];
            $lineVatPerUnit = (float)$subtotal > 0
                ? ((float)$calculatedVat * ((float)$item['line_subtotal'] / (float)$subtotal)) / max(1, (int)$item['quantity'])
                : 0;
            $recoverableVatPerUnit = $eligible ? $lineVatPerUnit : 0;
            $nonRecoverableVatPerUnit = (!$eligible && !$vatInclusive) ? $lineVatPerUnit : 0;
            $inventoryUnitCost = round($enteredCost - ($vatInclusive ? $recoverableVatPerUnit : 0) + $nonRecoverableVatPerUnit, 2);

            foreach ($variants as &$variant) {
                if (($variant['size'] ?? '') === $item['size'] && ($variant['color'] ?? '') === $item['color']) {
                    $variant['stock'] = (int)($variant['stock'] ?? 0) + (int)$item['quantity'];
                    break;
                }
            }
            unset($variant);

            $newUnits = $oldUnits + (int)$item['quantity'];
            $weightedCost = $newUnits > 0
                ? round((($oldUnitCost * $oldUnits) + ($inventoryUnitCost * (int)$item['quantity'])) / $newUnits, 2)
                : $inventoryUnitCost;

            $this->firestore->update('products', $product['id'], [
                'variants' => $variants,
                'cost_price' => $weightedCost,
                'updated_at' => now()->toIso8601String(),
            ]);

            $item['inventory_unit_cost'] = $inventoryUnitCost;
            $item['input_vat_estimate'] = $eligible ? round($recoverableVatPerUnit * (int)$item['quantity'], 2) : 0;
        }
        unset($item);

        $purchaseId = 'pur_'.Str::lower(Str::random(16));
        $purchase = [
            'supplier_name' => trim($data['supplier_name']),
            'invoice_number' => trim((string)($data['invoice_number'] ?? '')),
            'purchase_date' => date('Y-m-d', strtotime($data['purchase_date'])),
            'input_vat_eligible' => $eligible,
            'vat_inclusive' => $vatInclusive,
            'vat_rate' => $vatRate,
            'subtotal' => $subtotal,
            'input_vat_amount' => $calculatedVat,
            'eligible_input_vat' => $eligibleInputVat,
            'total' => $purchaseTotal,
            'items' => $items,
            'unit_count' => $totalUnits,
            'created_at' => now()->toIso8601String(),
            'created_by_email' => session('steporder_admin.email', 'Administrator'),
        ];

        $this->firestore->create('purchases', $purchase, $purchaseId);
        $this->activity->record('PURCHASE_RECEIVED', [
            'purchase_id' => $purchaseId,
            'supplier_name' => $purchase['supplier_name'],
            'invoice_number' => $purchase['invoice_number'],
            'unit_count' => $totalUnits,
            'total' => $purchaseTotal,
            'details' => 'Inventory received from supplier; eligible input VAT recorded only when explicitly marked eligible with an invoice number.',
        ]);

        return redirect('/admin/purchases')->with('success', 'Purchase recorded and inventory quantities updated.');
    }
}

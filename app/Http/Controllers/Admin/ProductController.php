<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly SettingsService $settings
    ) {}

    public function index(Request $request)
    {
        $products = $this->firestore->list('products');
        $categories = $this->firestore->list('categories');
        $lowStockThreshold = (int)$this->settings->all()['low_stock_threshold'];
        $q = trim($request->string('q')->toString());
        $category = $request->string('category')->toString();
        $gender = $request->string('gender')->toString();
        $status = $request->string('status')->toString();

        $categoryMap = [];
        foreach ($categories as $item) {
            $categoryMap[$item['id']] = $item['name'] ?? '—';
        }

        foreach ($products as &$product) {
            $product['_category'] = $product['category_name']
                ?? ($categoryMap[$product['category_id'] ?? ''] ?? 'Uncategorized');

            $product['_units'] = array_sum(array_map(
                fn ($variant) => (int)($variant['stock'] ?? 0),
                $product['variants'] ?? []
            ));

            $product['_variants'] = count($product['variants'] ?? []);

            $product['_out'] = count(array_filter(
                $product['variants'] ?? [],
                fn ($variant) => (int)($variant['stock'] ?? 0) === 0
            ));

            $product['_low'] = count(array_filter(
                $product['variants'] ?? [],
                fn ($variant) => (int)($variant['stock'] ?? 0) > 0 && (int)($variant['stock'] ?? 0) <= $lowStockThreshold
            ));
        }
        unset($product);

        if ($q) {
            $products = array_values(array_filter($products, fn ($product) =>
                Str::contains(
                    Str::lower(($product['name'] ?? '') . ' ' . ($product['sku'] ?? '')),
                    Str::lower($q)
                )
            ));
        }

        if ($category) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => ($product['category_id'] ?? '') === $category
            ));
        }

        if ($gender) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => ($product['gender'] ?? 'Unisex') === $gender
            ));
        }

        if ($status) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => ($product['status'] ?? 'active') === $status
            ));
        }

        $sort = $request->string('sort')->toString();
        if (!in_array($sort, ['name', 'price_asc', 'price_desc', 'stock_asc', 'stock_desc', 'recent'], true)) {
            $sort = 'name';
        }

        usort($products, function ($a, $b) use ($sort) {
            return match ($sort) {
                'price_asc' => ((float)($a['sale_price'] ?? $a['price'] ?? 0)) <=> ((float)($b['sale_price'] ?? $b['price'] ?? 0)),
                'price_desc' => ((float)($b['sale_price'] ?? $b['price'] ?? 0)) <=> ((float)($a['sale_price'] ?? $a['price'] ?? 0)),
                'stock_asc' => ((int)($a['_units'] ?? 0)) <=> ((int)($b['_units'] ?? 0)),
                'stock_desc' => ((int)($b['_units'] ?? 0)) <=> ((int)($a['_units'] ?? 0)),
                'recent' => strcmp((string)($b['updated_at'] ?? $b['created_at'] ?? ''), (string)($a['updated_at'] ?? $a['created_at'] ?? '')),
                default => strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')),
            };
        });

        $perPage = (int)$request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 20;

        $page = max(1, (int)$request->input('page', 1));
        $total = count($products);
        $items = array_slice($products, ($page - 1) * $perPage, $perPage);

        $products = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        );

        $view = $request->input('view', 'list');
        $view = in_array($view, ['list', 'grid'], true) ? $view : 'list';

        $columns = (int)$request->input('columns', 3);
        $columns = in_array($columns, [2, 3, 4], true) ? $columns : 3;

        return view('admin.products.index', compact(
            'products',
            'categories',
            'q',
            'category',
            'gender',
            'status',
            'sort',
            'perPage',
            'view',
            'columns'
        ));
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => null,
            'categories' => $this->firestore->list('categories'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->assertSkuAvailable($data['sku']);
        $data['status'] = $request->boolean('status', true) ? 'active' : 'inactive';
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();

        $this->firestore->create(
            'products',
            $data,
            'prod_'.Str::lower(Str::random(16))
        );

        return redirect('/admin/products')->with('success', 'Product created.');
    }

    public function edit(string $id)
    {
        $product = $this->firestore->find('products', $id);
        abort_unless($product, 404);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => $this->firestore->list('categories'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $this->validated($request);
        $this->assertSkuAvailable($data['sku'], $id);
        $data['status'] = $request->boolean('status', true) ? 'active' : 'inactive';
        $data['updated_at'] = now()->toIso8601String();

        $this->firestore->update('products', $id, $data);

        return redirect('/admin/products')->with('success', 'Product updated.');
    }

    public function destroy(string $id)
    {
        $product = $this->firestore->find('products', $id);
        abort_unless($product, 404);

        // Products are archived instead of physically deleted so existing orders keep their item references.
        $this->firestore->update('products', $id, [
            'status' => 'inactive',
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect('/admin/products')->with('success', 'Product archived and hidden from the kiosk.');
    }

    private function assertSkuAvailable(string $sku, ?string $exceptId = null): void
    {
        $sku = strtoupper(trim($sku));

        foreach ($this->firestore->list('products') as $existing) {
            $existingSku = strtoupper(trim((string)($existing['sku'] ?? '')));

            if ($existingSku !== $sku) {
                continue;
            }

            if ($exceptId !== null && ($existing['id'] ?? '') === $exceptId) {
                continue;
            }

            throw \Illuminate\Validation\ValidationException::withMessages([
                'sku' => 'That SKU is already assigned to another product.',
            ]);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'category_id' => ['required', 'string', 'max:80'],
            'category_name' => ['required', 'string', 'max:80'],
            'gender' => ['required', 'in:Unisex,Men,Women'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'is_top_pick' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['required', 'url', 'max:1000'],
            'color_images' => ['nullable', 'array'],
            'color_images.*' => ['nullable', 'url', 'max:1000'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color' => ['required', 'string', 'max:60'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['sku'] = strtoupper(trim($data['sku']));
        $variants = array_values(array_filter(
            array_map(
                fn ($variant) => [
                    'size' => trim($variant['size']),
                    'color' => trim($variant['color']),
                    'stock' => max(0, (int)$variant['stock']),
                ],
                $data['variants']
            ),
            fn ($variant) => $variant['size'] !== '' && $variant['color'] !== ''
        ));

        $data['variants'] = $variants;

        $regularPrice = (float)$data['price'];
        $salePrice = isset($data['sale_price']) && $data['sale_price'] !== ''
            ? (float)$data['sale_price']
            : null;

        $data['sale_price'] = $salePrice !== null && $salePrice > 0 && $salePrice < $regularPrice
            ? $salePrice
            : null;

        $data['is_top_pick'] = !empty($data['is_top_pick']);
        $data['gender'] = $data['gender'] ?? 'Unisex';

        $data['color_images'] = array_filter(
            array_map('trim', $data['color_images'] ?? []),
            fn ($url) => $url !== ''
        );

        return $data;
    }
}

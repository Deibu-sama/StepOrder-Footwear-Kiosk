<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        usort($products, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return view('admin.products.index', compact(
            'products',
            'categories',
            'q',
            'category',
            'gender',
            'status'
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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:50'],
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(Request $request)
    {
        $products = $this->firestore->list('products');
        $q = trim($request->string('q')->toString());

        if ($q) {
            $products = array_values(array_filter($products, fn ($p) =>
                Str::contains(Str::lower(($p['name'] ?? '') . ' ' . ($p['sku'] ?? '')), Str::lower($q))
            ));
        }

        usort($products, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return view('admin.products.index', compact('products', 'q'));
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

        $this->firestore->create(
            'products',
            $data,
            'prod_' . Str::lower(Str::random(16))
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
        $this->firestore->delete('products', $id);

        return redirect('/admin/products')->with('success', 'Product deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:50'],
            'category_id' => ['required', 'string', 'max:80'],
            'category_name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['required', 'url', 'max:1000'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color' => ['required', 'string', 'max:60'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $variants = array_values(array_filter(
            array_map(
                fn ($v) => [
                    'size' => trim($v['size']),
                    'color' => trim($v['color']),
                    'stock' => max(0, (int) $v['stock']),
                ],
                $data['variants']
            ),
            fn ($v) => $v['size'] !== '' && $v['color'] !== ''
        ));

        $data['variants'] = $variants;

        return $data;
    }
}

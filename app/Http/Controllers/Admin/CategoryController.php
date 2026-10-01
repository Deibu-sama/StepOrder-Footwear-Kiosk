<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index()
    {
        $categories = $this->firestore->list('categories');
        $products = $this->firestore->list('products');

        foreach ($categories as &$category) {
            $category['product_count'] = count(array_filter(
                $products,
                fn ($product) => ($product['category_id'] ?? '') === ($category['id'] ?? '')
            ));
        }
        unset($category);

        usort($categories, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['active'] = true;
        $data['slug'] = Str::slug($data['name']);
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();

        $this->firestore->create(
            'categories',
            $data,
            'cat_'.Str::lower(Str::random(12))
        );

        return redirect('/admin/categories')->with('success', 'Category created.');
    }

    public function edit(string $id)
    {
        $category = $this->firestore->find('categories', $id);
        abort_unless($category, 404);

        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, string $id)
    {
        $data = $this->validated($request);
        $data['active'] = $request->boolean('active', true);
        $data['slug'] = Str::slug($data['name']);
        $data['updated_at'] = now()->toIso8601String();

        $this->firestore->update('categories', $id, $data);

        return redirect('/admin/categories')->with('success', 'Category updated.');
    }

    public function destroy(string $id)
    {
        $products = $this->firestore->list('products');

        $used = count(array_filter(
            $products,
            fn ($product) => ($product['category_id'] ?? '') === $id
        ));

        if ($used > 0) {
            return back()->with('error', 'This category is assigned to '.$used.' product(s). Deactivate it instead of deleting it.');
        }

        $this->firestore->delete('categories', $id);

        return redirect('/admin/categories')->with('success', 'Category deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:1000'],
        ]);
    }
}

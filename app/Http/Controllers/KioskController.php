<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\SettingsService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KioskController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly SettingsService $settings,
        private readonly ActivityLogService $activity
    ) {}

    public function index()
    {
        return view('kiosk.start', ['settings' => $this->settings->all()]);
    }

    public function catalog(Request $request)
    {
        $config = $this->settings->all();
        $products = $this->activeProducts();

        if (!$config['show_out_of_stock']) {
            $products = array_values(array_filter($products, fn ($product) =>
                collect($product['variants'] ?? [])->contains(fn ($variant) => (int)($variant['stock'] ?? 0) > 0)
            ));
        }

        $categories = array_values(array_filter(
            $this->firestore->list('categories'),
            fn ($category) => ($category['active'] ?? true)
        ));

        $selectedCategory = $request->string('category')->toString();
        $search = trim($request->string('q')->toString());
        $filter = $request->string('filter')->toString();
        $gender = $request->string('gender')->toString();
        $priceRange = $request->string('price_range')->toString();

        if ($selectedCategory) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => ($product['category_id'] ?? '') === $selectedCategory
            ));
        }

        if ($gender) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => ($product['gender'] ?? 'Unisex') === $gender
            ));
        }

        if ($priceRange) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => $this->matchesPriceRange((float)($product['price'] ?? 0), $priceRange)
            ));
        }

        if ($search) {
            $products = array_values(array_filter(
                $products,
                fn ($product) => Str::contains(
                    Str::lower(($product['name'] ?? '') . ' ' . ($product['description'] ?? '') . ' ' . ($product['category_name'] ?? '')),
                    Str::lower($search)
                )
            ));
        }

        $orders = $this->firestore->list('orders');
        $purchaseCounts = [];

        foreach ($orders as $order) {
            foreach (($order['items'] ?? []) as $item) {
                $productId = $item['product_id'] ?? null;
                if ($productId) {
                    $purchaseCounts[$productId] = ($purchaseCounts[$productId] ?? 0) + (int)($item['quantity'] ?? 0);
                }
            }
        }

        foreach ($products as &$product) {
            $product['_sold_count'] = $purchaseCounts[$product['id']] ?? 0;
            $product['_sale_price'] = isset($product['sale_price']) && (float)$product['sale_price'] > 0
                ? (float)$product['sale_price']
                : null;
            $product['_is_sale'] = $product['_sale_price'] !== null && $product['_sale_price'] < (float)($product['price'] ?? 0);
            $product['_top_pick'] = (bool)($product['is_top_pick'] ?? false) || (bool)($product['is_most_bought'] ?? false) || $product['_sold_count'] >= 5;
        }
        unset($product);

        if ($filter === 'sale' && $config['show_sale_filter']) {
            $products = array_values(array_filter($products, fn ($product) => $product['_is_sale']));
        } elseif ($filter === 'top_pick' && $config['show_top_picks']) {
            $products = array_values(array_filter($products, fn ($product) => $product['_top_pick']));
            usort($products, fn ($a, $b) => ($b['_sold_count'] <=> $a['_sold_count']));
        }

        return view('kiosk.catalog-page', compact(
            'products',
            'categories',
            'selectedCategory',
            'search',
            'filter',
            'gender',
            'priceRange'
        ));
    }

    public function product(string $id)
    {
        $product = $this->firestore->find('products', $id);
        abort_unless($product && ($product['status'] ?? 'active') === 'active', 404);

        $product = $this->normalizeProduct($product);
        $cart = $this->cartData();
        $settings = $this->settings->all();

        $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
        $colors = collect($variants)->pluck('color')->filter()->unique()->values();
        $sizes = collect($variants)->pluck('size')->filter()->unique()->sort()->values();
        $defaultColor = $colors->first();
        $colorImages = is_array($product['color_images'] ?? null) ? $product['color_images'] : [];
        $allOut = count($variants) === 0 || collect($variants)->every(
            fn ($variant) => (int)($variant['stock'] ?? 0) <= 0
        );
        $configuredMax = (int)($settings['max_cart_quantity'] ?? 20);

        $allProducts = $this->activeProducts();
        $sameCategory = [];
        $sameGender = [];

        foreach ($allProducts as $candidate) {
            if (($candidate['id'] ?? '') === ($product['id'] ?? '')) {
                continue;
            }

            $available = !empty($candidate['variants'])
                && collect($candidate['variants'])->contains(fn ($variant) => (int)($variant['stock'] ?? 0) > 0);

            if (!$available) {
                continue;
            }

            if (($candidate['category_id'] ?? '') === ($product['category_id'] ?? '')) {
                $sameCategory[] = $candidate;
            } elseif (($candidate['gender'] ?? 'Unisex') === ($product['gender'] ?? 'Unisex')) {
                $sameGender[] = $candidate;
            }
        }

        $relatedProducts = array_slice(array_merge($sameCategory, $sameGender), 0, 4);

        foreach ($relatedProducts as &$related) {
            $regular = (float)($related['price'] ?? 0);
            $sale = isset($related['sale_price']) ? (float)$related['sale_price'] : null;
            $related['_is_sale'] = $sale !== null && $sale > 0 && $sale < $regular;
            $related['_sale_price'] = $related['_is_sale'] ? $sale : null;
            $related['_top_pick'] = (bool)($related['is_top_pick'] ?? false) || (bool)($related['is_most_bought'] ?? false);
        }
        unset($related);

        return view('kiosk.product-clean', compact(
            'product',
            'cart',
            'relatedProducts',
            'settings',
            'variants',
            'colors',
            'sizes',
            'defaultColor',
            'colorImages',
            'allOut',
            'configuredMax'
        ));
    }

    public function addToCart(Request $request)
    {
        $max = (int)$this->settings->all()['max_cart_quantity'];
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'size' => ['required', 'string', 'max:20'],
            'color' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
        ]);

        $product = $this->firestore->find('products', $data['product_id']);
        abort_unless($product && ($product['status'] ?? 'active') === 'active', 404);
        $variant = $this->findVariant($product, $data['size'], $data['color']);

        if (!$variant || (int)($variant['stock'] ?? 0) < 1) {
            return back()->with('error', 'That size/color is currently unavailable.');
        }

        if ((int)$data['quantity'] > (int)$variant['stock']) {
            return back()->with('error', 'Only '.$variant['stock'].' item(s) are available.');
        }

        $cart = $this->cartData();
        $key = $data['product_id'].'|'.$data['size'].'|'.$data['color'];
        $newQty = (int)($cart[$key]['quantity'] ?? 0) + (int)$data['quantity'];

        if ($newQty > (int)$variant['stock']) {
            return back()->with('error', 'You cannot add more than the available stock.');
        }

        if ($newQty > $max) {
            return back()->with('error', 'The maximum quantity per line is '.$max.'.');
        }

        $regularPrice = (float) ($product['price'] ?? 0);
        $salePrice = isset($product['sale_price']) && (float)$product['sale_price'] > 0 && (float)$product['sale_price'] < $regularPrice
            ? (float)$product['sale_price']
            : null;
        $effectivePrice = $salePrice ?? $regularPrice;

        $cart[$key] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'image_url' => $product['color_images'][$data['color']] ?? ($product['image_url'] ?? ''),
            'price' => $effectivePrice,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'size' => $data['size'],
            'color' => $data['color'],
            'quantity' => $newQty,
            'stock' => (int)$variant['stock']
        ];

        $request->session()->put('cart', $cart);
        return redirect('/products/'.$product['id'])->with('success', 'Added to cart.');
    }

    public function cart()
    {
        $cart = $this->cartData();
        $total = $this->cartTotal($cart);
        return view('kiosk.cart', compact('cart', 'total'));
    }

    public function updateCart(Request $request)
    {
        $max = (int)$this->settings->all()['max_cart_quantity'];
        $data = $request->validate([
            'key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
        ]);

        $cart = $this->cartData();
        if (!isset($cart[$data['key']])) {
            return back();
        }

        $item = &$cart[$data['key']];
        if ((int)$data['quantity'] > (int)$item['stock']) {
            return back()->with('error', 'Quantity exceeds available stock.');
        }

        $item['quantity'] = (int)$data['quantity'];
        $request->session()->put('cart', $cart);

        return back();
    }

    public function removeCart(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'string']]);
        $cart = $this->cartData();
        unset($cart[$data['key']]);
        $request->session()->put('cart', $cart);
        return back();
    }

    public function placeOrder(Request $request)
    {
        $cart = $this->cartData();

        if (!$cart) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        $items = array_values($cart);
        $total = $this->cartTotal($cart);

        foreach ($items as $item) {
            $product = $this->firestore->find('products', $item['product_id']);
            $variant = $product ? $this->findVariant($product, $item['size'], $item['color']) : null;

            if (!$variant || (int)$variant['stock'] < (int)$item['quantity']) {
                return redirect('/cart')->with('error', $item['name'].' is no longer available in the requested quantity.');
            }
        }

        foreach ($items as $item) {
            $product = $this->firestore->find('products', $item['product_id']);
            $variants = $product['variants'] ?? [];

            foreach ($variants as &$variant) {
                if (($variant['size'] ?? '') === $item['size'] && ($variant['color'] ?? '') === $item['color']) {
                    $variant['stock'] = (int)$variant['stock'] - (int)$item['quantity'];
                }
            }

            unset($variant);
            $this->firestore->update('products', $product['id'], ['variants' => $variants]);
        }

        $config = $this->settings->all();
        $orderNumber = $this->nextOrderNumber($config);

        $orderId = 'ord_'.Str::lower(Str::random(16));

        $this->firestore->create('orders', [
            'order_number' => $orderNumber,
            'items' => $items,
            'total' => $total,
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
            'created_by' => 'Kiosk',
        ], $orderId);

        $this->activity->record('ORDER_CREATED', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'items' => $items,
            'unit_count' => array_sum(array_map(fn ($item) => (int)($item['quantity'] ?? 0), $items)),
            'total' => $total,
            'details' => 'Order generated at customer kiosk.',
        ]);

        $request->session()->forget('cart');

        return redirect('/order/'.$orderNumber);
    }

    public function confirmation(string $orderNumber)
    {
        $orders = $this->firestore->findByField('orders', 'order_number', $orderNumber);
        abort_unless($orders, 404);
        $order = $orders[0];

        return view('kiosk.confirmation', compact('order'));
    }

    private function activeProducts(): array
    {
        $products = array_values(array_filter(
            $this->firestore->list('products'),
            fn ($p) => ($p['status'] ?? 'active') === 'active'
        ));

        return array_map(
            fn ($product) => $this->normalizeProduct($product),
            $products
        );
    }

    private function normalizeProduct(array $product): array
    {
        return array_replace([
            'id' => '',
            'name' => 'Unnamed Product',
            'sku' => '',
            'category_id' => '',
            'category_name' => 'Uncategorized',
            'gender' => 'Unisex',
            'price' => 0,
            'sale_price' => null,
            'is_top_pick' => false,
            'is_most_bought' => false,
            'description' => '',
            'image_url' => '',
            'color_images' => [],
            'status' => 'active',
            'variants' => [],
        ], $product);
    }

    private function cartData(): array
    {
        return session('cart', []);
    }

    private function cartTotal(array $cart): float
    {
        return round(array_sum(array_map(
            fn ($i) => (float)$i['price'] * (int)$i['quantity'],
            $cart
        )), 2);
    }

    private function findVariant(array $product, string $size, string $color): ?array
    {
        foreach (($product['variants'] ?? []) as $v) {
            if (($v['size'] ?? '') === $size && ($v['color'] ?? '') === $color) {
                return $v;
            }
        }

        return null;
    }

    private function matchesPriceRange(float $price, string $range): bool
    {
        return match ($range) {
            'under_1000' => $price < 1000,
            '1000_1999' => $price >= 1000 && $price < 2000,
            '2000_2999' => $price >= 2000 && $price < 3000,
            '3000_plus' => $price >= 3000,
            default => true,
        };
    }

    private function nextOrderNumber(array $config): string
    {
        $max = 0;

        foreach ($this->firestore->list('orders') as $order) {
            if (preg_match('/(\d+)$/', (string)($order['order_number'] ?? ''), $matches)) {
                $max = max($max, (int)$matches[1]);
            }
        }

        return (string)($config['order_prefix'] ?? '')
            .str_pad((string)($max + 1), (int)($config['order_digits'] ?? 4), '0', STR_PAD_LEFT);
    }
}

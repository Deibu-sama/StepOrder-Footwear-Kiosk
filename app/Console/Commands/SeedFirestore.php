<?php

namespace App\Console\Commands;

use App\Services\FirestoreService;
use Illuminate\Console\Command;

class SeedFirestore extends Command
{
    protected $signature = 'steporder:seed {--force : Create or refresh the StepOrder demo product catalog}';
    protected $description = 'Seed a sample generic product catalog into Firestore.';

    public function handle(FirestoreService $firestore): int
    {
        $categories = [
            ['id' => 'cat_sneakers', 'name' => 'Food & Drinks', 'slug' => 'food-drinks'],
            ['id' => 'cat_sports', 'name' => 'School Supplies', 'slug' => 'school-supplies'],
            ['id' => 'cat_sandals', 'name' => 'Groceries', 'slug' => 'groceries'],
            ['id' => 'cat_slippers', 'name' => 'Household', 'slug' => 'household'],
            ['id' => 'cat_clogs', 'name' => 'Health & Personal Care', 'slug' => 'health-personal-care'],
            ['id' => 'cat_casual', 'name' => 'Office Supplies', 'slug' => 'office-supplies'],
            ['id' => 'cat_formal', 'name' => 'Electronics', 'slug' => 'electronics'],
            ['id' => 'cat_school', 'name' => 'Other Essentials', 'slug' => 'other-essentials'],
        ];

        if ($this->option('force')) {
            foreach ($firestore->list('products') as $existing) {
                if (str_starts_with((string)($existing['sku'] ?? ''), 'STP-')) {
                    $firestore->delete('products', $existing['id']);
                }
            }
        }

        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[$category['name']] = $category['id'];

            $payload = [
                'name' => $category['name'],
                'slug' => $category['slug'],
                'active' => true,
                'description' => 'Sample ' . strtolower($category['name']) . ' category for the generic StepOrder catalog.',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            if ($firestore->find('categories', $category['id'])) {
                $firestore->update('categories', $category['id'], $payload);
            } else {
                $firestore->create('categories', $payload, $category['id']);
            }
        }

        $salePrices = [
            'STP-001' => 29,
            'STP-003' => 79,
            'STP-005' => 149,
            'STP-007' => 129,
        ];

        $topPickSkus = ['STP-001', 'STP-004', 'STP-006'];

        $colorImageOverrides = [];

        $products = [
            ['id'=>'demo_001','name'=>'Chocolate Snack Bar','sku'=>'STP-001','category'=>'Food & Drinks','price'=>35,'image'=>'https://images.unsplash.com/photo-1548907040-4d42f6ddf3f3?auto=format&fit=crop&w=900&q=80','sizes'=>['1 pc','6-pack'],'colors'=>['Milk Chocolate'=>12,'Dark Chocolate'=>8],'option1_label'=>'Pack size','option2_label'=>'Flavor'],
            ['id'=>'demo_002','name'=>'Bottled Water','sku'=>'STP-002','category'=>'Groceries','price'=>25,'image'=>'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=900&q=80','sizes'=>['500 ml','1.5 L'],'colors'=>['Still'=>20],'option1_label'=>'Volume','option2_label'=>'Type'],
            ['id'=>'demo_003','name'=>'Writing Notebook','sku'=>'STP-003','category'=>'School Supplies','price'=>89,'image'=>'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=900&q=80','sizes'=>['A5','A4'],'colors'=>['Lined'=>15,'Grid'=>6],'option1_label'=>'Paper size','option2_label'=>'Page style'],
            ['id'=>'demo_004','name'=>'Ballpoint Pen Set','sku'=>'STP-004','category'=>'Office Supplies','price'=>120,'image'=>'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=900&q=80','sizes'=>['1 pc','5-pack'],'colors'=>['Blue ink'=>15,'Black ink'=>10],'option1_label'=>'Pack size','option2_label'=>'Ink color'],
            ['id'=>'demo_005','name'=>'Rice Pack','sku'=>'STP-005','category'=>'Groceries','price'=>180,'image'=>'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=900&q=80','sizes'=>['1 kg','5 kg'],'colors'=>['White rice'=>20],'option1_label'=>'Weight','option2_label'=>'Variety'],
            ['id'=>'demo_006','name'=>'Instant Noodles','sku'=>'STP-006','category'=>'Food & Drinks','price'=>30,'image'=>'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=900&q=80','sizes'=>['1 cup','5-pack'],'colors'=>['Original'=>18,'Spicy'=>12],'option1_label'=>'Pack size','option2_label'=>'Flavor'],
            ['id'=>'demo_007','name'=>'Dishwashing Liquid','sku'=>'STP-007','category'=>'Household','price'=>145,'image'=>'https://images.unsplash.com/photo-1585421514738-01798e348b17?auto=format&fit=crop&w=900&q=80','sizes'=>['250 ml','500 ml'],'colors'=>['Lemon'=>10,'Original'=>12],'option1_label'=>'Volume','option2_label'=>'Scent'],
            ['id'=>'demo_008','name'=>'First Aid Kit','sku'=>'STP-008','category'=>'Health & Personal Care','price'=>399,'image'=>'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&w=900&q=80','sizes'=>['Basic','Family'],'colors'=>['Standard kit'=>5],'option1_label'=>'Kit size','option2_label'=>'Kit type'],
            ['id'=>'demo_009','name'=>'USB Flash Drive','sku'=>'STP-009','category'=>'Electronics','price'=>299,'image'=>'https://images.unsplash.com/photo-1618410320928-25228d811631?auto=format&fit=crop&w=900&q=80','sizes'=>['32 GB','64 GB'],'colors'=>['USB-A'=>8,'USB-C'=>6],'option1_label'=>'Capacity','option2_label'=>'Connector'],
            ['id'=>'demo_010','name'=>'Reusable Shopping Bag','sku'=>'STP-010','category'=>'Other Essentials','price'=>59,'image'=>'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?auto=format&fit=crop&w=900&q=80','sizes'=>['Small','Large'],'colors'=>['Natural'=>10,'Black'=>8],'option1_label'=>'Size','option2_label'=>'Color'],
        ];

        foreach ($products as $product) {
            $variants = [];

            foreach ($product['colors'] as $color => $baseStock) {
                foreach ($product['sizes'] as $size) {
                    $stock = (int) $baseStock;

                    // Small stock variation to demonstrate inventory by option combination.
                    if ($stock > 0 && ((int) $size + strlen($color)) % 4 === 0) {
                        $stock = max(1, $stock - 1);
                    }

                    $variants[] = [
                        'size' => $size,
                        'color' => $color,
                        'stock' => $stock,
                    ];
                }
            }

            $payload = [
                'name' => $product['name'],
                'sku' => $product['sku'],
                'category_id' => $categoryIds[$product['category']],
                'category_name' => $product['category'],
                'price' => $product['price'],
                'sale_price' => $salePrices[$product['sku']] ?? null,
                'is_top_pick' => in_array($product['sku'], $topPickSkus, true),
                'option1_label' => $product['option1_label'] ?? 'Option 1',
                'option2_label' => $product['option2_label'] ?? 'Option 2',
                'description' => 'Sample product for the StepOrder self-service kiosk.',
                'image_url' => $product['image'],
                'color_images' => array_replace(
                    array_fill_keys(array_keys($product['colors']), $product['image']),
                    $colorImageOverrides[$product['sku']] ?? []
                ),
                'status' => 'active',
                'variants' => $variants,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            if ($firestore->find('products', $product['id'])) {
                $firestore->update('products', $product['id'], $payload);
            } else {
                $firestore->create('products', $payload, $product['id']);
            }
        }

        $this->info('StepOrder generic sample catalog is ready: ' . count($products) . ' demo products across generic categories.');
        if ($this->option('force')) {
            $this->info('Previous STP-* demo products were replaced.');
        }

        return self::SUCCESS;
    }
}

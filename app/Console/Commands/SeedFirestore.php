<?php

namespace App\Console\Commands;

use App\Services\FirestoreService;
use Illuminate\Console\Command;

class SeedFirestore extends Command
{
    protected $signature = 'steporder:seed {--force : Create or refresh the StepOrder demo catalog}';
    protected $description = 'Seed a sample StepOrder footwear catalog into Firestore.';

    public function handle(FirestoreService $firestore): int
    {
        $categories = [
            ['id' => 'cat_sneakers', 'name' => 'Sneakers', 'slug' => 'sneakers'],
            ['id' => 'cat_sports', 'name' => 'Sports Shoes', 'slug' => 'sports-shoes'],
            ['id' => 'cat_sandals', 'name' => 'Sandals', 'slug' => 'sandals'],
            ['id' => 'cat_slippers', 'name' => 'Slippers', 'slug' => 'slippers'],
            ['id' => 'cat_clogs', 'name' => 'Clogs', 'slug' => 'clogs'],
            ['id' => 'cat_casual', 'name' => 'Casual Shoes', 'slug' => 'casual-shoes'],
            ['id' => 'cat_formal', 'name' => 'Formal Shoes', 'slug' => 'formal-shoes'],
            ['id' => 'cat_school', 'name' => 'School Shoes', 'slug' => 'school-shoes'],
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
                'description' => 'StepOrder ' . strtolower($category['name']) . ' collection.',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            if ($firestore->find('categories', $category['id'])) {
                $firestore->update('categories', $category['id'], $payload);
            } else {
                $firestore->create('categories', $payload, $category['id']);
            }
        }

        $products = [
            ['id'=>'demo_001','name'=>'Classic Runner','sku'=>'STP-001','category'=>'Sneakers','price'=>2499,'image'=>'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Black'=>5,'White'=>4]],
            ['id'=>'demo_002','name'=>'Urban White','sku'=>'STP-002','category'=>'Sneakers','price'=>2199,'image'=>'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['White'=>5,'Blue'=>3]],
            ['id'=>'demo_003','name'=>'Street Court','sku'=>'STP-003','category'=>'Sneakers','price'=>2899,'image'=>'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=80','sizes'=>['40','41','42','43'],'colors'=>['Black'=>7,'Green'=>0]],
            ['id'=>'demo_004','name'=>'Sprint Pro','sku'=>'STP-004','category'=>'Sports Shoes','price'=>3299,'image'=>'https://images.unsplash.com/photo-1539185441755-769473a23570?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Blue'=>6,'Orange'=>2]],
            ['id'=>'demo_005','name'=>'Daily Trainer','sku'=>'STP-005','category'=>'Sports Shoes','price'=>2499,'image'=>'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42'],'colors'=>['Gray'=>4,'Black'=>4]],
            ['id'=>'demo_006','name'=>'Active Step','sku'=>'STP-006','category'=>'Sports Shoes','price'=>2799,'image'=>'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=900&q=80','sizes'=>['40','41','42','43'],'colors'=>['Black'=>0,'Red'=>3]],
            ['id'=>'demo_007','name'=>'Comfort Slide','sku'=>'STP-007','category'=>'Sandals','price'=>799,'image'=>'https://images.unsplash.com/photo-1603487742131-4160ec999306?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Black'=>8,'Cream'=>5]],
            ['id'=>'demo_008','name'=>'Everyday Sandal','sku'=>'STP-008','category'=>'Sandals','price'=>999,'image'=>'https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=900&q=80','sizes'=>['38','39','40','41','42'],'colors'=>['Brown'=>4,'Tan'=>4]],
            ['id'=>'demo_009','name'=>'Cloud Walk','sku'=>'STP-009','category'=>'Slippers','price'=>899,'image'=>'https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Gray'=>6,'Black'=>6]],
            ['id'=>'demo_010','name'=>'Soft Home','sku'=>'STP-010','category'=>'Slippers','price'=>649,'image'=>'https://images.unsplash.com/photo-1570332092245-1ed5b4c8a8b0?auto=format&fit=crop&w=900&q=80','sizes'=>['38','39','40','41'],'colors'=>['Pink'=>0,'Blue'=>5]],
            ['id'=>'demo_011','name'=>'Classic Clog','sku'=>'STP-011','category'=>'Clogs','price'=>799,'image'=>'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['White'=>4,'Black'=>4]],
            ['id'=>'demo_012','name'=>'Platform Clog','sku'=>'STP-012','category'=>'Clogs','price'=>879,'image'=>'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a3?auto=format&fit=crop&w=900&q=80','sizes'=>['37','38','39','40','41'],'colors'=>['Blue'=>3,'Cream'=>3]],
            ['id'=>'demo_013','name'=>'Canvas Daily','sku'=>'STP-013','category'=>'Casual Shoes','price'=>1599,'image'=>'https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Black'=>5,'White'=>5]],
            ['id'=>'demo_014','name'=>'Weekend Lace','sku'=>'STP-014','category'=>'Casual Shoes','price'=>1899,'image'=>'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=900&q=80','sizes'=>['40','41','42','43'],'colors'=>['Brown'=>4,'Navy'=>2]],
            ['id'=>'demo_015','name'=>'Classic Derby','sku'=>'STP-015','category'=>'Formal Shoes','price'=>2599,'image'=>'https://images.unsplash.com/photo-1614252369475-531eba835eb1?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42','43'],'colors'=>['Black'=>4,'Brown'=>4]],
            ['id'=>'demo_016','name'=>'Office Loafer','sku'=>'STP-016','category'=>'Formal Shoes','price'=>2399,'image'=>'https://images.unsplash.com/photo-1616406432452-07bc5938759d?auto=format&fit=crop&w=900&q=80','sizes'=>['39','40','41','42'],'colors'=>['Black'=>3,'Brown'=>3]],
            ['id'=>'demo_017','name'=>'School Classic','sku'=>'STP-017','category'=>'School Shoes','price'=>1399,'image'=>'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=900&q=80','sizes'=>['35','36','37','38','39','40'],'colors'=>['Black'=>10]],
            ['id'=>'demo_018','name'=>'School Lace-Up','sku'=>'STP-018','category'=>'School Shoes','price'=>1499,'image'=>'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=900&q=80','sizes'=>['35','36','37','38','39'],'colors'=>['Black'=>0,'Brown'=>2]],
        ];

        foreach ($products as $product) {
            $variants = [];

            foreach ($product['colors'] as $color => $baseStock) {
                foreach ($product['sizes'] as $size) {
                    $stock = (int) $baseStock;

                    // Small stock variation so the kiosk demonstrates per-size inventory.
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
                'description' => 'Demo footwear item for the StepOrder self-service kiosk.',
                'image_url' => $product['image'],
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

        $this->info('StepOrder sample catalog is ready: 8 categories and ' . count($products) . ' demo products.');
        if ($this->option('force')) {
            $this->info('Previous STP-* demo products were replaced.');
        }

        return self::SUCCESS;
    }
}

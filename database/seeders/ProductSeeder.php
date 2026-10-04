<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // SAMPLE products for testing only. Names, prices and stock are placeholders;
        // you will replace them with your real products later.
        // Format: [category slug, name, slug, sku, price, stock, featured?]
        $products = [
            ['wall-decor',         'Abstract Canvas Wall Art',   'abstract-canvas-wall-art',   'WD-001', 3500, 12, true],
            ['wall-decor',         'Floral Framed Print',        'floral-framed-print',        'WD-002', 2200, 20, false],
            ['mirrors',            'Round Gold Frame Mirror',    'round-gold-frame-mirror',    'MR-001', 6500,  8, true],
            ['mirrors',            'Arched Wall Mirror',         'arched-wall-mirror',         'MR-002', 7800,  5, false],
            ['islamic-decor',      'Acrylic Islamic Wall Art',   'acrylic-islamic-wall-art',   'IS-001', 4200, 15, true],
            ['islamic-decor',      'Ayat ul Kursi Frame',        'ayat-ul-kursi-frame',        'IS-002', 3800, 10, false],
            ['wall-clocks',        'Minimal Wooden Wall Clock',  'minimal-wooden-wall-clock',  'WC-001', 3000, 14, true],
            ['decorative-pieces',  'Ceramic Table Vase',         'ceramic-table-vase',         'DP-001', 1800, 25, false],
            ['organizers',         'Woven Storage Basket',       'woven-storage-basket',       'OR-001', 1500, 18, false],
            ['personalized-decor', 'Custom Name Wall Frame',     'custom-name-wall-frame',     'PD-001', 4500,  0, false], // 0 stock: tests "out of stock"
        ];

        foreach ($products as [$categorySlug, $name, $slug, $sku, $price, $stock, $featured]) {

            // Find the category by its slug (created by CategorySeeder)
            $category = Category::where('slug', $categorySlug)->first();
            if (! $category) {
                continue; // skip if the category is missing
            }

            // updateOrCreate: running this twice never makes duplicates
            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id'      => $category->id,
                    'name'             => $name,
                    'sku'              => $sku,
                    'description'      => 'Sample description for ' . $name . '. Replace with the real product description.',
                    'price'            => $price,
                    'stock'            => $stock,
                    'status'           => 'active',
                    'is_featured'      => $featured,
                    'seo_title'        => $name . ' | SGHomeTrends',
                    'meta_description' => 'Shop ' . $name . ' at SGHomeTrends.',
                ]
            );
        }
    }
}

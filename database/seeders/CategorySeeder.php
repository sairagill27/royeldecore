<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // The 7 shop categories from your plan, in display order
        $categories = [
            ['name' => 'Wall Décor',        'slug' => 'wall-decor'],
            ['name' => 'Mirrors',           'slug' => 'mirrors'],
            ['name' => 'Islamic Décor',     'slug' => 'islamic-decor'],
            ['name' => 'Wall Clocks',       'slug' => 'wall-clocks'],
            ['name' => 'Decorative Pieces', 'slug' => 'decorative-pieces'],
            ['name' => 'Organizers',        'slug' => 'organizers'],
            ['name' => 'Personalized Décor', 'slug' => 'personalized-decor'],
        ];

        foreach ($categories as $i => $data) {
            // updateOrCreate: updates the row if the slug exists, creates it if not.
            // So running the seeder twice never makes duplicates.
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'is_active' => true, 'sort_order' => $i + 1]
            );
        }
    }
}

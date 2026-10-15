<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        // The ten categories from your roadmap, in display order
        $names = [
            'Living Room',
            'Bedroom',
            'Kitchen',
            'Wall Décor',
            'Lighting',
            'Curtains',
            'Small Spaces',
            'Budget Décor',
            'DIY',
            'Colors & Design',
        ];

        foreach ($names as $i => $name) {
            // updateOrCreate: running this twice never makes duplicates
            BlogCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i + 1]
            );
        }
    }
}

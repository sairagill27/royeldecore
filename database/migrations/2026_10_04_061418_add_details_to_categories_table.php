<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add each column only if it is missing
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name'); // URL name
            }
            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('categories', 'image')) {
                $table->string('image')->nullable();                         // photo, empty for now
            }
            if (! Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);                 // hide without deleting
            }
            if (! Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);           // display order
            }
        });

        // Give your existing rows a slug: "Wall Decor" becomes "wall-decor"
        DB::table('categories')->whereNull('slug')->get()->each(function ($row) {
            DB::table('categories')->where('id', $row->id)
                ->update(['slug' => Str::slug($row->name)]);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description', 'image', 'is_active', 'sort_order']);
        });
    }
};

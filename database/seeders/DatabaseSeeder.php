<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@stockpilot.test'],
            ['name' => 'Admin', 'password' => 'password'],
        );

        if (Category::count() === 0) {
            Category::factory(5)
                ->has(Product::factory()->count(10))
                ->create();
        }
    }
}

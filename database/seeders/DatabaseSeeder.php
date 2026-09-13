<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach ([['name' => 'Ideas', 'color' => '#d96745'], ['name' => 'Reading', 'color' => '#6d8b74'], ['name' => 'Work', 'color' => '#58718a']] as $category) {
            Category::create(['name' => $category['name'], 'slug' => str($category['name'])->slug(), 'color' => $category['color']]);
        }

        $this->call(BangladeshiUsersSeeder::class);
    }
}

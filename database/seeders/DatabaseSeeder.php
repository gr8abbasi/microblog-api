<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Factories bypass $fillable — password can be set directly here
        $testUser = User::factory()->create([
            'name'     => 'Kashif Abbasi',
            'username' => 'kashif',
            'email'    => 'kashif@example.com',
            'password' => Hash::make('password'),
        ]);

        Post::factory()->count(10)->create(['user_id' => $testUser->id]);

        User::factory(9)
            ->has(Post::factory()->count(5))
            ->create();
    }
}
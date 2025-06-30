<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $response = Http::withoutVerifying()->get('https://jsonplaceholder.typicode.com/users');

        $users = $response->json();

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'website' => $userData['website'],
            ]);
        }
    }
}

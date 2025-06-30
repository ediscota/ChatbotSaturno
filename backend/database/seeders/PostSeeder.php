<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Seeder;
use App\Models\Post;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chiamata all'API esterna senza SSL
        $response = Http::withoutVerifying()->get('https://jsonplaceholder.typicode.com/posts');


        $posts = $response->json();

        foreach ($posts as $postData) {
            Post::create([
                'userId' => $postData['userId'],
                'title' => $postData['title'],
                'body' => $postData['body'],
            ]);
        }
    }
}

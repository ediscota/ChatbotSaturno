<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use Illuminate\Support\Facades\Http;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $response = Http::withoutVerifying()->get('https://jsonplaceholder.typicode.com/comments');
        $comments = $response->json();

        foreach ($comments as $commentData) {
            Comment::create([
                'postId' => $commentData['postId'],
                'name' => $commentData['name'],
                'email' => $commentData['email'],
                'body' => $commentData['body'],
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{

    public function index()
    {
        return response()->json(['chats' => Chat::all()]);
    }

    public function store(Request $request)
    {
        $chat = Chat::create(['title' => $request->input('title', 'Nuova Chat'),]);
        return response()->json(['chat' => $chat], 200);
    }

}

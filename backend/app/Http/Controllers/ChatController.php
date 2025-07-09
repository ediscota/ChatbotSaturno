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

    //implementa la post per creare altre chat in futuro

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryController extends Controller
{
    public function execute(Request $request)
    {
        // fai poi in modo che non puoi eseguire delete ecc
        $query = $request->input('query');
        $result = DB::select($query);
        return response()->json(['result' => $result]);
    }
}

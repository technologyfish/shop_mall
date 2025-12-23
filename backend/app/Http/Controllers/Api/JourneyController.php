<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journey;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function index()
    {
        // 按照时间/排序获取所有 Journey 记录
        $journeys = Journey::orderBy('sort', 'desc')
            ->orderBy('id', 'asc') // 或者按时间排序 if 'year' allows
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $journeys
        ]);
    }
}







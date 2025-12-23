<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JourneyController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 20);
        $journeys = Journey::orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $journeys
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $journey = Journey::create($request->all());
            return response()->json([
                'code' => 0,
                'message' => 'Journey created successfully',
                'data' => $journey
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'sometimes|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $journey = Journey::findOrFail($id);
            $journey->update($request->all());
            return response()->json([
                'code' => 0,
                'message' => 'Journey updated successfully',
                'data' => $journey
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            Journey::findOrFail($id)->delete();
            return response()->json(['code' => 0, 'message' => 'Journey deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}







<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SizeController extends Controller
{
    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'size_group_id' => 'required|exists:size_groups,id',
                'value' => 'required|string|max:255',
                'sort_order' => 'nullable|integer',
            ]);

            $size = Size::create([
                'size_group_id' => $request->size_group_id,
                'value' => $request->value,
                // 'sort_order' => $request->sort_order,
            ]);

            DB::commit();
            return response()->json([

                'status' => 201,
                'message' => 'Size created successfully!',
                'size' => $size
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show()
    {
        $size = Size::all(); // Fetch all size

        return response()->json(
            [
                'status' => 200,
                'message' => 'Size get successfully!',
                'size' => $size
            ],
            200
        );
    }

    public function showBySizeGroupID(Request $request)
    {
        $request->validate([
            'size_group_id' => 'required|exists:size_groups,id',
        ]);

        // $size = Size::where('size_group_id', 1)
        //     ->orderBy('sort_order', 'asc') // Recommended: keeps your XL/M/S in order
        //     ->get();

        $size = Size::where('size_group_id', $request->size_group_id)->get();

        return response()->json(
            [
                'status' => 200,
                'message' => 'Size retrieved successfully!',
                'size' => $size
            ],
            200
        );
    }

    public function showByColorID(Request $request)
    {
        $request->validate([
            'color_id' => 'required|exists:colors,id',
        ]);

        // $size = Size::where('size_group_id', 1)
        //     ->orderBy('sort_order', 'asc') // Recommended: keeps your XL/M/S in order
        //     ->get();

        $size = Size::where('id', $request->color_id)->get();

        return response()->json(
            [
                'status' => 200,
                'message' => 'Size retrieved successfully!',
                'size' => $size
            ],
            200
        );
    }
}

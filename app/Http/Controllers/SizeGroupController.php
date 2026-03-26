<?php

namespace App\Http\Controllers;

use App\Models\SizeGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SizeGroupController extends Controller
{
    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $sizeGroup = SizeGroup::create([
                'name' => $request->name,
            ]);

            DB::commit();
            return response()->json([

                'status' => 201,
                'message' => 'Size Group created successfully!',
                'sizeGroup' => $sizeGroup
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
        $sizeGroup = SizeGroup::all(); // Fetch all size group

        return response()->json(
            [
                'status' => 200,
                'message' => 'Size Group get successfully!',
                'sizeGroup' => $sizeGroup
            ],
            200
        );
    }
}

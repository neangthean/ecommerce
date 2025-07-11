<?php

// app/Http/Controllers/CategoryController.php

namespace App\Http\Controllers;

use App\Models\Category; // Assuming you have a Category model
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name', // Category name must be unique
                'description' => 'nullable|string', // Optional description
                'image_url' => 'nullable|string',
            ]);

            // Create the new category
            $category = Category::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                "image_url" => $validatedData['image_url'] ?? null,
            ]);

            DB::commit(); // Commit the transaction if successful

            // Return a success response with the created category
            return response()->json(
                [
                    'status' => 201,
                    'message' => 'Category created successfully!',
                    'category' => $category
                ],
                201
            ); // 201 Created status code
        } catch (ValidationException $e) {
            DB::rollBack(); // Rollback transaction on validation error
            // Return validation errors
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity status code
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on any other error
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while creating the category.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error status code
        }
    }

    public function index()
    {
        $categories = Category::all(); // Fetch all categories

        return response()->json(
            [
                'status' => 200,
                'categories' => $categories
            ],
            200
        );
    }

    public function show(Category $category)
    {
        return response()->json(
            [
                'status' => 200,
                'category' => $category
            ],
            200
        );
    }

    public function update(Request $request, Category $category)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
                'description' => 'nullable|string',
            ]);

            $category->update($validatedData);
            DB::commit();

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Category updated successfully!',
                    'category' => $category
                ],
                200
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(Category $category)
    {
        DB::beginTransaction();
        try {
            $category->delete();
            DB::commit();
            return response()->json(
                [
                    'status' => 204,
                    'message' => 'Category deleted successfully!'
                ],
                204
            ); // 204 No Content
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

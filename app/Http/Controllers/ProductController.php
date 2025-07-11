<?php

// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'subTitle' => 'nullable|string|max:255',
                'discount' => 'nullable|numeric|min:0|max:999.99',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0|max:999999.99',
                'product_image' => 'nullable|url|max:255',
                'colors' => 'nullable|array', // Now an array of objects
                'colors.*.name' => 'required|string|max:50', // Each color object must have a 'name'
                'colors.*.image_url' => 'nullable|url|max:255', // Each color object can have an 'image_url'
            ]);

            $product = Product::create([
                'title' => $validatedData['title'],
                'subTitle' => $validatedData['subTitle'] ?? null,
                'discount' => $validatedData['discount'] ?? 0.00,
                'category_id' => $validatedData['category_id'],
                'price' => $validatedData['price'],
                'product_image' => $validatedData['product_image'] ?? null,
            ]);

            $pivotData = [];
            if (!empty($validatedData['colors'])) {
                foreach ($validatedData['colors'] as $colorData) {
                    $colorName = $colorData['name'];
                    $imageUrl = $colorData['image_url'] ?? null;

                    // Find existing color or create new one
                    $color = Color::firstOrCreate(['name' => $colorName]);

                    // Prepare data for the pivot table
                    $pivotData[$color->id] = ['image_url' => $imageUrl];
                }
                // Attach colors with their specific image URLs to the product
                // it will insert data to product_color table
                $product->colors()->attach($pivotData); // function colors in Product model
            }

            DB::commit();

            $product->load('colors'); // Eager load colors with pivot data
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Product created successfully!',
                    'product' => $product
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
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $products = Product::with('colors'); // Eager load colors with pivot data

        // Optional: Filter products by category ID.
        // Example usage in URL: /api/products?category_id=1
        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');
            $products->where('category_id', $categoryId);
        }

        // // Example: Filter by color name (e.g., ?color=red)
        // if ($request->has('color')) {
        //     $colorName = $request->input('color');
        //     $products->whereHas('colors', function ($query) use ($colorName) {
        //         $query->where('name', $colorName);
        //     });
        // }
        // // Example: Filter by multiple colors (e.g., ?colors[]=red&colors[]=blue)
        // if ($request->has('colors') && is_array($request->input('colors'))) {
        //     $colorNames = $request->input('colors');
        //     foreach ($colorNames as $colorName) {
        //         $products->whereHas('colors', function ($query) use ($colorName) {
        //             $query->where('name', $colorName);
        //         });
        //     }
        // }
        // // Example: Filter by image URL (less common, but possible)
        // if ($request->has('image_url')) {
        //     $imageUrl = $request->input('image_url');
        //     $products->whereHas('colors', function ($query) use ($imageUrl) {
        //         $query->wherePivot('image_url', $imageUrl); // Use wherePivot for pivot table columns
        //     });
        // }


        return response()->json(
            [
                'status' => 200,
                'products' => $products->get()
            ],
            200
        );
    }

    public function show(Product $product)
    {
        // Eager load colors and their pivot data (including image_url)
        $product->load('colors');

        return response()->json(
            [
                'status' => 200,
                'product' => $product
            ],
            200
        );
    }

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'subTitle' => 'nullable|string|max:255',
                'discount' => 'nullable|numeric|min:0|max:999.99',
                'category_id' => 'sometimes|required|exists:categories,id',
                'price' => 'sometimes|required|numeric|min:0|max:999999.99',
                'product_image' => 'nullable|url|max:255',
                'colors' => 'nullable|array',
                'colors.*.name' => 'required|string|max:50',
                'colors.*.image_url' => 'nullable|url|max:255',
            ]);

            $product->update($validatedData);

            if (array_key_exists('colors', $validatedData)) {
                $pivotData = [];
                if (!empty($validatedData['colors'])) {
                    foreach ($validatedData['colors'] as $colorData) {
                        $colorName = $colorData['name'];
                        $imageUrl = $colorData['image_url'] ?? null;

                        $color = Color::firstOrCreate(['name' => $colorName]);
                        $pivotData[$color->id] = ['image_url' => $imageUrl];
                    }
                }
                // Sync the colors with their specific image URLs.
                // This will attach new, detach removed, and update existing pivot data.
                // it will insert or update data in product_color table
                $product->colors()->sync($pivotData);
            }

            DB::commit();

            $product->load('colors'); // Eager load colors with updated pivot data
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Product updated successfully!',
                    'product' => $product
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
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            // onDelete('cascade') in migrations handles pivot table entries
            $product->delete();
            DB::commit();
            return response()->json(
                [
                    'status' => 204,
                    'message' => 'Product deleted successfully!'
                ],
                204
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

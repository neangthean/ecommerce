<?php

namespace App\Http\Controllers;

use App\Models\AutoSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AutoSliderController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                // 'image_url' => 'required|string|max:255',
                // 'image_url' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
                'title' => 'required|string|max:255',
                'sub_title' => 'required|string|max:255',
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'status' => 401,
                        'error' => $validator->errors()
                    ],
                    401
                );
            }
            $data = $request->all();
            if ($request->hasFile('image_url')) {
                $image = $request->file('image_url');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/sliders'); // folder name is sliders
                $image->move($destinationPath, $name);
                $data['image_url'] = $name;
            }
            $autoSlider = AutoSlider::create($data);
            DB::commit();
            return response()->json(
                [
                    'status' => 200,
                    'autoSliderData' => $autoSlider,
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

    public function index()
    {
        try {
            DB::beginTransaction();
            $autoSlider = AutoSlider::all();
            DB::commit();
            return response()->json(
                [
                    'status' => 200,
                    'autoSliderData' => $autoSlider,
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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\OtpMail;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{

    // Register user
    public function register(Request $request)
    {
        try {
            // require some column when we input data
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email',
                'password' => 'required',
                'c_password' => 'required|same:password',
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

            $input = $request->all();

            $user = User::where('email', $input['email'])->first();
            // Check if the user exists
            // if ($user) {
            //     return response()->json(
            //         [
            //             'status' => 400,
            //             'message' => 'An account is already created.'
            //         ],
            //         400
            //     );
            // }

            if ($request->hasFile('profile_url')) {
                $image = $request->file('profile_url');
                $name = time() . '.' . $image->getClientOriginalExtension();
                // $destinationPath = public_path('/users'); // folder name is users
                // $image->move($destinationPath, $name);
                // $input['profile_url'] = $name;

                // Storage::disk('s3')->put('images', $image, $name);
                // // Get the public URL of the uploaded file
                // $filePath = 'images/' . $name;
                // $imageUrl = Storage::disk('s3')->url($filePath);

                // Laravel-native S3 upload with visibility
                // $path = $image->storeAs('images', $name, [
                //     'disk' => 's3',
                //     'visibility' => 'public',
                // ]);
                // $imageUrl = Storage::disk('s3')->url($path);


                // Store the file in S3 in the "images" directory with public visibility
                $filePath = 'images/' . $name;
                Storage::disk('s3')->put($filePath, file_get_contents($image), 'public');
                // Get the public URL of the uploaded file
                $imageUrl = Storage::disk('s3')->url($filePath);
                $input['profile_url'] = $imageUrl;
            }

            // Generate a 6-digit OTP.
            $input['otp'] = Str::random(6); // You can use rand(100000, 999999) for numeric OTP

            // Calculate OTP expiration time (e.g., 10 minutes from now).
            $input['otp_expires_at'] = Carbon::now()->addMinutes(10);

            $input['password'] = bcrypt($input['password']);

            // Check if the user exists
            if ($user) {
                // greaterThan is the same isAfter
                // lessThan is the same isBefore
                // check verify otp
                if ($user->otp_expires_at && Carbon::now()->greaterThan($user->otp_expires_at)) {
                    $user->update($input);
                } elseif ($user->otp_expires_at && Carbon::now()->lessThan($user->otp_expires_at)) {
                    return response()->json(
                        [
                            'status' => 400,
                            'message' => "An account is already created, but didn't verify. please! wait 10 minutes and then you can register again."
                        ],
                        400
                    );
                } else {
                    return response()->json(
                        [
                            'status' => 400,
                            'message' => 'An account is already created.'
                        ],
                        400
                    );
                }
            } else {
                // insert to database
                $user = User::create($input);
                // $token = $user->createToken('MyApp')->accessToken;
            }

            // Send the OTP email to the registered user.
            Mail::to($user->email)->send(new OtpMail($input['otp']));

            return response()->json(
                [
                    'status' => 200,
                    'user' => $user,
                    // 'token' => $token,
                ],
                200
            );
        } catch (ValidationException $e) {
            // Handle validation errors.
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Resend OTP for email verification
    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
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

            $input = $request->all();

            // Find the user by email
            $user = User::where('email', $input['email'])->first();

            // Check if the user's email is already verified.
            if ($user->email_verified_at) {
                return response()->json(
                    [
                        'status' => 400,
                        'message' => 'Email is already verified.'
                    ],
                    400
                );
            }

            // Generate a 6-digit OTP.
            $otp = Str::random(6); // You can use rand(100000, 999999) for numeric OTP

            // Calculate OTP expiration time (e.g., 10 minutes from now).
            $otpExpiresAt = Carbon::now()->addMinutes(10);

            // Update the user's OTP and expiration time.
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
            ]);

            // Send the new OTP email.
            Mail::to($user->email)->send(new OtpMail($otp));

            // Return a success response.
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'New OTP sent to your email for verification.'
                ],
                200
            );
        } catch (ValidationException $e) {
            // Handle validation errors.
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred while resending OTP.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Verify the email using the provided OTP.
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required',
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

            $input = $request->all();
            // $input['email'] is $request->email
            // $input['otp'] is $request->otp

            // Find the user by email
            $user = User::where('email', $input['email'])->first();

            // Check if the user exists and the OTP matches
            if (!$user || $user->otp !== $input['otp']) {
                return response()->json(
                    [
                        'status' => 400,
                        'message' => 'Invalid OTP.'
                    ],
                    400
                );
            }

            // Check if the OTP has expired
            if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
                return response()->json(
                    [
                        'status' => 400,
                        'message' => 'OTP has expired. Please request a new one.'
                    ],
                    400
                );
            }

            // Mark the email as verified and clear the OTP fields
            $user->update(
                [
                    'email_verified_at' => Carbon::now(),
                    'otp' => null,
                    'otp_expires_at' => null,
                ]
            );

            // create token when verify success.
            $token = $user->createToken('MyApp')->accessToken;

            return response()->json(
                [
                    'status' => 200,
                    // 'message' => 'Email verified successfully.',
                    'user' => $user,
                    'token' => $token,
                ],
                200
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation Error',
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during OTP verification.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    // Login user
    public function login(Request $request)
    {
        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                // Check if already verified email, It will login
                if (!$user->email_verified_at) {
                    return response()->json(
                        [
                            'status' => 400,
                            'message' => "Email didn't verify."
                        ],
                        400
                    );
                }
                $token = $user->createToken('MyApp')->accessToken;

                return response()->json(
                    [
                        'status' => 200,
                        'user' => $user,
                        'token' => $token,
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'status' => 401,
                        'error' => 'Unauthorised',
                    ],
                    401
                );
            }
        } catch (ValidationException $e) {
            // Handle validation errors.
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred while login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get current account user
    public function me()
    {
        try {
            $user = Auth::user();
            return response()->json(
                [
                    'status' => 200,
                    'user' => $user
                ],
                200
            );
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred while get current logged.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Logout user
    public function logout()
    {
        try {
            $user = Auth::user()->token();
            $user->revoke(); // Log out by revoke token
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Successfully logged out'
                ],
                200
            );
        } catch (ValidationException $e) {
            // Handle validation errors.
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred while log out.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function updateProfile(Request $request)
    {
        try {
            // make sure "confirm password" is the same "password"
            $validator = Validator::make($request->all(), [
                'email' => 'sometimes|required|email',
                'password' => 'sometimes|required',
                'c_password' => 'required_with:password|same:password',
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
            $user = Auth::user();

            if ($user != null) {
                // if ($request->hasFile('profile_url')) {
                //     $image = $request->file('profile_url');
                //     $name = time() . '.' . $image->getClientOriginalExtension();
                //     $destinationPath = public_path('/users');
                //     $image->move($destinationPath, $name);
                //     $data['profile_url'] = $name;
                //     $oldImage = $user->profile_url;
                // }
                // $user->update($data);
                // $destinationPath = public_path('/users');
                // // After update new image and then delete old image
                // if (file_exists($destinationPath . '/' . $oldImage)) {
                //     unlink($destinationPath . '/' . $oldImage);
                // }

                if ($request->hasFile('profile_url')) {
                    $image = $request->file('profile_url');
                    $name = time() . '.' . $image->getClientOriginalExtension();
                    $filePath = 'images/' . $name;

                    // Optional: delete old image
                    if ($user->profile_url) {
                        $oldPath = parse_url($user->profile_url, PHP_URL_PATH);
                        if ($oldPath) {
                            Storage::disk('s3')->delete(ltrim($oldPath, '/'));
                        }
                    }

                    // Upload new image to S3
                    Storage::disk('s3')->put($filePath, file_get_contents($image), 'public');
                    $imageUrl = Storage::disk('s3')->url($filePath);
                    $data['profile_url'] = $imageUrl;
                }

                // Update user
                $user->update($data);

                return response()->json(
                    [
                        'status' => 200,
                        'user' => $user
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'status' => 401,
                        'error' => 'Unauthorised'
                    ],
                    401
                );
            }
        } catch (ValidationException $e) {
            // Handle validation errors.
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other unexpected errors.
            return response()->json([
                'message' => 'An error occurred while update user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

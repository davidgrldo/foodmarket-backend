<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request)
    {
        try {
            // Set validation
            $validator = Validator::make($request->all(), [
                'email' => 'email|required',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            // Credentials checking (login)
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized.'
                ], 'Authentication Failed.', 500);
            }

            // If hash doesn't match
            $user = User::where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials.');
            }
            // If auth success
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated.');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Authentication Failed.', 500);
        }
    }

    public function register(Request $request)
    {
        try {
            // Set validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => $this->passwordRules()
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Something went wrong.',
                    'error' => $validator->errors()
                ]);
            }

            // Create credentials
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'house_number' => $request->house_number,
                'phone_number' => $request->phone_number,
                'city' => $request->city,
                'password' => Hash::make($request->password),
            ]);

            // Generate token for user
            $user = User::where('email', $request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong.',
                'error' => $error,
            ], 'Authentication Failed.', 500);
        }
    }

    public function logout(Request $request)
    {
        // Delete user token
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, 'Token revoked.');
    }

    public function fetch(Request $request)
    {
        // Retrieve profile data
        return ResponseFormatter::success($request->user(), 'Profile data is retrieved successfuly.');
    }

    public function updateProfile(Request $request)
    {
        // Retrieve data
        $data = $request->all();

        // Update data
        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Updated.');
    }

    public function uploadPhoto(Request $request)
    {
        // Set validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        // If fails
        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'Failed to update photo.', 401);
        }

        // If success
        if ($request->file('file')) {
            // Set assets location
            $file = $request->file->store('assets/user', 'public');

            // Update profile picture (URL) to database
            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([
                $file
            ], 'Photo updated successfully.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'string', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9_]/'],
            'business_name' => ['nullable', 'string', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_cac_registered' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $formattedErrors = [];

            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = $messages[0]; // Take the first error message for each field
            }

            return response()->json($formattedErrors, 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'business_name' => $request->business_name,
            'phone' => $request->phone,
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'is_admin' => false,
            'is_cac_registered' => $request->is_cac_registered,
            'unique_identifier' => Str::random(5),
        ]);

        $whatsappChatLink = $this->generateWhatsAppChatLink($user);
        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message' => 'Registration successful!',
            'user' => $user,
            'token' => $token,
            'uniqueLink' => $whatsappChatLink,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->accessToken;

            return response()->json([
                'message' => 'Login successful!',
                'user' => $user,
                'token' => $token,
            ], 200);
        } else {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (!$user->is_admin) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message' => 'Admin login successful!',
            'token' => $token,
        ], 200);
    }

    private function generateWhatsAppChatLink(User $user)
    {
        $queryParams = http_build_query([
            '0' => $user->unique_identifier,
        ]);

        $whatsappNumber = '2348103982074';
        $whatsappChatLink = 'https://wa.me/' . $whatsappNumber . '?' . $queryParams;

        return $whatsappChatLink;
    }
}

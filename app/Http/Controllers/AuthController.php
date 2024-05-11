<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Exception;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

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
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
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
            'unique_identifier' => Str::random(5), 
          ]);
        
          $whatsappChatLink = $this->generateWhatsAppChatLink($user);

          $token = $user->createToken('auth_token')->accessToken;
        
          
        
          return response()->json([
            'user' => $user,
            'token' => $token,
            'uniqueLink' => $whatsappChatLink, 
            'redirect' => '/account',
          ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->accessToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'redirect' => '/home', 
            ], 201);
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
            return response()->json(['errors' => $validator->errors()], 422);
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
            'token' => $token,
            'redirect' => '/dashboard', 
        ], 201);
    }

    public function generateWhatsAppChatLink(User $user)
    {
    // Construct URL with user information as query parameters
    $queryParams = http_build_query([
        $user->id,
        $user->unique_identifier,
        $user->business_name,
        
    
    ]);

    // WhatsApp number associated with your Infobip Answers chatbot
    $whatsappNumber = '447860099299';

    // Construct WhatsApp chat link
    $whatsappChatLink = 'https://wa.me/' . $whatsappNumber . '?' . $queryParams;

    return $whatsappChatLink;
}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\Log;

class ZikorController extends Controller

{
    public function interactWithAI(Request $request)
    {
        // Send user input to OpenAI
        $response = app(\App\Services\OpenAIService::class)->sendMessage($request->input('user_input'));

        // Process the response as needed
        $aiResponse = $response['choices'][0]['text'];

        return response()->json(['response' => $aiResponse]);
    }

    public function validateBusinessName(Request $request)
    {
        $businessName = $request->get('business_name');

        $user = User::where('business_name', $businessName)->first();

        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'Business not found'], 404);
        }
    }
    
}

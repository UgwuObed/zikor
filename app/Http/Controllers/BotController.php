<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function handleUserMessage(Request $request)
    {
        // Extract user ID and bot ID from the request
        $userId = $request->input('user_id');
        $botId = $request->input('bot_id');

        // Pass user message and details to Rasa bot
        $this->sendToRasaBot($request->input('message'), $userId, $botId);
    }

    public function sendToRasaBot($message, $userId, $botId)
    {
        // Send user message and user details to Rasa bot
        $response = Http::post('http://rasa-bot-server/webhooks/rest/webhook', [
            'message' => $message,
            'user_id' => $userId,
            'bot_id' => $botId,
        ]);

        // Handle Rasa bot response
        $this->handleRasaResponse($response->json());
    }

    public function handleRasaResponse($rasaResponse)
    {
        // Extract product information from Rasa bot response
        $productName = $rasaResponse['product_name'];
        $productPrice = $rasaResponse['product_price'];
        // Extract other relevant product information...

        // Format the response for the user
        $formattedResponse = "Product: $productName\nPrice: $productPrice";
        
        // Send the response back to the user via the bot instance created in Laravel
        $this->sendResponseToUser($formattedResponse);
    }

     public function sendResponseToUser($response)
    {
        // Log the response for debugging
        Log::info("Response to user: $response");

        // Return the response to the user via the bot instance created in Laravel
        return response()->json(['message' => $response]);
    }
}

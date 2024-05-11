<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;
use InvalidArgumentException;
use Infobip\Api\WhatsAppApi;
use Infobip\Configuration;

class ChatController extends Controller
{
    public function initiateChat($uniqueIdentifier)
    {
        // Extract user identifier from the URL
        $userId = $uniqueIdentifier;
    
        $infobipApiKey = env('INFOBIP_API_KEY');
        $infobipBaseUrl = '9l9kyd.api.infobip.com';
    
        // Prepare webhook URL (replace with your actual webhook endpoint URL)
        $webhookUrl = route('chat.webhook', $userId);
    
        // Prepare data for Infobip API request (no customer phone number)
        $data = [
            'from' => [  
                'phoneNumber' => '447860099299'
            ],
            'webhookUrl' => $webhookUrl,
        ];
    
        // Create a Guzzle HTTP client
        $client = new Client([
            'auth' => [$infobipApiKey],
            'base_uri' => $infobipBaseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    
        try {
            // Log API request data before sending
            $logData = [
                'userId' => $userId,
                'infobipApiKey' => $infobipApiKey, 
                'infobipBaseUrl' => $infobipBaseUrl,
                'webhookUrl' => $webhookUrl,
                'data' => $data,
            ];
            Log::debug('Initiating chat session with Infobip', $logData);
    
            // Send POST request to Infobip API to initiate chat session
            $response = $client->post('', ['json' => $data]);
    
            // Log API response
            Log::info('Infobip API response:', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
    
            if ($response->getStatusCode() === 201) {
                // Chat session initiated successfully
                return response()->json(['message' => 'Chat session initiated']);
            } else {
                // Handle error from Infobip API
                $error = json_decode($response->getBody(), true);
                $errorMessage = $error['errorMessage'] ?? 'Unknown error from Infobip API';
                return response()->json(['error' => $errorMessage], $response->getStatusCode());
            }
        } catch (\Exception $e) {
            // Handle other errors during communication with Infobip API
            Log::error('Error initiating chat session:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to initiate chat session'], 500);
        }
    }
    
    public function handleWebhook(Request $request, $uniqueIdentifier)
    {
        try {
            // Extract user identifier from the URL
            $userId = $uniqueIdentifier;
            
            // Process the incoming webhook data from Infobip
            $infobipData = $request->json()->all();
            
            // Verify that the webhook data structure matches our assumptions
            if (!isset($infobipData['eventType']) || !isset($infobipData['messages'])) {
                throw new InvalidArgumentException('Invalid webhook data structure');
            }
            
            // Check the data type (refer to Infobip's documentation for actual property names)
            if ($infobipData['eventType'] === 'USER_SENT_MESSAGE') {
                // Extract the message content from the user
                $message = $infobipData['messages'][0]['text'];
                
                // Retrieve the user's products from the database
                $userProducts = Product::where('user_id', $userId)->get();
                
                // Respond with relevant product information
                $responseMessage = "Here are our products: \r\n";
                foreach ($userProducts as $product) {
                    $responseMessage .= $product->name . " - " . $product->description . "\r\n";
                }
                
                // Send the response message through Infobip's API
                $this->sendInfobipMessage($userId, $responseMessage);
                return response()->json(['message' => 'Webhook received and processed'], 200);
            } else {
                // Handle other webhook data types (e.g., session ended)
                // Implement logic based on the specific data type
                return response()->json(['message' => 'Webhook received (unknown type)'], 200);
            }
        } catch (Throwable $e) {
            // Add error handling to ensure robustness
            Log::error('Error handling webhook:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process webhook'], 500);
        }
    }
    
    private function sendInfobipMessage($userId, $responseMessage)
    {
        // Implement the sendInfobipMessage method to send responses back to Infobip's API
        // Use the Infobip API client to send the response message
        // ...
    }
    
}
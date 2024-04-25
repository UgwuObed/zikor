<?php 
namespace App\Services;

use App\Models\User;
use App\Models\ChatbotInstance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RasaService
{
    public function notifyRasaAboutInstance(User $user, ChatbotInstance $instance)
    {
        // Make a request to Rasa's API to notify about the new instance
        // Include necessary information such as user ID, instance ID, etc.
        // You may need to use Guzzle or another HTTP client to send the request

        // Example:
        $rasaEndpoint = 'http://0.0.0.0:5005/api/register_instance';
        $requestData = [
            'user_id' => $user->id,
            'instance_id' => $instance->id,
        ];

        $response = Http::post($rasaEndpoint, $requestData);

        // Check if Rasa acknowledges the registration
        if ($response->successful()) {
            // Handle successful registration
            Log::info('Rasa acknowledged the new instance registration.');
        } else {
            // Handle failure
            Log::error('Failed to notify Rasa about the new instance.');
        }
    }
}
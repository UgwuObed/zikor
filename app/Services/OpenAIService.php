<?php

namespace App\Services;

use GuzzleHttp\Client;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function sendMessage($message)
    {
    
        $response = $this->client->post('https://api.openai.com/v1/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo-16k', 
                'prompt' => $message,
                'max_tokens' => 150,
            ],
        ]);

        // Return the response from OpenAI
        return json_decode($response->getBody()->getContents(), true);
    }
}

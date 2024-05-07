<?php

namespace App\Services;

use App\Models\ZikorPhoneNumber;
use Twilio\Rest\Client;

class TwilioService
{
    protected $twilioClient;

    protected $accountSid;
    protected $authToken;

    public function __construct()
    {
        $this->accountSid = env('TWILIO_ACCOUNT_SID');
        $this->authToken = env('TWILIO_AUTH_TOKEN');

         $this->twilioClient = new Client($this->accountSid, $this->authToken);
    }

   
}

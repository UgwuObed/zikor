<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    /**
     * Initialize a payment transaction
     */
    public function initializePayment(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:plans,id',
            'email' => 'required|email',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

 
        $plan = Plan::findOrFail($request->plan_id);

        // If free plan, create subscription without payment
        if ($plan->is_free) {
            $subscription = $this->createSubscription($user->id, $plan->id, 'active', $request->billing_cycle);
            
            return response()->json([
                'message' => 'Free plan activated successfully',
                'subscription' => $subscription
            ], 200);
        }

        // Calculate the amount based on billing cycle
        $amount = $request->billing_cycle === 'monthly' 
            ? $plan->monthly_price * 100 
            : $plan->yearly_price * 100;

        // Generate a unique reference
        $reference = 'PS_' . uniqid() . '_' . time();

        // Prepare callback URL
        $callbackUrl = url('/api/payment/verify');

        // Prepare metadata
        $metadata = [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $request->billing_cycle,
            'custom_fields' => [
                [
                    'display_name' => 'Plan Name',
                    'variable_name' => 'plan_name',
                    'value' => $plan->name
                ],
                [
                    'display_name' => 'Billing Cycle',
                    'variable_name' => 'billing_cycle',
                    'value' => ucfirst($request->billing_cycle)
                ]
            ]
        ];

        // Initialize Paystack transaction
        try {
            $response = Paystack::getAuthorizationUrl([
                'amount' => $amount,
                'email' => $request->email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => json_encode($metadata),
                'currency' => 'NGN',
            ]);

            // Store the reference for verification
            session(['paystack_reference' => $reference]);

            return response()->json([
                'authorization_url' => $response,
                'reference' => $reference
            ], 200);
        } catch (\Exception $e) {
            Log::error('Paystack error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to initialize payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment callback
     */
   /**
 * Verify payment callback
 */
public function verifyPayment(Request $request)
{
    // Get the reference from the URL
    $reference = $request->query('reference');
    if (!$reference) {
        return response()->json(['error' => 'Reference not found'], 400);
    }

    try {
        $paystack = new \Unicodeveloper\Paystack\Paystack();
        $transaction = $paystack->getPaymentData($reference);

        if ($transaction['status'] === true && $transaction['data']['status'] === 'success') {
            // Extract metadata
            $metadata = $transaction['data']['metadata'];
            $userId = $metadata['user_id'];
            $planId = $metadata['plan_id'];
            $billingCycle = $metadata['billing_cycle'];

            // Create a subscription record
            $subscription = $this->createSubscription($userId, $planId, 'active', $billingCycle);

            // Generate access token for front-end redirect
            $user = User::find($userId);
            $token = $user->createToken('auth_token')->accessToken;

            // Change this to redirect to your frontend URL instead of a Laravel route
            $frontendUrl = "https://zikor.shop/plan/verify";
            $successUrl = $frontendUrl . "?payment=success&token=" . $token;
            
            return redirect($successUrl);
        } else {
            $errorMessage = urlencode('Payment was not successful');
            // Change this to redirect to your frontend URL instead of a Laravel route
            $frontendUrl = "https://zikor.shop/plan/verify";
            $errorUrl = $frontendUrl . "?payment=failed&message=" . $errorMessage;
            
            return redirect($errorUrl);
        }
    } catch (\Exception $e) {
        Log::error('Paystack verification error: ' . $e->getMessage());
        $errorMessage = urlencode('An error occurred while verifying your payment');
        // Change this to redirect to your frontend URL instead of a Laravel route
        $frontendUrl = "https://zikor.shop/plan/verify";
        $errorUrl = $frontendUrl . "?payment=failed&message=" . $errorMessage;
        
        return redirect($errorUrl);
    }
}
    /**
     * Create a subscription record
     */
    private function createSubscription($userId, $planId, $status, $billingCycle)
    {
        $startDate = Carbon::now();
        $endDate = $billingCycle === 'monthly' 
            ? Carbon::now()->addMonth() 
            : Carbon::now()->addYear();

        return Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => $status,
            'billing_cycle' => $billingCycle,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_billing_date' => $endDate,
        ]);
    }

    /**
     * Get user subscription status
     */
    public function getSubscription(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $subscription = $user->activeSubscription();
        
        return response()->json([
            'has_subscription' => !is_null($subscription),
            'subscription' => $subscription ? [
                'plan' => $subscription->plan->name,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'start_date' => $subscription->start_date->format('Y-m-d'),
                'end_date' => $subscription->end_date->format('Y-m-d'),
                'days_remaining' => $subscription->end_date->diffInDays(Carbon::now()),
            ] : null
        ]);
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        
        if (!$this->verifySignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        
        switch ($event['event']) {
            case 'charge.success':
                $this->handleChargeSuccess($event['data']);
                break;
            case 'subscription.create':
                $this->handleSubscriptionCreated($event['data']);
                break;
            case 'subscription.disable':
                $this->handleSubscriptionDisabled($event['data']);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature($payload, $signature)
    {
        $secret = config('paystack.secretKey');
        
        $hash = hash_hmac('sha512', $payload, $secret);
        
        return hash_equals($hash, $signature);
    }

    /**
     * Handle charge.success event
     */
    private function handleChargeSuccess($data)
    {
    
        if (isset($data['metadata']['user_id']) && isset($data['metadata']['plan_id'])) {
            $userId = $data['metadata']['user_id'];
            $planId = $data['metadata']['plan_id'];
            $billingCycle = $data['metadata']['billing_cycle'] ?? 'monthly';
            
       
            Log::info('Charge success for user ' . $userId . ' on plan ' . $planId);
            
 
            $subscription = Subscription::where('user_id', $userId)
                ->where('plan_id', $planId)
                ->where('status', 'active')
                ->first();
            
            if (!$subscription) {
       
                $this->createSubscription($userId, $planId, 'active', $billingCycle);
            }
        }
    }

    /**
     * Handle subscription.create event
     */
    private function handleSubscriptionCreated($data)
    {
        // Extract customer and subscription details
        if (isset($data['customer']) && isset($data['subscription_code'])) {
            $email = $data['customer']['email'];
            $subscriptionCode = $data['subscription_code'];
            
            // Find user by email
            $user = User::where('email', $email)->first();
            
            if ($user) {
                // Update subscription with Paystack details
                $subscription = $user->activeSubscription();
                
                if ($subscription) {
                    $subscription->update([
                        'paystack_subscription_code' => $subscriptionCode,
                        'status' => 'active',
                    ]);
                    
                    Log::info('Updated subscription for user ' . $user->id . ' with code ' . $subscriptionCode);
                }
            }
        }
    }

    /**
     * Handle subscription.disable event
     */
    private function handleSubscriptionDisabled($data)
    {
   
        if (isset($data['subscription_code'])) {
            $subscriptionCode = $data['subscription_code'];
            
        
            $subscription = Subscription::where('paystack_subscription_code', $subscriptionCode)->first();
            
            if ($subscription) {
                $subscription->update([
                    'status' => 'cancelled',
                ]);
                
                Log::info('Disabled subscription with code ' . $subscriptionCode);
            }
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $subscription = $user->activeSubscription();
        
        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found'], 404);
        }

        // If there's a Paystack subscription code, cancel it on Paystack
        if ($subscription->paystack_subscription_code) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('paystack.secretKey'),
                ])->post('https://api.paystack.co/subscription/disable', [
                    'code' => $subscription->paystack_subscription_code,
                    'token' => $subscription->paystack_email_token, 
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to cancel Paystack subscription: ' . $response->body());
                    return response()->json(['error' => 'Could not cancel subscription on Paystack'], 500);
                }
            } catch (\Exception $e) {
                Log::error('Paystack cancellation error: ' . $e->getMessage());
                return response()->json(['error' => 'An error occurred'], 500);
            }
        }

    
        $subscription->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Paystack;
use App\Models\PendingPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    protected $paystackSecretKey;
    
    public function __construct()
    {
        $this->paystackSecretKey = config('services.paystack.secret_key');
    }
    
    /**
     * Initialize payment
     */

    public function initializePayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'email' => 'required|email',
        ]);
        
        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);
        
        if ($plan->is_free) {
            $this->createSubscription($user, $plan, 'free', $request->billing_cycle);
            return response()->json([
                'message' => 'Free plan activated successfully',
                'redirect_url' => '/store/storefront'
            ]);
        }
        
        $amount = $request->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price;
        $amountInKobo = $amount * 100;
        $reference = 'zik_' . uniqid();
        

        $callbackUrl = route('payment.callback');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amountInKobo,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'custom_fields' => [
                    [
                        'display_name' => "Plan Name",
                        'variable_name' => "plan_name",
                        'value' => $plan->name
                    ],
                    [
                        'display_name' => "Billing Cycle",
                        'variable_name' => "billing_cycle",
                        'value' => $request->billing_cycle
                    ]
                ]
            ]
        ]);
        
        if (!$response->successful()) {
            Log::error('Failed to initialize Paystack payment', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'response' => $response->json()
            ]);
            
            return response()->json([
                'message' => 'Failed to initialize payment',
                'error' => $response->json()
            ], 400);
        }
        
        PendingPayment::create([
            'user_id' => $user->id,
            'plan_id' => $request->plan_id,
            'billing_cycle' => $request->billing_cycle,
            'reference' => $reference,
            'status' => 'pending',
        ]);
    
        return response()->json([
            'authorization_url' => $response->json()['data']['authorization_url'],
            'access_code' => $response->json()['data']['access_code'],
            'reference' => $response->json()['data']['reference'],
        ]);
    }
    
    /**
     * Handle callback from Paystack and automatically verify payment
     */
    public function handlePaymentCallback(Request $request)
{
    $reference = $request->reference;
    $frontendUrl = config('app.frontend_url', 'https://zikor.shop');
    
    if (!$reference) {
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('No reference provided'));
    }

 
    $pendingPayment = PendingPayment::where('reference', $reference)->first();
    
    if (!$pendingPayment) {
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('Invalid payment reference'));
    }

 
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->paystackSecretKey,
        'Content-Type' => 'application/json',
    ])->get('https://api.paystack.co/transaction/verify/' . $reference);
    
    if (!$response->successful()) {
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('Payment verification failed'));
    }
    
    $data = $response->json()['data'];
    
    if ($data['status'] !== 'success') {
        $pendingPayment->status = 'failed';
        $pendingPayment->save();
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('Payment failed'));
    }

    $user = User::find($pendingPayment->user_id);
    $plan = Plan::find($pendingPayment->plan_id);

    if (!$user || !$plan) {
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('Invalid user or plan'));
    }

    try {

        $this->createSubscription(
            $user, 
            $plan, 
            'paystack', 
            $pendingPayment->billing_cycle, 
            [
                'customer_code' => $data['customer']['customer_code'] ?? null,
                'authorization_code' => $data['authorization']['authorization_code'] ?? null
            ]
        );
        
        $pendingPayment->status = 'completed';
        $pendingPayment->save();

        
        return redirect()->to($frontendUrl . '/plan/verify?payment=success');

    } catch (\Exception $e) {
        return redirect()->to($frontendUrl . '/plan?payment=failed&message=' . urlencode('Error: ' . $e->getMessage()));
    }
}

    /**
     * Create subscription record
     */
    protected function createSubscription($user, $plan, $paymentMethod, $billingCycle, $paystackData = [])
    {
        $startDate = now();
        $endDate = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
        
        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'paystack_subscription_code' => $paystackData['subscription_code'] ?? null,
            'paystack_customer_code' => $paystackData['customer_code'] ?? null,
            'authorization_code' => $paystackData['authorization_code'] ?? null,
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_payment_date' => $billingCycle === 'yearly' ? $endDate : null,
            'billing_cycle' => $billingCycle,
            'payment_method' => $paymentMethod,
            'amount' => $billingCycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
        ]);
        

        $user->current_plan_id = $plan->id;
        $user->save();
        
        return $subscription;
    }
    
    
    /**
     * Verify Paystack webhook signature
     */
    protected function verifyPaystackWebhook($signature, $payload)
    {
        $computedSignature = hash_hmac('sha512', json_encode($payload), $this->paystackSecretKey);
        return hash_equals($signature, $computedSignature);
    }
    
    /**
     * Handle successful charge for recurring payment
     */
    protected function handleChargeSuccess($payload)
    {
        $data = $payload['data'];
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;
        
        if (!$subscriptionCode) {
            return response()->json(['status' => 'error', 'message' => 'No subscription code'], 400);
        }
        
        $subscription = Subscription::where('paystack_subscription_code', $subscriptionCode)->first();
        
        if (!$subscription) {
            Log::error('Subscription not found for successful charge', ['subscription_code' => $subscriptionCode]);
            return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
        }
     
        $billingCycle = $subscription->billing_cycle;
        $newEndDate = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
        
        $subscription->update([
            'end_date' => $newEndDate,
            'next_payment_date' => $newEndDate,
            'last_payment_date' => now(),
            'status' => 'active',
        ]);
        
        Log::info('Subscription renewed successfully', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id
        ]);
        
        return response()->json(['status' => 'success']);
    }
    
    /**
     * Handle new subscription creation via webhook
     */
    protected function handleSubscriptionCreated($payload)
    {
        $data = $payload['data'];
        $userEmail = $data['customer']['email'];
        $subscriptionCode = $data['subscription_code'];
        
        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            Log::error('User not found for subscription creation', ['email' => $userEmail]);
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        
        // Update the user's subscription with the subscription code
        Subscription::where('user_id', $user->id)
            ->whereNull('paystack_subscription_code')
            ->update(['paystack_subscription_code' => $subscriptionCode]);
        
        return response()->json(['status' => 'success']);
    }
    
    /**
     * Handle subscription disabled event
     */
    protected function handleSubscriptionDisabled($payload)
    {
        $data = $payload['data'];
        $subscriptionCode = $data['subscription_code'];
        
        $subscription = Subscription::where('paystack_subscription_code', $subscriptionCode)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'inactive',
                'cancelled_at' => now(),
            ]);
            
            Log::info('Subscription disabled via webhook', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);
        }
        
        return response()->json(['status' => 'success']);
    }
    
    /**
     * Get current user's subscription
     */
    public function getCurrentSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
            
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
                'has_subscription' => false
            ]);
        }
        
        return response()->json([
            'has_subscription' => true,
            'subscription' => $subscription,
            'plan' => $subscription->plan,
            'days_remaining' => now()->diffInDays($subscription->end_date, false)
        ]);
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
            
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }
        
        // If it's a Paystack subscription, disable it via API
        if ($subscription->paystack_subscription_code) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subscription/disable', [
                'code' => $subscription->paystack_subscription_code,
                'token' => $subscription->authorization_code
            ]);
            
            if (!$response->successful()) {
                Log::error('Failed to cancel Paystack subscription', [
                    'user_id' => $user->id,
                    'response' => $response->json()
                ]);
                
                return response()->json([
                    'message' => 'Failed to cancel subscription with payment processor',
                    'error' => $response->json()
                ], 400);
            }
        }
        
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        return response()->json(['message' => 'Subscription cancelled successfully']);
    }
    
    /**
     * Verify payment status
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required'
        ]);
        
        $pendingPayment = PendingPayment::where('reference', $request->reference)->first();
        
        if (!$pendingPayment) {
            return response()->json(['message' => 'Payment reference not found'], 404);
        }
        
        return response()->json([
            'status' => $pendingPayment->status,
            'payment' => $pendingPayment
        ]);
    }


    /**
     * Create Paystack subscription for recurring payments
     */
    protected function createPaystackSubscription($user, $plan, $subscription, $authorizationCode)
    {
 
        $paystackPlanId = $plan->paystack_plan_id;
        
        if (!$paystackPlanId) {
            $planResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/plan', [
                'name' => $plan->name . ' (Yearly)',
                'amount' => $plan->yearly_price * 100,
                'interval' => 'yearly',
                'description' => $plan->description,
            ]);
            
            if ($planResponse->successful()) {
                $paystackPlanId = $planResponse->json()['data']['plan_code'];
                $plan->update(['paystack_plan_id' => $paystackPlanId]);
            }
        }
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/subscription', [
            'customer' => $user->email,
            'plan' => $paystackPlanId,
            'authorization' => $authorizationCode,
        ]);
        
        if ($response->successful()) {
            $subscription->update([
                'paystack_subscription_code' => $response->json()['data']['subscription_code'],
                'next_payment_date' => $response->json()['data']['next_payment_date'],
            ]);
        }
    }
    
    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('x-paystack-signature');
        
        if (!$this->verifyPaystackWebhook($signature, $payload)) {
            Log::error('Invalid Paystack webhook signature');
            return response()->json(['status' => 'error'], 403);
        }
        
        $event = $payload['event'];
        
        switch ($event) {
            case 'charge.success':
                return $this->handleChargeSuccess($payload);
            case 'subscription.create':
                return $this->handleSubscriptionCreated($payload);
            case 'subscription.disable':
                return $this->handleSubscriptionDisabled($payload);
            case 'invoice.create':
                return $this->handleInvoiceCreated($payload);
            case 'invoice.update':
                return $this->handleInvoiceUpdated($payload);
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function handleSuccessfulCharge($data)
    {
        
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;
        
        if ($subscriptionCode) {
            $subscription = Subscription::where('paystack_subscription_code', $subscriptionCode)->first();
            
            if ($subscription) {
                $subscription->update([
                    'next_payment_date' => $data['subscription']['next_payment_date'],
                    'end_date' => $data['subscription']['next_payment_date'],
                ]);
            }
        }
    }
    

}

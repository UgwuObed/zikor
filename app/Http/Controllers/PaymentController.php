<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
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
    

    $frontendUrl = env('FRONTEND_URL', config('app.url'));
    $callbackUrl = env('FRONTEND_URL', 'https://zikor.shop') . '/payment/verify/' . $reference;
    
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
    
    PendingPayment::create([
        'user_id' => auth()->id(),
        'plan_id' => $request->plan_id,
        'billing_cycle' => $request->billing_cycle,
        'reference' => $reference,
        'status' => 'pending',
    ]);

    if ($response->successful()) {
        return response()->json([
            'authorization_url' => $response->json()['data']['authorization_url'],
            'access_code' => $response->json()['data']['access_code'],
            'reference' => $response->json()['data']['reference'],
            'callback_url' => $callbackUrl  
        ]);
    }
    
    return response()->json([
        'message' => 'Failed to initialize payment',
        'error' => $response->json()
    ], 400);
}
    
    /**
     * Verify payment and create subscription
     */
    public function verifyPayment($reference)
    {
        $pendingPayment = PendingPayment::where('reference', $reference)->first();
        
        if (!$pendingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'No pending payment found.',
            ], 404);
        }
        
        $user = User::find($pendingPayment->user_id);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->get('https://api.paystack.co/transaction/verify/' . $reference);
        
        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.',
                'error' => $response->json()
            ], 400);
        }
        
        $data = $response->json()['data'];
        
        if ($data['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not successful.',
            ], 400);
        }
        
        $plan = Plan::find($pendingPayment->plan_id);
        
        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.',
            ], 404);
        }
        
        $subscription = $this->createSubscription(
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
        
        return response()->json([
            'success' => true,
            'message' => 'Payment verified and subscription created successfully.',
            'subscription' => $subscription,
            'redirect_url' => '/store/storefront',
        ]);
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
        
        return Subscription::create([
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
        $secret = config('services.paystack.secret_key');
        
    
        if ($signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
            Log::error('Invalid Paystack webhook signature');
            return response()->json(['status' => 'error'], 403);
        }
        
        $event = $payload['event'];
        
        switch ($event) {
            case 'subscription.create':
               
                break;
                
            case 'charge.success':
               
                $this->handleSuccessfulCharge($payload['data']);
                break;
                
            case 'subscription.disable':
               
                $this->handleSubscriptionDisabled($payload['data']);
                break;
                
            case 'invoice.create':
            case 'invoice.update':
           
                break;
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
    
    protected function handleSubscriptionDisabled($data)
    {
        $subscriptionCode = $data['subscription_code'];
        Subscription::where('paystack_subscription_code', $subscriptionCode)
            ->update(['status' => 'inactive']);
    }
}
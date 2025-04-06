<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Skip for admin users
        if ($user->is_admin) {
            return $next($request);
        }
        
        // Check if user has an active subscription
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();
            
        if (!$activeSubscription && !$request->is('plans*', 'payment*', 'logout')) {
            return redirect()->route('plans')->with('error', 'You need an active subscription to access this page');
        }
        
        return $next($request);
    }
}
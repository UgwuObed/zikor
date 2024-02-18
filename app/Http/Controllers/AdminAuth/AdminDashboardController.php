<?php

namespace App\Http\Controllers\AdminAuth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }
    
    public function getUsers()
    {
        $users = User::all();

        // Return JSON response for API request
        if(request()->expectsJson()) {
            return response()->json(['users' => $users]);
        }

        // Return Blade view for browser request
        return view('admin.dashboard', ['users' => $users]);
    }

    public function showUsersWithProducts()
        {
            $usersWithProducts = User::with('products')->get();
            return view('admin.users', compact('usersWithProducts'));
        }

    public function deleteUser($userId)

        {
            $user = User::findOrFail($userId);
            $user->delete();

            return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
        }

        public function deleteProduct($userId, $productId)
        {
            $user = User::findOrFail($userId);
            $product = $user->products()->findOrFail($productId);
            $product->delete();

            return redirect()->back()->with('success', 'Product deleted successfully.');
        }

    
}

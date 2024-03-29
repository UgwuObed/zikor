<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Http\Middleware\AdminAuthorization;
use Illuminate\Support\Facades\Validator;

class AdminDashboardController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(AdminAuthorization::class);
    }

    public function dashboard()
    {
        
        return response()->json(['message' => 'Welcome to the admin dashboard']);
    }

    public function showUsersWithProducts()
    {
        $usersWithProducts = User::with('products')->get();
   
        return response()->json($usersWithProducts);
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function deleteProduct($userId, $productId)
    {
        $user = User::findOrFail($userId);
        $product = $user->products()->findOrFail($productId);
        $product->delete();

   
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function createCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);
    
        $existingCategory = Category::where('name', $request->name)->first();
        if ($existingCategory) {
            return response()->json(['error' => 'Category with this name already exists'], 422);
        }
    
        Category::create($request->all());
    
        return response()->json(['message' => 'Category created successfully'], 201);
    }

    public function updateCategory(Request $request, $categoryId)
    {
       
       $category = Category::findOrFail($categoryId);
    
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id . ',id',
        ]);
    
        $existingCategory = Category::where('name', $request->name)
            ->where('id', '!=', $categoryId)
            ->first();
    
        if ($existingCategory) {
            return response()->json(['error' => 'Category with this name already exists'], 422);
        }
    
        $category->update($request->all());
    
        return response()->json(['message' => 'Category updated successfully']);
    }

    public function deleteCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();

  
        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function listCategories()
    {
        $categories = Category::all();
        
        return response()->json($categories);
    }

    public function categories()
    {
        $categories = Category::all();
        
        return response()->json($categories);
    }
}

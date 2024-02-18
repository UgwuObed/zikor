<?php

namespace App\Http\Controllers\AdminAuth;

use App\Models\Admin;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
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

public function createCategory(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|unique:categories|max:255',
        ]);

        Category::create($request->all());

        return redirect()->route('admin.dashboard')->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, $categoryId)
    {
       
        $category = Category::findOrFail($categoryId);

        
        $request->validate([
            'name' => 'required|unique:categories,name,' . $category->id . '|max:255',
        ]);

        $category->update($request->all());

        return redirect()->route('admin.dashboard')->with('success', 'Category updated successfully.');
    }

    public function deleteCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Category deleted successfully.');
    }
    
    public function listCategories()
      {
    $categories = Category::all();
    return view('admin.categories.index', compact('categories'));
   }

   public function categories()
{
  $categories = Category::all();
  return view('admin.categories', ['categories' => $categories]);
}
}

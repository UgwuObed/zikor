<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{

    public function create()
    {
        return view('product-upload');
    }

    public function showProducts()
{
    $user = auth()->user();
    $products = $user->products;

    return view('products-list', compact('products'));
}
    
    public function index()
    {
        $user = auth()->user();
        $products = $user->products;
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'main_price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'quantity' => 'required|integer',
            'description' => 'nullable|string',
            'image' => 'required|image|max:2048', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        
        $imagePath = $request->file('image')->store('product_images');

        
        $user = auth()->user();
        $product = $user->products()->create([
            'name' => $request->name,
            'main_price' => $request->main_price,
            'discount_price' => $request->discount_price,
            'quantity' => $request->quantity,
            'description' => $request->description,
            'image_url' => $imagePath,
        ]);

        return response()->json(['message' => 'Product uploaded successfully', 'product' => $product], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'main_price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'quantity' => 'required|integer',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = auth()->user();
        $product = $user->products()->findOrFail($id);
        $productData = $request->except('image');

      
        $product->update($productData);

        
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('product_images');
            $product->image_url = $imagePath;
            $product->save();
        }

        return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $product = $user->products()->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}

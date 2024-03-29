<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function create()
    {
       
        $categories = Category::all();
        return response()->json(['categories' => $categories]);
    }

    public function showProducts()
    {
        $user = auth()->user();
        $products = $user->products->map(function ($product) {
            $product->image_url = Storage::disk('tigris')->url($product->image);
            return $product;
        });

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
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $imagePath = Storage::disk('tigris')->putFile('product_images', $request->file('image'));

            $user = auth()->user();
            $product = $user->products()->create([
                'name' => $request->name,
                'main_price' => $request->main_price,
                'discount_price' => $request->discount_price,
                'quantity' => $request->quantity,
                'description' => $request->description,
                'image' => $imagePath,
                'category_id' => $request->category_id,
            ]);

            return response()->json(['message' => 'Product uploaded successfully', 'product' => $product], 201);
        } catch (\Exception $e) {
            Log::error('Image upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Image upload failed. Please try again.'], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'main_price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'quantity' => 'required|integer',
            'description' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = auth()->user();
        $product = $user->products()->findOrFail($id);
        $productData = $request->except('image');

        if ($request->hasFile('image')) {
            try {
                $imagePath = Storage::disk('tigris')->putFile('product_images', $request->file('image'));

                if ($product->image) {
                    Storage::disk('tigris')->delete($product->image);
                }

                $productData['image'] = $imagePath;
            } catch (\Exception $e) {
                Log::error('Image upload error: ' . $e->getMessage());
                return response()->json(['message' => 'Image upload failed. Please try again.'], 400);
            }
        }

        $product->update($productData);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $product = $user->products()->findOrFail($id);

        if ($product->image) {
            Storage::delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}

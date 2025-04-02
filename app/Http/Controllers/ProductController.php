<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class ProductController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        return response()->json(['categories' => $categories]);
    }

    public function showProducts()
    {
        $user = auth()->user();
        $products = $user->products()->with('category')->get();
    
        $productCount = $products->count();
    
        $productData = $products->map(function ($product) {
            $productData = $product->toArray();
            
          
            if (is_string($product->image) && $this->isJson($product->image)) {
                $images = json_decode($product->image, true);
                $productData['image_urls'] = is_array($images) ? $images : [];
            } 
           
            else {
                $productData['image_urls'] = [$product->image];
            }
            
            return $productData;
        });
    
        return response()->json([
            'message' => 'Successfully fetched products',
            'count' => $productCount,
            'products' => $productData,
        ]);
    }
    
    private function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
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
            'images' => 'required|array',
            'images.*' => 'image|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        try {
            $uploadedImages = [];
            
            // Upload each image to Cloudinary
            foreach($request->file('images') as $image) {
                $uploadedFileUrl = cloudinary()->upload($image->getRealPath(), [
                    'folder' => 'product_images',
                ])->getSecurePath();
                
                $uploadedImages[] = $uploadedFileUrl;
            }
            
            Log::info('Images uploaded successfully: ' . implode(', ', $uploadedImages));
    
            $user = auth()->user();
            $product = $user->products()->create([
                'name' => $request->name,
                'main_price' => $request->main_price,
                'discount_price' => $request->discount_price,
                'quantity' => $request->quantity,
                'description' => $request->description,
                'image' => json_encode($uploadedImages), 
                'category_id' => $request->category_id,
            ]);
    
            return response()->json(['message' => 'Product uploaded successfully', 'product' => $product], 201);
        } catch (Exception $e) {
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
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'category_id' => 'required|exists:categories,id',
            'keep_images' => 'nullable|array',
            'keep_images.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = auth()->user();
        $product = $user->products()->findOrFail($id);
        
        $productData = $request->except(['images', 'keep_images']);
        
        // Handle image updates
        $currentImages = is_string($product->image) ? json_decode($product->image, true) : [];
        if (!is_array($currentImages)) {
            $currentImages = [$product->image]; 
        }
        
        try {
            // Determine which images to keep
            $imagesToKeep = $request->has('keep_images') ? $request->keep_images : [];
            
            // Delete images that aren't in the keep_images array
            $imagesToDelete = array_diff($currentImages, $imagesToKeep);
            foreach ($imagesToDelete as $imageUrl) {
                if (!empty($imageUrl)) {
                    Storage::disk('cloudinary')->delete($imageUrl);
                }
            }
            
            // Upload new images if any
            $newImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $uploadedFileUrl = cloudinary()->upload($image->getRealPath(), [
                        'folder' => 'product_images',
                    ])->getSecurePath();
                    
                    $newImages[] = $uploadedFileUrl;
                }
            }
            
            // Combine kept images with new ones
            $allImages = array_merge($imagesToKeep, $newImages);
            $productData['image'] = json_encode($allImages);
            
            // Update the product
            $product->update($productData);

            return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
        } catch (Exception $e) {
            Log::error('Image update error: ' . $e->getMessage());
            return response()->json(['message' => 'Image update failed. Please try again.'], 400);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $product = $user->products()->findOrFail($id);

        try {
            // Delete all images from Cloudinary
            $images = is_string($product->image) ? json_decode($product->image, true) : $product->image;
            
            if (is_array($images)) {
                foreach ($images as $imageUrl) {
                    if (!empty($imageUrl)) {
                        Storage::disk('cloudinary')->delete($imageUrl);
                    }
                }
            } else if (!empty($product->image)) {
                Storage::disk('cloudinary')->delete($product->image);
            }

            $product->delete();

            return response()->json(['message' => 'Product deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Product deletion error: ' . $e->getMessage());
            return response()->json(['message' => 'Product deletion failed. Please try again.'], 400);
        }
    }

    public function getBusinessProducts(Request $request)
    {
        $businessName = $request->input('business_name');

        $user = User::where('business_name', $businessName)->first();

        if (!$user) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $products = $user->products()->with('category')->get();

        if ($products->isEmpty()) {
            return response()->json(['error' => 'No products found for this business'], 404);
        }

        $aiResponse = $this->aiService->generateProductResponse($businessName, $products->toArray());

        return response()->json(['message' => $aiResponse]);
    }

    public function getBusinessName()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $businessName = $user->business_name;
        
        return response()->json([
            'message' => 'Successfully retrieved business name',
            'business' => [
                'name' => $businessName ?? ''
            ]
        ], 200);
    }
}

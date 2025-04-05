<?php

namespace App\Http\Controllers;


use App\Models\Storefront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class StorefrontController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $storefront = $user->storefront;

        if (!$storefront) {
            return response()->json([
                'message' => 'No storefront found',
                'has_storefront' => false
            ], 200);
        }

        return response()->json([
            'message' => 'Storefront retrieved successfully',
            'has_storefront' => true,
            'storefront' => $storefront
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:storefronts,slug',
            'category' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:2048',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'social_links' => 'nullable',
            'color_theme' => 'nullable|string|max:50',
            'business_hours' => 'nullable',
            'address' => 'nullable|string',
            'bank_details' => 'nullable',
            'bank_details.bank_name' => 'nullable|string|max:255',
            'bank_details.account_name' => 'nullable|string|max:255',
            'bank_details.account_number' => 'nullable|string|max:20',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $user = auth()->user();
            
            if ($user->storefront) {
                return response()->json([
                    'message' => 'User already has a storefront',
                    'storefront' => $user->storefront
                ], 400);
            }
            
            $storefrontData = $request->except(['logo', 'banner']);
            
            if (empty($storefrontData['slug'])) {
                $storefrontData['slug'] = Storefront::generateUniqueSlug($request->business_name);
            }
            
            if ($request->hasFile('logo')) {
                $logoPath = cloudinary()->upload($request->file('logo')->getRealPath(), [
                    'folder' => 'storefront_logos',
                ])->getSecurePath();
                $storefrontData['logo'] = $logoPath;
            }
            
            if ($request->hasFile('banner')) {
                $bannerPath = cloudinary()->upload($request->file('banner')->getRealPath(), [
                    'folder' => 'storefront_banners',
                ])->getSecurePath();
                $storefrontData['banner'] = $bannerPath;
            }

            $storefrontData['user_id'] = $user->id;
            
      
            $storefront = Storefront::create($storefrontData);
            
            if (empty($user->business_name)) {
                $user->business_name = $request->business_name;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Storefront created successfully',
                'storefront' => $storefront
            ], 201);
        } catch (Exception $e) {
            Log::error('Storefront creation error: ' . $e->getMessage());
            return response()->json([
                'failed' => true,
                'message' => 'Failed to create storefront',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:storefronts,slug,' . auth()->user()->storefront->id,
            'category' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:2048',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'social_links' => 'nullable',
            'color_theme' => 'nullable|string|max:50',
            'business_hours' => 'nullable',
            'address' => 'nullable|string',
            'bank_details' => 'nullable',
            'bank_details.bank_name' => 'nullable|string|max:255',
            'bank_details.account_name' => 'nullable|string|max:255',
            'bank_details.account_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $user = auth()->user();
            $storefront = $user->storefront;
            
            if (!$storefront) {
                return response()->json(['message' => 'Storefront not found'], 404);
            }
            
            $storefrontData = $request->except(['logo', 'banner']);
            
            // Handle file uploads
            if ($request->hasFile('logo')) {
                $logoPath = cloudinary()->upload($request->file('logo')->getRealPath(), [
                    'folder' => 'storefront_logos',
                ])->getSecurePath();
                $storefrontData['logo'] = $logoPath;
            }
            
            if ($request->hasFile('banner')) {
                $bannerPath = cloudinary()->upload($request->file('banner')->getRealPath(), [
                    'folder' => 'storefront_banners',
                ])->getSecurePath();
                $storefrontData['banner'] = $bannerPath;
            }
            
        
            $storefront->update($storefrontData);
            
           
            if (isset($storefrontData['business_name']) && $user->business_name !== $storefrontData['business_name']) {
                $user->business_name = $storefrontData['business_name'];
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Storefront updated successfully',
                'storefront' => $storefront
            ], 200);
        } catch (Exception $e) {
            Log::error('Storefront update error: ' . $e->getMessage());
            return response()->json([
                'failed' => true,
                'message' => 'Failed to update storefront',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkSlugAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $slug = Str::slug($request->slug);
        $isAvailable = !Storefront::where('slug', $slug)->exists();

        return response()->json([
            'slug' => $slug,
            'is_available' => $isAvailable
        ]);
    }

    public function getBySlug($slug)
    {
        $storefront = Storefront::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$storefront) {
            return response()->json(['message' => 'Storefront not found'], 404);
        }

        // Load related products
        $products = $storefront->user->products()
            ->with('category')
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($product) {
                $productData = $product->toArray();
                
                if (is_string($product->image) && $this->isJson($product->image)) {
                    $images = json_decode($product->image, true);
                    $productData['image_urls'] = is_array($images) ? $images : [];
                } else {
                    $productData['image_urls'] = [$product->image];
                }
                
                return $productData;
            });

        return response()->json([
            'storefront' => $storefront,
            'products' => $products
        ]);
    }

    private function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
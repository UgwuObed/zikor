<?php

namespace App\Http\Controllers;

use App\Models\ChatbotInstance;
use Illuminate\Http\Request;


class ZikorController extends Controller
{

public function getUserProductsForRasa(Request $request)
{
    // Retrieve the authenticated user
    $user = $request->user();

    // Retrieve the user's chatbot instance
    $chatbotInstance = ChatbotInstance::where('user_id', $user->id)->first();

    // If the chatbot instance is found, retrieve the products associated with it
    if ($chatbotInstance) {
        $products = $chatbotInstance->products()->with('category')->get();

        // Transform product data
        $productData = $products->map(function ($product) {
            $productData = $product->toArray();
            $productData['image_url'] = Storage::disk('tigris')->url($product->image);
            return $productData;
        });

        // Return product data as JSON response
        return response()->json(['products' => $productData]);
    } else {
        // If no chatbot instance is found, return an empty response or an error
        return response()->json(['error' => 'Chatbot instance not found'], 404);
    }
}

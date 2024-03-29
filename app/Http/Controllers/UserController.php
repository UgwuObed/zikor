<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    
    public function createUser(Request $request)
    {
        // check if the user is admin
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        //create user

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = 'user'; 
        $user->save();
    
        return response()->json(['message' => 'User created successfully'], 201);
    }
    
    public function getUserProfiles(Request $request)
{
    //check if the user is admin
    if ($request->user()->role !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Retrieve all users
    $users = User::all();

    $users = User::select('id', 'name', 'email', 'role')->get();
    return response()->json(['users' => $users], 200);
}

public function getUserProfile(Request $request)
{
    $user = $request->user();
    return response()->json(['name' => $user->name, 'email' => $user->email], 200);
}

    public function updateUserProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        return response()->json(['message' => 'User profile updated successfully'], 200);
    }

    public function deleteUser(Request $request, $id)
    {

        // check if the user is admin
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

 
        //delete user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}


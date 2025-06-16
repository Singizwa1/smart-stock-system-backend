<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::with('roles')
                        ->whereHas('roles', function($query) {
                            $query->where('name', 'Farmer');
                        })
                        ->get()
                        ->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'role_id' => 2, // Farmer role_id
                                'created_at' => $user->created_at
                            ];
                        });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching farmers:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch farmers'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6'
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => 2 // Farmer role_id
            ]);

            $user->assignRole('Farmer');

            return response()->json([
                'success' => true,
                'message' => 'Farmer created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => 2
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating farmer:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create farmer'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
            ]);

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Farmer updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => 2
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating farmer:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update farmer'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Farmer deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting farmer:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete farmer'
            ], 500);
        }
    }


    
}
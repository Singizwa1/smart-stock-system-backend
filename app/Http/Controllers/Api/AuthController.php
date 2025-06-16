<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller{
    public function register(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 2  // Default Farmer role_id
        ]);

        // Make sure role exists and assign it
        $farmerRole = \Spatie\Permission\Models\Role::findById(2);
        $user->assignRole($farmerRole);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => 2,
                'role' => 'Farmer'
            ]
        ], 201);
    }
    public function login(Request $request)
{
    try {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Retrieve user
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'roles' => $user->getRoleNames()
            ]
        ];

        // Debug logging
        Log::info('User login successful:', [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'roles' => $user->getRoleNames()->toArray()
        ]);

        return response()->json($response, 200);
    
    } catch (ValidationException $e) {
        Log::warning('Validation error during login:', ['errors' => $e->errors()]);
        return response()->json([
            'success' => false,
            'message' => 'Validation error occurred',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {
        Log::error('Unexpected error during login:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred during login'
        ], 500);
    }
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }


public function createFarmer(Request $request)
{
    try {
        $user = Auth::user();

        // Debug log for request details
        Log::info('User attempting to create farmer:', [
            'user_id' => $user->id ?? null,
            'roles' => $user?->getRoleNames()->toArray()
        ]);

        // Check if user is authorized
        if (!$user || !$user->hasRole('Admin')) {
            Log::warning('Unauthorized user attempted to create a farmer', ['user_id' => $user->id ?? null]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can create farmers.',
                'user_roles' => $user?->getRoleNames()
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        // Create a new farmer with role_id = 2
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => 2, // Hardcoded role_id for Farmer
        ]);

        // Assign the Spatie role by name
        $newUser->assignRole('Farmer');

        Log::info('Farmer created successfully:', ['farmer_id' => $newUser->id]);

        return response()->json([
            'success' => true,
            'message' => 'Farmer created successfully',
            'data' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'role_id' => $newUser->role_id,
            ]
        ], 201);

    } catch (ValidationException $e) {
        Log::warning('Validation error during farmer creation:', ['errors' => $e->errors()]);
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {
        Log::error('Unexpected error during farmer creation:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred while creating the farmer.'
        ], 500);
    }
}


public function assignRoleToUser(Request $request)
{
    try {
        $admin = Auth::user();

        // Only user with ID = 1 (Admin) can assign roles
        if (!$admin || $admin->id !== 1) {
            Log::warning('Unauthorized user attempted to assign a role', ['user_id' => $admin->id ?? null]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Admin can assign roles.'
            ], 403);
        }

        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_name' => 'required|string|exists:roles,name'
        ]);

        $targetUser = User::find($validated['user_id']);

        try {
            // Assign role via Spatie
            $targetUser->assignRole($validated['role_name']);

            // Update role_id manually in users table
            $targetUser->role_id = Role::where('name', $validated['role_name'])->value('id');
            $targetUser->save();

        } catch (\Throwable $e) {
            Log::error('Failed to assign role:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role.',
                'error' => $e->getMessage()
            ], 500);
        }

        Log::info('Role assigned successfully', [
            'admin_id' => $admin->id,
            'user_id' => $targetUser->id,
            'role' => $validated['role_name']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully.',
            'data' => [
                'user_id' => $targetUser->id,
                'assigned_role' => $validated['role_name']
            ]
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {
        Log::error('Unexpected error in role assignment:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred.'
        ], 500);
    }
}



public function updateFarmer(Request $request, $id)
{
    try {
        $authUser = Auth::user();

        // Allow only users with role_id = 1 (Admin) or 2 (Farmer)
        if (!in_array($authUser->role_id, [1, 2])) {
            Log::warning('Unauthorized user attempted to update farmer.', ['user_id' => $authUser->id]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You are not allowed to update farmers.'
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'role_id' => 'sometimes|in:1,2' // Optional update to either Admin or Farmer role
        ]);

        // Find the user to update
        $farmer = User::findOrFail($id);

        // Update fields
        if (isset($validated['name'])) $farmer->name = $validated['name'];
        if (isset($validated['email'])) $farmer->email = $validated['email'];
        if (isset($validated['password'])) $farmer->password = Hash::make($validated['password']);
        if (isset($validated['role_id'])) $farmer->role_id = $validated['role_id'];

        $farmer->save();

        // Re-assign Spatie role if role_id changed
        if (isset($validated['role_id'])) {
            $farmer->syncRoles($validated['role_id'] == 1 ? 'Admin' : 'Farmer');
        }

        Log::info('Farmer updated successfully', ['farmer_id' => $farmer->id]);

        return response()->json([
            'success' => true,
            'message' => 'Farmer updated successfully',
            'data' => [
                'id' => $farmer->id,
                'name' => $farmer->name,
                'email' => $farmer->email,
                'role_id' => $farmer->role_id
            ]
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {
        Log::error('Unexpected error during farmer update', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred while updating the farmer.'
        ], 500);
    }
}

public function getUserRoles($userId)
{
    try {
        $user = User::findOrFail($userId);
        $roles = $user->getRoleNames();
        
        return response()->json([
            'status' => true,
            'message' => 'User roles retrieved successfully',
            'roles' => $roles
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve user roles',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
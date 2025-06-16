<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = Auth::user();

        if (!$user || !$user->HasRoles($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only ' . ucfirst($role) . 's can access this endpoint.'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact an administrator.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        // Support comma separated roles or multiple arguments
        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $allowedRoles[] = trim($r);
            }
        }

        if (!in_array($user->role, $allowedRoles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access for your role.',
                ], 403);
            }

            // Redirect to appropriate landing page
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')->with('error', 'Unauthorized area.');
            } elseif ($user->isPurchasing()) {
                return redirect()->route('staff.dashboard')->with('error', 'Unauthorized area.');
            } elseif ($user->isCashier()) {
                return redirect()->route('cashier.pos')->with('error', 'Unauthorized area.');
            }

            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}

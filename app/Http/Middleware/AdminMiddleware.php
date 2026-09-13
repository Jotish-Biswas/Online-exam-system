<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin/teacher is logged in via Laravel Auth or session
        if (!\Illuminate\Support\Facades\Auth::check() && !session()->has('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Please login to access admin panel.');
        }

        // If the user is a student (logged in via session), they should not access admin routes.
        if (session()->has('student_id')) {
            return redirect('/')->with('error', 'You do not have permission to access this page.');
        }

        // Block inactive admins (deactivated via manage-admins)
        if (\Illuminate\Support\Facades\Auth::check()) {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if (str_starts_with($role, 'inactive_')) {
                \Illuminate\Support\Facades\Auth::logout();
                return redirect()->route('admin.login')
                    ->with('error', 'Your admin account has been deactivated. Please contact a super admin.');
            }
        }

        return $next($request);
    }
}

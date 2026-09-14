<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((!Auth::check() || Auth::user()->role !== 'student') && !session('student_exam_id')) {
            return redirect()->route('student.login')->with('error', 'Please login with your student account.');
        }

        return $next($request);
    }
}

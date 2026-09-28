<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        try {
            $redirect = Redirect::query()
                ->where('is_active', true)
                ->where(function ($q) use ($path, $request) {
                    $q->where('from_path', $path)
                        ->orWhere('from_path', $request->getPathInfo())
                        ->orWhere('from_path', ltrim($path, '/'));
                })
                ->first();
        } catch (Throwable) {
            return $next($request);
        }

        if (! $redirect) {
            return $next($request);
        }

        $status = (int) ($redirect->status_code ?: 301);
        if (! in_array($status, [301, 302, 307, 308], true)) {
            $status = 301;
        }

        $to = $redirect->to_path;
        if (! str_starts_with($to, 'http://') && ! str_starts_with($to, 'https://')) {
            $to = url($to);
        }

        return redirect()->away($to, $status);
    }
}

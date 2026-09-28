<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOneC
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->authorized($request)) {
            return $next($request);
        }

        return response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="1C Exchange"',
        ]);
    }

    private function authorized(Request $request): bool
    {
        $token = (string) config('services.onec.token', env('ONE_C_TOKEN', ''));
        if ($token !== '') {
            $headerToken = (string) $request->header('x-1c-token', '');
            if (hash_equals($token, $headerToken)) {
                return true;
            }

            $auth = (string) $request->header('Authorization', '');
            if (str_starts_with($auth, 'Bearer ')) {
                $bearer = trim(substr($auth, 7));
                if ($bearer !== '' && hash_equals($token, $bearer)) {
                    return true;
                }
            }
        }

        $user = (string) config('services.onec.user', env('ONE_C_USER', ''));
        $password = (string) config('services.onec.password', env('ONE_C_PASSWORD', ''));

        if ($user === '' && $password === '' && $token === '') {
            return false;
        }

        if ($user === '' && $password === '') {
            return false;
        }

        $basicUser = (string) $request->getUser();
        $basicPass = (string) $request->getPassword();

        return hash_equals($user, $basicUser) && hash_equals($password, $basicPass);
    }
}

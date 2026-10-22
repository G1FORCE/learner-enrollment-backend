<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function handle(
    Request $request,
    Closure $next
): Response {
    $jwt = app(JwtService::class);
        $authorization = $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token is required.',
            ], 401);
        }

        try {
            $payload = $jwt->verifyToken($matches[1]);

            $user = User::find($payload['sub']);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $request->attributes->set('jwt_payload', $payload);
            $request->setUserResolver(fn () => $user);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired authentication token.',
            ], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            $userId = $request->header('X-User-Id');

            if (! $userId) {
                return response()->json([
                    'message' => 'Debes iniciar sesion para continuar.',
                ], 401);
            }

            $user = User::find($userId);

            if (! $user) {
                return response()->json([
                    'message' => 'No fue posible identificar la cuenta solicitada.',
                ], 401);
            }
        }

        $request->setUserResolver(fn () => $user);

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso.',
                'required_roles' => $roles,
                'current_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $response->getStatusCode() >= 400
            || $this->isLoggedExplicitly($request)) {
            return $response;
        }

        $path = trim($request->path(), '/');
        $route = $request->route();
        $parameters = is_object($route) ? $route->parameters() : [];
        $entityId = $this->entityId($parameters, $request);

        ActivityLogger::log(
            $this->action($request, $path),
            $this->entity($path),
            sprintf('%s %s melalui aplikasi', $this->action($request, $path), $this->entity($path)),
            [
                'entity_id' => $entityId,
                'route' => $path,
                'method' => $request->method(),
                'input' => Arr::except($request->input(), [
                    'password', 'password_confirmation', 'current_password', 'token', 'authorization',
                ]),
            ],
        );

        return $response;
    }

    private function isLoggedExplicitly(Request $request): bool
    {
        if ($request->method() !== 'POST') {
            return false;
        }

        return in_array(trim($request->path(), '/'), [
            'login', 'register', 'admin/login', 'api/login', 'api/register', 'api/motorcycles',
            'admin/motorcycles_Management', 'admin/users', 'admin/admins',
        ], true);
    }

    private function action(Request $request, string $path): string
    {
        if ($request->isMethod('DELETE')) {
            return 'DELETE';
        }
        if ($request->isMethod('PUT') || $request->isMethod('PATCH') || str_contains($path, 'toggle-status')) {
            return 'UPDATE';
        }
        if (str_contains($path, 'cancel')) {
            return 'CANCEL';
        }
        if (str_contains($path, 'approve')) {
            return 'APPROVE';
        }
        if (str_contains($path, 'reject')) {
            return 'REJECT';
        }
        if (str_contains($path, 'logout')) {
            return 'LOGOUT';
        }
        if (str_ends_with($path, '/success')) {
            return 'PAYMENT_SUCCESS';
        }
        if (str_ends_with($path, '/failed')) {
            return 'PAYMENT_FAILED';
        }

        return 'CREATE';
    }

    private function entity(string $path): string
    {
        foreach ([
            'motorcycles' => 'motorcycle', 'users' => 'user', 'admins' => 'admin',
            'accessories' => 'accessory', 'service-types' => 'service_type',
            'services' => 'service', 'returns' => 'return', 'payments' => 'payment',
            'bookings' => 'booking', 'locations' => 'location', 'devices' => 'device',
            'verification' => 'user_verification', 'images-management' => 'motorcycle_image',
            'transaksi' => 'rental', 'gps' => 'location',
        ] as $segment => $entity) {
            if (str_contains($path, $segment)) {
                return $entity;
            }
        }

        return 'application';
    }

    private function entityId(array $parameters, Request $request): mixed
    {
        foreach ($parameters as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter->getKey();
            }
            if (is_scalar($parameter)) {
                return $parameter;
            }
        }

        return $request->input('id') ?? $request->input('rental_id');
    }
}

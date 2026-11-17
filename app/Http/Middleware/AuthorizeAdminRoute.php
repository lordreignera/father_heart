<?php

namespace App\Http\Middleware;

use App\Support\RoutePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName() ?? '';
        $permission = RoutePermissions::forRequest($name, $request->route('category'));
        // Forms need the district-dependent lookup even when the helper route was not selected separately.
        if ($name === 'admin.location-options.subcounties' && $request->user()?->canAny([
            'route-admin-assessments-create', 'route-admin-assessments-edit',
            'route-admin-distributions-create', 'route-admin-distributions-edit',
            'route-admin-distributions-store', 'route-admin-distributions-update',
        ])) return $next($request);
        abort_unless($permission !== null && $request->user()?->can($permission), 403);
        return $next($request);
    }
}

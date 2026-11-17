<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class RoutePermissions
{
    public static function key(string $route): string
    {
        return 'route-'.str_replace('.', '-', $route);
    }

    public static function forRequest(string $route, ?string $category = null): ?string
    {
        if ($route === 'admin.historical.index') {
            return in_array($category, ['locations', 'events', 'media'], true) ? self::key('admin.historical.'.$category) : null;
        }
        return str_starts_with($route, 'admin.') ? self::key($route) : null;
    }

    public static function catalogue(): array
    {
        $result = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.') || $name === 'admin.historical.index') continue;
            $description = implode('|', array_diff($route->methods(), ['HEAD'])).' /'.$route->uri();
            if ($name === 'admin.location-options.subcounties') $description .= ' · Also available to staff who can open an assessment or distribution form';
            if ((str_starts_with($name, 'admin.historical.team.') && $name !== 'admin.historical.team.index')
                || str_starts_with($name, 'admin.registration-requests.')
                || in_array($name, ['admin.historical.feedback.update', 'admin.historical.feedback.archive', 'admin.historical.feedback.restore', 'admin.permissions.create', 'admin.permissions.store', 'admin.permissions.edit', 'admin.permissions.update', 'admin.permissions.destroy'], true)) {
                $description .= ' · Super administrators only';
            }
            $result[self::key($name)] = $description;
        }
        foreach (['locations', 'events', 'media'] as $category) {
            $result[self::key('admin.historical.'.$category)] = 'GET /admin/historical/'.$category;
        }
        ksort($result);
        return $result;
    }
}

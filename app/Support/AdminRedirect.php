<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class AdminRedirect
{
    public static function firstAllowed(array $routes): RedirectResponse
    {
        foreach ($routes as $name => $parameters) {
            if (auth()->user()->can(RoutePermissions::key($name))) return redirect()->route($name, $parameters);
        }
        return redirect()->route('dashboard');
    }
}

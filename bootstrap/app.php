<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->routeIs('admin.assessments.store', 'admin.assessments.update') && ! $request->expectsJson()) {
                return redirect($exception->redirectTo ?? url()->previous())
                    ->withInput($request->except(['consent.recipient_identity', 'consent.document', '_token']))
                    ->withErrors($exception->errors(), $request->input('_error_bag', $exception->errorBag));
            }
            if (! $request->routeIs('admin.therapy.*') || $request->expectsJson()) return null;
            $private = $request->only(['registration_data', 'registration_custom', 'date_of_birth', 'notes']);
            return redirect($exception->redirectTo ?? url()->previous())
                ->withInput($request->except(['registration_data', 'registration_custom', 'date_of_birth', 'notes', 'document', '_token']))
                ->with('therapy_private_old_input', Crypt::encryptString(json_encode($private, JSON_THROW_ON_ERROR)))
                ->withErrors($exception->errors(), $request->input('_error_bag', $exception->errorBag));
        });
    })->create();

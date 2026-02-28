<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // S'assurer que les erreurs d'autorisation renvoient 403 (et non 404)
        // pour les utilisateurs sans permission (ex: non super_admin)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('admin/*') || $request->is('livewire/*')) {
                // Dans le panel admin : rediriger vers le dashboard avec un message
                if ($request->expectsJson()) {
                    return response()->json(['message' => $e->getMessage() ?: 'Accès refusé'], 403);
                }
                return redirect('/admin')
                    ->with('error', $e->getMessage() ?: 'Vous n\'avez pas la permission d\'accéder à cette ressource.');
            }

            // Hors admin : afficher la page 403
            return response()->view('errors.403', ['message' => $e->getMessage()], 403);
        });
    })->create();

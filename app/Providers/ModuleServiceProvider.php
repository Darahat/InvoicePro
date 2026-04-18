<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
   public function register(): void
    {
        // Bind interfaces to implementations module-wise if needed.
    }

    public function boot(): void
    {
        $modulePath = app_path('Modules');

        if (!File::isDirectory($modulePath)) {
            return;
        }

        foreach (File::directories($modulePath) as $moduleDir) {
            $routeFile = $moduleDir . '/routes.php';

            if (File::exists($routeFile)) {
                Route::middleware(['web'])
                    ->group($routeFile);
            }
        }
    }

}

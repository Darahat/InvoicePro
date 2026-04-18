# InvoicePro - Nuxt + Inertia + Laravel (Modular Monolith)

This guide gives you copy-paste commands to build **InvoicePro** as a modular monolith with clean architecture boundaries, so each module can be extracted later into its own service.

## 1) Create Laravel project

```bash
composer create-project laravel/laravel InvoicePro
cd InvoicePro
```

## 2) Install core backend packages (Composer)

```bash
# Inertia server adapter
composer require inertiajs/inertia-laravel

# Auth starter with Inertia + Vue
composer require laravel/breeze --dev

# URL generator for frontend
composer require tightenco/ziggy

# PDF generation for invoice download
composer require barryvdh/laravel-dompdf

# DTOs and query filtering for clean architecture layering
composer require spatie/laravel-data spatie/laravel-query-builder

# Role/permission support (Admin module)
composer require spatie/laravel-permission
```

## 3) Install frontend stack for Inertia

```bash
php artisan breeze:install vue --typescript
npm install
npm install @inertiajs/vue3 pinia
npm run build
```

## 4) Create modular monolith folder structure

```bash
mkdir -p app/Modules/{Invoice,User,Client,Guest,Auth,Admin}/{Controllers,Requests,Services,Repositories,Models,DTOs,Actions}

touch app/Modules/Invoice/routes.php
touch app/Modules/User/routes.php
touch app/Modules/Client/routes.php
touch app/Modules/Guest/routes.php
touch app/Modules/Auth/routes.php
touch app/Modules/Admin/routes.php
```

## 5) Add module namespace autoload (composer.json)

Update `composer.json`:

```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "App\\Modules\\": "app/Modules/"
  }
}
```

Then run:

```bash
composer dump-autoload
```

## 6) Create provider to register module routes

```bash
php artisan make:provider ModuleServiceProvider
```

Put this in `app/Providers/ModuleServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
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
```

Then register provider in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
];
```

## 7) Create reusable Artisan command for new modules (important)

```bash
php artisan make:command MakeModuleStructure
```

Put this in `app/Console/Commands/MakeModuleStructure.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleStructure extends Command
{
    protected $signature = 'make:module {name}';

    protected $description = 'Create module folders with clean architecture structure';

    public function handle(): int
    {
        $name = ucfirst($this->argument('name'));
        $base = app_path("Modules/{$name}");

        $folders = [
            'Controllers',
            'Requests',
            'Services',
            'Repositories',
            'Models',
            'DTOs',
            'Actions',
        ];

        File::ensureDirectoryExists($base);

        foreach ($folders as $folder) {
            File::ensureDirectoryExists("{$base}/{$folder}");
        }

        $routeFile = "{$base}/routes.php";

        if (!File::exists($routeFile)) {
            File::put($routeFile, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
        }

        $this->info("Module {$name} created successfully.");

        return self::SUCCESS;
    }
}
```

Use it:

```bash
php artisan make:module Invoice
php artisan make:module User
php artisan make:module Client
php artisan make:module Guest
php artisan make:module Auth
php artisan make:module Admin
```

## 8) Invoice module starter Artisan commands

```bash
php artisan make:model Modules/Invoice/Models/Invoice -m
php artisan make:model Modules/Invoice/Models/InvoiceItem -m
php artisan make:controller Modules/Invoice/Controllers/InvoiceController
php artisan make:request Modules/Invoice/Requests/StoreInvoiceRequest
php artisan make:request Modules/Invoice/Requests/UpdateInvoiceRequest
```

## 9) Suggested route pattern per module

Example `app/Modules/Invoice/routes.php`:

```php
<?php

use App\Modules\Invoice\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
    Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
    Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

    // PDF download endpoint
    Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
});
```

## 10) Database and run

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

## 11) Nuxt integration strategy (future-safe)

Inertia and Nuxt are usually different frontend approaches. For your migration-safe goal:

1. Keep Laravel modules strictly in Services/Repositories/DTOs/Actions.
2. Keep Inertia controllers thin (HTTP adapter only).
3. Create API routes in parallel (`routes/api.php` or per-module `api.php`) using same Services.
4. Later replace Inertia pages with Nuxt app (`frontend/`) that consumes Laravel API without changing domain logic.

Optional future Nuxt app bootstrap:

```bash
npx nuxi@latest init frontend
cd frontend
npm install
npm run dev
```

## 12) Clean architecture guardrails (must follow)

- Controller -> Action/Service only.
- Service -> Repository + DTO only.
- Repository -> Eloquent/DB only.
- No cross-module direct model access; use contracts/actions.
- Keep each module self-contained so extraction to microservice is easy.

## 13) Useful extra packages (optional)

```bash
# Better debugging in development
composer require barryvdh/laravel-debugbar --dev

# API auth when you split frontend/backend later
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
php artisan migrate
```

---

This setup gives you a modular monolith now, with minimum rewrite later when moving from Inertia to API + separate Nuxt frontend.

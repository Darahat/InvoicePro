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

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $resource = new App\Filament\Resources\InjectionReportResource();
    $form = $resource::form(Filament\Forms\Form::make(app(\Livewire\Component::class)));
    
    // Evaluate defaults to trigger the closures
    foreach ($form->getComponents() as $component) {
        if (method_exists($component, 'getChildComponents')) {
            foreach ($component->getChildComponents() as $child) {
                if (method_exists($child, 'getDefaultState')) {
                    $child->getDefaultState();
                }
            }
        }
    }
    
    echo "SUCCESS\n";
} catch (\Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

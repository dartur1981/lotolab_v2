<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $ref = new ReflectionClass('Filament\Tables\Actions\Action');
    echo "Found: " . $ref->getFileName() . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

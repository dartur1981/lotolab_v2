<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $ref = new ReflectionClass('Filament\Tables\Actions\EditAction');
    echo "Found EditAction: " . $ref->getFileName() . "\n";
} catch (Exception $e) {
    echo "Exception EditAction: " . $e->getMessage() . "\n";
}

try {
    $ref = new ReflectionClass('Filament\Actions\Action');
    echo "Found Action: " . $ref->getFileName() . "\n";
} catch (Exception $e) {
    echo "Exception Action: " . $e->getMessage() . "\n";
}

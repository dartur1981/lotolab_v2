<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Lotofacil\Combinacao18::first();
$s = new \App\Services\ScoreLotofacilService();
echo "Score: " . $s->calcularESalvar($c) . "\n";

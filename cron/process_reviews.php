<?php

require __DIR__ . '/../src/autoload.php';

use App\Config\BusinessRepository;
use App\Database\Connection;
use App\Reviews\ReviewRepository;
use App\Reviews\ReviewResponder;
use App\Support\Logger;

$basePath = dirname(__DIR__);
$logPath = $basePath . '/storage/app.log';
$databaseConfig = require $basePath . '/config/database.php';

if (!is_dir(dirname($logPath))) {
    mkdir(dirname($logPath), 0777, true);
}

$pdo = Connection::make($databaseConfig);
$businessRepository = new BusinessRepository($pdo);
$reviewRepository = new ReviewRepository($pdo);
$logger = new Logger($logPath);
$reviewResponder = new ReviewResponder($reviewRepository, $logger);

try {
    $businesses = $businessRepository->all();
    foreach ($businesses as $business) {
        $logger->info('Processing business', ['businessId' => $business['id']]);
        $reviewResponder->handleBusiness($business);
    }
} catch (\Throwable $exception) {
    $logger->error('Processing failed', [
        'exception' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
    ]);
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

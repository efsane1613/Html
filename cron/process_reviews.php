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
$reviewResponder = new ReviewResponder($reviewRepository, $businessRepository, $logger);

$businesses = $businessRepository->all();

foreach ($businesses as $business) {
    try {
        $logger->info('Processing business', ['businessId' => $business['id']]);
        $result = $reviewResponder->handleBusiness($business);
        $businessRepository->recordLastCheck($business['id'], $result['fetched'], $result['replied']);
        $businessRepository->updateConnectionStatus($business['id'], 'connected', 'Cron kontrolü başarıyla tamamlandı.');
        $logger->info('Business processed', [
            'businessId' => $business['id'],
            'fetched' => $result['fetched'],
            'replied' => $result['replied'],
        ]);
    } catch (\Throwable $exception) {
        $logger->error('Processing failed', [
            'businessId' => $business['id'],
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        $businessRepository->updateConnectionStatus($business['id'], 'error', $exception->getMessage());
        fwrite(STDERR, sprintf('Business %d failed: %s%s', $business['id'], $exception->getMessage(), PHP_EOL));
    }
}

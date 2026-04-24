<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use RioSlum\HiringTest\ContactImporter;
use RioSlum\HiringTest\MockCrmClient;

$inputPath = __DIR__ . '/data/contacts.json';
$outputPath = __DIR__ . '/output/import-results.json';

$client = new MockCrmClient();
$importer = new ContactImporter($client);

$result = $importer->run($inputPath, $outputPath);

echo "Import finished. Results saved to output/import-results.json" . PHP_EOL;
echo json_encode($result['summary'] ?? [], JSON_PRETTY_PRINT) . PHP_EOL;

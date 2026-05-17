<?php

declare(strict_types=1);

namespace BatchDataImporter\Report;

use RuntimeException;

final class ImportReport
{
    public function build(
        array $normalizationStats,
        iterable $imported,
        iterable $failed,
        iterable $skipped,
    ): array {
        $totalImported = $this->countIterable($imported);
        $totalFailed = $this->countIterable($failed);
        $attempted = $totalImported + $totalFailed;

        return [
            'summary' => [
                'total_records'      => $normalizationStats['total_records'],
                'valid_records'      => $normalizationStats['valid_records'],
                'invalid_records'    => $normalizationStats['invalid_records'],
                'duplicates_merged'  => $normalizationStats['duplicates_merged'],
                'attempted_imports'  => $attempted,
                'successful_imports' => $totalImported,
                'failed_imports'     => $totalFailed,
            ],
            'imported'     => $imported,
            'failed'       => $failed,
            'skipped'      => $skipped,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    public function write(array $report, string $path): void
    {
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException("Cannot create output directory: {$dir}");
        }

        $handle = fopen($path, 'wb');
        if (!$handle) {
            throw new RuntimeException("Cannot open file for writing: {$path}");
        }

        fwrite($handle, "{\n");

        fwrite($handle, '  "summary": ' . json_encode($report['summary'], JSON_UNESCAPED_UNICODE) . ",\n");

        $this->writeJsonSection($handle, 'imported', $report['imported'], false);
        $this->writeJsonSection($handle, 'failed', $report['failed'], false);
        $this->writeJsonSection($handle, 'skipped', $report['skipped'], false);

        fwrite($handle, '  "generated_at": "' . $report['generated_at'] . "\"\n");

        fwrite($handle, "}\n");

        fclose($handle);
    }

    private function writeJsonSection($handle, string $key, iterable $items, bool $isLast = false): void
    {
        fwrite($handle, "  \"{$key}\": [\n");

        $isFirst = true;
        foreach ($items as $item) {
            if (!$isFirst) {
                fwrite($handle, ",\n");
            }
            fwrite($handle, "    " . json_encode($item, JSON_UNESCAPED_UNICODE));
            $isFirst = false;
        }

        fwrite($handle, "\n  ]" . ($isLast ? "\n" : ",\n"));
    }

    private function countIterable(iterable $iterable): int
    {
        if (is_array($iterable) || $iterable instanceof \Countable) {
            return count($iterable);
        }

        $count = 0;

        foreach ($iterable as $it) {
            $count++;
        }

        return $count;
    }
}
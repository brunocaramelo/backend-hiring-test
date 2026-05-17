<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Report\ImportReport;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ImportReportTest extends TestCase
{
    private ImportReport $report;
    private string       $tmpDir;

    protected function setUp(): void
    {
        $this->report = new ImportReport();
        $this->tmpDir = sys_get_temp_dir() . '/rioslum_report_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->deleteDir($this->tmpDir);
        }
    }

    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $items = scandir($dirPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dirPath . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteDir($path); 
            } else {
                unlink($path);
            }
        }

        rmdir($dirPath); // Deleta a pasta agora que ela está 100% vazia
    }

    private function stats(array $overrides = []): array
    {
        return array_merge([
            'total_records'     => 10,
            'valid_records'     => 8,
            'invalid_records'   => 2,
            'duplicates_merged' => 1,
        ], $overrides);
    }

    public function test_build_summary_matches_inputs(): void
    {
        $result = $this->report->build(
            normalizationStats: $this->stats(),
            imported:           [['email' => 'a@b.com'], ['email' => 'c@d.com']],
            failed:             [['email' => 'e@f.com']],
            skipped:            [['raw' => [], 'reason' => 'missing email']],
        );

        $summary = $result['summary'];
        $this->assertSame(10, $summary['total_records']);
        $this->assertSame(8,  $summary['valid_records']);
        $this->assertSame(2,  $summary['invalid_records']);
        $this->assertSame(1,  $summary['duplicates_merged']);
        $this->assertSame(3,  $summary['attempted_imports']);  // 2 imported + 1 failed
        $this->assertSame(2,  $summary['successful_imports']);
        $this->assertSame(1,  $summary['failed_imports']);
    }

    public function test_build_includes_imported_failed_skipped_arrays(): void
    {
        $result = $this->report->build(
            normalizationStats: $this->stats(),
            imported:           [['email' => 'a@b.com']],
            failed:             [],
            skipped:            [],
        );

        $this->assertArrayHasKey('imported', $result);
        $this->assertArrayHasKey('failed',   $result);
        $this->assertArrayHasKey('skipped',  $result);
    }

    public function test_build_includes_generated_at_timestamp(): void
    {
        $result = $this->report->build($this->stats(), [], [], []);

        $this->assertArrayHasKey('generated_at', $result);
        $this->assertNotEmpty($result['generated_at']);
    }

    public function test_write_creates_valid_json_file(): void
    {
        $path   = $this->tmpDir . '/output/result.json';
        $report = $this->report->build($this->stats(), [], [], []);

        $this->report->write($report, $path);

        $this->assertFileExists($path);
        $decoded = json_decode(file_get_contents($path), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('summary', $decoded);
    }

    public function test_write_creates_output_directory_if_missing(): void
    {
        $path = $this->tmpDir . '/nested/deep/result.json';
        $this->report->write($this->report->build($this->stats(), [], [], []), $path);

        $this->assertFileExists($path);
    }

    public function test_write_produces_pretty_printed_json(): void
    {
        $path = $this->tmpDir . '/pretty.json';
        $this->report->write($this->report->build($this->stats(), [], [], []), $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString("\n", $content); // pretty print adds newlines
    }

    public function test_attempted_imports_is_sum_of_imported_and_failed(): void
    {
        $result = $this->report->build(
            normalizationStats: $this->stats(),
            imported:           array_fill(0, 4, []),
            failed:             array_fill(0, 3, []),
            skipped:            [],
        );

        $this->assertSame(7, $result['summary']['attempted_imports']);
    }
}

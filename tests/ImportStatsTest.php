<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Contact\Services\ImportStats;
use PHPUnit\Framework\TestCase;

final class ImportStatsTest extends TestCase
{
    private ImportStats $stats;

    protected function setUp(): void
    {
        $this->stats = new ImportStats();
    }

    public function test_total_starts_at_zero(): void
    {
        $compiled = $this->stats->compile(0, 0);
        $this->assertSame(0, $compiled['stats']['total_records']);
    }

    public function test_increment_total_accumulates(): void
    {
        $this->stats->incrementTotal();
        $this->stats->incrementTotal();
        $this->stats->incrementTotal();

        $compiled = $this->stats->compile(0, 0);
        $this->assertSame(3, $compiled['stats']['total_records']);
    }

    public function test_add_skipped_accumulates(): void
    {
        $this->stats->addSkipped(['email' => ''], 'missing email');
        $this->stats->addSkipped(['email' => 'bad'], 'invalid email format');

        $compiled = $this->stats->compile(0, 0);
        $this->assertCount(2, $compiled['skipped']);
        $this->assertSame(2, $compiled['stats']['invalid_records']);
    }

    public function test_compile_calculates_valid_records(): void
    {
        // 5 total: 3 valid (after dedup) + 2 duplicates merged
        $this->stats->incrementTotal(); // 5x
        $this->stats->incrementTotal();
        $this->stats->incrementTotal();
        $this->stats->incrementTotal();
        $this->stats->incrementTotal();

        $compiled = $this->stats->compile(validCount: 3, duplicateCount: 2);

        $this->assertSame(5, $compiled['stats']['valid_records']); // 3 + 2
    }

    public function test_compile_includes_skipped_array(): void
    {
        $raw = ['name' => 'Bad', 'email' => ''];
        $this->stats->addSkipped($raw, 'missing email');

        $compiled = $this->stats->compile(0, 0);

        $this->assertSame($raw,            $compiled['skipped'][0]['raw']);
        $this->assertSame('missing email', $compiled['skipped'][0]['reason']);
    }

    public function test_compile_with_no_skipped_returns_empty_array(): void
    {
        $compiled = $this->stats->compile(5, 1);
        $this->assertSame([], $compiled['skipped']);
    }

    public function test_compile_stats_structure(): void
    {
        $compiled = $this->stats->compile(3, 1);

        $this->assertArrayHasKey('total_records',     $compiled['stats']);
        $this->assertArrayHasKey('valid_records',     $compiled['stats']);
        $this->assertArrayHasKey('invalid_records',   $compiled['stats']);
        $this->assertArrayHasKey('duplicates_merged', $compiled['stats']);
    }
}

<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use BatchDataImporter\Reader\JsonLineReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JsonLineReaderTest extends TestCase
{
    private JsonLineReader $reader;
    private string         $tmpDir;

    protected function setUp(): void
    {
        $this->reader = new JsonLineReader();
        $this->tmpDir = sys_get_temp_dir() . '/rioslum_jsonline_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    private function write(string $content): string
    {
        $path = $this->tmpDir . '/test.jsonl';
        file_put_contents($path, $content);
        return $path;
    }

    public function test_reads_multiple_jsonl_lines(): void
    {
        $path = $this->write(
            '{"email":"a@b.com"}' . "\n" .
            '{"email":"c@d.com"}' . "\n"
        );

        $results = iterator_to_array($this->reader->read($path));

        $this->assertCount(2, $results);
        $this->assertSame('a@b.com', $results[0]['email']);
        $this->assertSame('c@d.com', $results[1]['email']);
    }

    public function test_skips_empty_lines(): void
    {
        $path = $this->write(
            '{"email":"a@b.com"}' . "\n" .
            "\n" .
            '{"email":"c@d.com"}' . "\n"
        );

        $results = iterator_to_array($this->reader->read($path));

        $this->assertCount(2, $results);
    }

    public function test_throws_on_missing_file(): void
    {
        $this->expectException(RuntimeException::class);
        iterator_to_array($this->reader->read('/nonexistent/path.jsonl'));
    }

    public function test_throws_on_invalid_json_line(): void
    {
        $path = $this->write("not valid json\n");

        $this->expectException(\JsonException::class);
        iterator_to_array($this->reader->read($path));
    }

    public function test_returns_iterable(): void
    {
        $path = $this->write('{"email":"a@b.com"}' . "\n");

        $result = $this->reader->read($path);

        $this->assertIsIterable($result);
    }

    public function test_empty_file_yields_nothing(): void
    {
        $path = $this->write('');

        $results = iterator_to_array($this->reader->read($path));

        $this->assertCount(0, $results);
    }
}

<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests\Reader;

use PHPUnit\Framework\TestCase;
use BatchDataImporter\Reader\JsonStreamReader;
use RuntimeException;

class JsonStreamReaderTest extends TestCase
{
    private string $tempFile;
    private JsonStreamReader $reader;

    protected function setUp(): void
    {
        $this->reader = new JsonStreamReader();
        $this->tempFile = sys_get_temp_dir() . '/stream_test_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function test_read_yields_objects_from_json_array_successfully(): void
    {
        $jsonData = [
            ['name' => 'Bruno', 'email' => 'bsouza@fedora.com'],
            ['name' => 'Caramelo', 'email' => 'caramelo@fedora.com']
        ];
        file_put_contents($this->tempFile, json_encode($jsonData));

        $generator = $this->reader->read($this->tempFile);
        $result = iterator_to_array($generator);

        $this->assertCount(count($result), $result);
        $this->assertEquals($result[0]['name'], $result[0]['name']);
        $this->assertEquals($result[1]['email'], $result[1]['email']);
    }

    public function test_read_handles_strings_with_structural_characters_inside_quotes(): void
    {
        $complexJson = [
            [
                'name' => 'Complex {Name}', 
                'bio' => 'He said: "Hello \\ World"', 
                'email' => 'test@test.com'
            ]
        ];
        file_put_contents($this->tempFile, json_encode($complexJson));

        $generator = $this->reader->read($this->tempFile);
        $result = iterator_to_array($generator);

        $this->assertCount(1, $result);
        $this->assertEquals('Complex {Name}', $result[0]['name']);
        $this->assertEquals('He said: "Hello \\ World"', $result[0]['bio']);
    }

    public function test_read_skips_malformed_json_objects_gracefully(): void
    {
        $malformedData = '[{"name": "Valid"}, {invalid-json-here}]';
        file_put_contents($this->tempFile, $malformedData);

        $generator = $this->reader->read($this->tempFile);
        $result = iterator_to_array($generator);

        $this->assertCount(1, $result);
        $this->assertEquals('Valid', $result[0]['name']);
    }

    public function test_read_throws_exception_if_file_does_not_exist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Input file not found: non_existent.json');

        iterator_to_array($this->reader->read('non_existent.json'));
    }
}
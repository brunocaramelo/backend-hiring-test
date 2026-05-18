<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests\Contact\Services;

use PHPUnit\Framework\TestCase;
use BatchDataImporter\Contact\Services\ContactFileConciliation;
use BatchDataImporter\Reader\JsonStreamReader;

class ContactFileConciliationTest extends TestCase
{
    private $fileReaderMock;
    private string $fakePath;

    protected function setUp(): void
    {
        $this->fileReaderMock = $this->createMock(JsonStreamReader::class);
        $this->fakePath = '/srv/work/sass-vowt/contacts.json';
    }

    public function test_handle_processes_valid_contacts_successfully(): void
    {
        $rawRows = [
            ['name' => 'Bruno Souza', 'email' => 'bsouza@fedora.com', 'phone' => '11999999999', 'company' => 'Fedora Labs'],
            ['name' => 'Caramelo', 'email' => 'caramelo@fedora.com', 'phone' => '11888888888', 'company' => 'Pet Shop']
        ];

        $this->fileReaderMock->expects($this->once())
            ->method('read')
            ->with($this->fakePath)
            ->willReturn($rawRows);

        $conciliation = new ContactFileConciliation($this->fileReaderMock, $this->fakePath);

        $result = $conciliation->handle();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contacts', $result);
        $this->assertCount(2, $result['contacts']);
        
        $this->assertEquals('Bruno Souza', $result['contacts'][0]->name);
        $this->assertEquals('bsouza@fedora.com', $result['contacts'][0]->email);
    }

    public function test_handle_filters_invalid_contacts_and_increments_skipped_stats(): void
    {
        $rawRows = [
            ['name' => 'Bruno Souza', 'email' => 'bsouza@fedora.com', 'phone' => '11999999999', 'company' => 'Fedora Labs'],
            ['name' => 'Invalid Contact', 'email' => '', 'phone' => '', 'company' => ''] // Sem e-mail
        ];

        $this->fileReaderMock->expects($this->once())
            ->method('read')
            ->with($this->fakePath)
            ->willReturn($rawRows);

        $conciliation = new ContactFileConciliation($this->fileReaderMock, $this->fakePath);

        $result = $conciliation->handle();

        $this->assertCount(1, $result['contacts']);
        $this->assertEquals('bsouza@fedora.com', $result['contacts'][0]->email);
        
        $this->assertArrayHasKey('skipped', $result);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_handle_combines_and_merges_duplicate_contacts(): void
    {
        $rawRows = [
            ['name' => 'Bruno Souza', 'email' => 'bsouza@fedora.com', 'phone' => '11999999999', 'company' => ''],
            ['name' => 'B. Souza', 'email' => 'bsouza@fedora.com', 'phone' => '', 'company' => 'Fedora Labs']
        ];

        $this->fileReaderMock->expects($this->once())
            ->method('read')
            ->with($this->fakePath)
            ->willReturn($rawRows);

        $conciliation = new ContactFileConciliation($this->fileReaderMock, $this->fakePath);

        $result = $conciliation->handle();

        $this->assertCount(1, $result['contacts']);
        $this->assertEquals('bsouza@fedora.com', $result['contacts'][0]->email);
    }
}
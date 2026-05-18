<?php

declare(strict_types=1);

namespace BatchDataImporter\Tests;

use PHPUnit\Framework\TestCase;
use BatchDataImporter\ContactImporter;
use BatchDataImporter\Crm\CrmClientInterface;
use BatchDataImporter\Crm\CrmResponse;
use BatchDataImporter\Crm\CrmResponseStatus;


class ContactImporterTest extends TestCase
{
    private string $tempInputFile;
    private string $tempOutputFile;
    private $crmClientMock;

    protected function setUp(): void
    {
        $this->tempInputFile = sys_get_temp_dir() . '/test_input_' . uniqid() . '.json';
        $this->tempOutputFile = sys_get_temp_dir() . '/test_output_' . uniqid() . '.json';
        
        $this->crmClientMock = $this->createMock(CrmClientInterface::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempInputFile)) {
            unlink($this->tempInputFile);
        }
        if (file_exists($this->tempOutputFile)) {
            unlink($this->tempOutputFile);
        }
    }

    public function test_run_imports_contacts_successfully_and_generates_report(): void
    {
        $mockContacts = [
            ['name' => 'Bruno Souza', 'email' => 'bsouza@fedora.com'],
            ['name' => 'Caramelo', 'email' => 'caramelo@fedora.com'],
        ];
        
        file_put_contents($this->tempInputFile, json_encode($mockContacts));

        $realResponse = new CrmResponse(CrmResponseStatus::Success, 'Success');

        $this->crmClientMock->method('send')
            ->willReturn($realResponse);

        $batchSize = 2;
        $maxAttempts = 3;
        $importer = new ContactImporter($this->crmClientMock, $batchSize, $maxAttempts);

        $report = $importer->run($this->tempInputFile, $this->tempOutputFile);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('imported', $report);
        $this->assertArrayHasKey('failed', $report);
        $this->assertArrayHasKey('skipped', $report);
        $this->assertArrayHasKey('generated_at', $report);

        $this->assertFileExists($this->tempOutputFile);
        
        $writtenReport = json_decode(file_get_contents($this->tempOutputFile), true);
        $this->assertEquals($report['summary'], $writtenReport['summary']);
    }
}
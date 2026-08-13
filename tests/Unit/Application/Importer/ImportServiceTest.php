<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Importer;

use App\Application\DTO\EntityImportDTO;
use App\Application\Importer\EntityImporterInterface;
use App\Application\Importer\ImportService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @label VERIFIED - ImportService factory pattern tests
 */
class ImportServiceTest extends TestCase
{
    private ImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function importUsesCorrectImporterForTxtFile(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        $dto = new EntityImportDTO('text', 'Test Title');
        $txtImporter->expects(self::once())
            ->method('import')
            ->with('/path/to/file.txt')
            ->willReturn($dto);

        $result = $service->import('/path/to/file.txt');

        self::assertSame('Test Title', $result->title());
        self::assertSame('text', $result->type());
    }

    #[Test]
    public function importUsesCorrectImporterForPdfFile(): void
    {
        $pdfImporter = $this->createMockImporter('.pdf', 'pdf');
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter, $pdfImporter]);

        $dto = new EntityImportDTO('book', 'PDF Title');
        $pdfImporter->expects(self::once())
            ->method('import')
            ->with('/path/to/file.pdf')
            ->willReturn($dto);

        $result = $service->import('/path/to/file.pdf');

        self::assertSame('PDF Title', $result->title());
    }

    #[Test]
    public function importThrowsExceptionWhenNoImporterSupportsFile(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No importer found for file: /path/to/file.xyz');

        $service->import('/path/to/file.xyz');
    }

    #[Test]
    public function supportsReturnsTrueForSupportedFile(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        self::assertTrue($service->supports('/path/to/file.txt'));
    }

    #[Test]
    public function supportsReturnsFalseForUnsupportedFile(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        self::assertFalse($service->supports('/path/to/file.pdf'));
    }

    #[Test]
    public function addImporterAddsNewImporterAtRuntime(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        // Initially doesn't support PDF
        self::assertFalse($service->supports('/path/to/file.pdf'));

        // Add PDF importer
        $pdfImporter = $this->createMockImporter('.pdf', 'pdf');
        $service->addImporter($pdfImporter);

        // Now supports PDF
        self::assertTrue($service->supports('/path/to/file.pdf'));
    }

    #[Test]
    public function firstMatchingImporterIsUsed(): void
    {
        // Create two importers that both support .txt
        $firstImporter = $this->createMockImporter('.txt', 'first');
        $secondImporter = $this->createMockImporter('.txt', 'second');
        
        $firstImporter->method('supports')->willReturn(true);
        $secondImporter->method('supports')->willReturn(true);
        
        $service = new ImportService([$firstImporter, $secondImporter]);

        $dto = new EntityImportDTO('book', 'First Wins');
        $firstImporter->expects(self::once())
            ->method('import')
            ->willReturn($dto);
        $secondImporter->expects(self::never())
            ->method('import');

        $service->import('/path/to/file.txt');
    }

    #[Test]
    public function importPropagatesImporterExceptions(): void
    {
        $txtImporter = $this->createMockImporter('.txt', 'text');
        $service = new ImportService([$txtImporter]);

        $txtImporter->expects(self::once())
            ->method('import')
            ->willThrowException(new RuntimeException('Import failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import failed');

        $service->import('/path/to/file.txt');
    }

    /**
     * Helper to create a mock importer.
     */
    private function createMockImporter(string $extension, string $type): EntityImporterInterface
    {
        $importer = $this->createMock(EntityImporterInterface::class);
        $importer->method('supports')
            ->willReturnCallback(function (string $filePath) use ($extension) {
                return str_ends_with(strtolower($filePath), $extension);
            });
        $importer->method('getEntityType')
            ->willReturn($type);
        
        return $importer;
    }
}

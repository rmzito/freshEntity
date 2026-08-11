<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Manuscript;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class ManuscriptTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = new EntityId('manuscript-123');
        $manuscript = new Manuscript($id, 'Test Manuscript', 'test-manuscript', 'Test Author', 'ar');
        
        $this->assertEquals('manuscript-123', $manuscript->id()->value());
        $this->assertSame('manuscript', $manuscript->type());
        $this->assertSame('Test Manuscript', $manuscript->title());
        $this->assertSame('test-manuscript', $manuscript->slug());
        $this->assertSame('Test Author', $manuscript->author());
        $this->assertSame('ar', $manuscript->language());
    }

    public function test_it_can_update_language(): void
    {
        $id = new EntityId('manuscript-123');
        $manuscript = new Manuscript($id, 'Test Manuscript', 'test-manuscript');
        
        $manuscript->updateLanguage('en');
        
        $this->assertSame('en', $manuscript->language());
    }

    public function test_type_name_is_manuscript(): void
    {
        $this->assertSame('manuscript', Manuscript::typeName());
    }
}

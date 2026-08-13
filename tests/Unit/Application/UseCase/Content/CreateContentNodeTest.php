<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Content;

use App\Application\UseCase\Content\CreateContentNode;
use App\Domain\Content\ContentNode;
use App\Domain\Content\ContentNodeType;
use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label PROPOSED - Test for Application Layer (Phase 3)
 */
final class CreateContentNodeTest extends TestCase
{
    private ContentNodeRepositoryInterface $repository;
    private CreateContentNode $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ContentNodeRepositoryInterface::class);
        $this->useCase = new CreateContentNode($this->repository);
    }

    public function testExecuteSavesNode(): void
    {
        $node = new ContentNode(
            ContentNodeId::generate(),
            EntityId::generate(),
            ContentNodeType::CHAPTER,
            'Chapter 1',
            'First chapter content',
            [],
            '/chapter-1',
            0
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->equalTo($node));

        $this->useCase->execute($node);
    }
}

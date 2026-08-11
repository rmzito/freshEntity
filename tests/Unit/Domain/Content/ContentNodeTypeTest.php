<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Content;

use Domain\Content\ContentNodeType;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class ContentNodeTypeTest extends TestCase
{
    public function test_container_types(): void
    {
        $this->assertTrue(ContentNodeType::CHAPTER->isContainer());
        $this->assertTrue(ContentNodeType::SECTION->isContainer());
        $this->assertTrue(ContentNodeType::SUBSECTION->isContainer());
    }

    public function test_leaf_types(): void
    {
        $this->assertTrue(ContentNodeType::PARAGRAPH->isLeaf());
        $this->assertTrue(ContentNodeType::NOTE->isLeaf());
        $this->assertTrue(ContentNodeType::REFERENCE->isLeaf());
        $this->assertTrue(ContentNodeType::IMAGE->isLeaf());
        $this->assertTrue(ContentNodeType::AUDIO->isLeaf());
        $this->assertTrue(ContentNodeType::VIDEO->isLeaf());
        $this->assertTrue(ContentNodeType::TRANSCRIPT->isLeaf());
        $this->assertTrue(ContentNodeType::ATTACHMENT->isLeaf());
    }

    public function test_container_types_are_not_leaf(): void
    {
        $this->assertFalse(ContentNodeType::CHAPTER->isLeaf());
        $this->assertFalse(ContentNodeType::SECTION->isLeaf());
        $this->assertFalse(ContentNodeType::SUBSECTION->isLeaf());
    }

    public function test_values_match_string(): void
    {
        $this->assertSame('chapter', ContentNodeType::CHAPTER->value);
        $this->assertSame('section', ContentNodeType::SECTION->value);
        $this->assertSame('subsection', ContentNodeType::SUBSECTION->value);
        $this->assertSame('paragraph', ContentNodeType::PARAGRAPH->value);
        $this->assertSame('note', ContentNodeType::NOTE->value);
        $this->assertSame('reference', ContentNodeType::REFERENCE->value);
        $this->assertSame('image', ContentNodeType::IMAGE->value);
        $this->assertSame('audio', ContentNodeType::AUDIO->value);
        $this->assertSame('video', ContentNodeType::VIDEO->value);
        $this->assertSame('transcript', ContentNodeType::TRANSCRIPT->value);
        $this->assertSame('attachment', ContentNodeType::ATTACHMENT->value);
    }
}

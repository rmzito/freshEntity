<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Video;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class VideoTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = new EntityId('video-123');
        $video = new Video($id, 'Test Video', 'test-video', 'Test Author', 7200, 'mp4', '/thumbnails/test.jpg');
        
        $this->assertEquals('video-123', $video->id()->value());
        $this->assertSame('video', $video->type());
        $this->assertSame('Test Video', $video->title());
        $this->assertSame('test-video', $video->slug());
        $this->assertSame('Test Author', $video->author());
        $this->assertSame(7200, $video->durationSeconds());
        $this->assertSame('mp4', $video->format());
        $this->assertSame('/thumbnails/test.jpg', $video->thumbnailPath());
    }

    public function test_it_can_update_thumbnail_path(): void
    {
        $id = new EntityId('video-123');
        $video = new Video($id, 'Test Video', 'test-video');
        
        $video->updateThumbnailPath('/thumbnails/updated.jpg');
        
        $this->assertSame('/thumbnails/updated.jpg', $video->thumbnailPath());
    }

    public function test_type_name_is_video(): void
    {
        $this->assertSame('video', Video::typeName());
    }
}

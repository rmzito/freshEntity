<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use Domain\Entity\Audio;
use Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class AudioTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = new EntityId('audio-123');
        $audio = new Audio($id, 'Test Audio', 'test-audio', 'Test Author', 3600, 'mp3');
        
        $this->assertEquals('audio-123', $audio->id()->value());
        $this->assertSame('audio', $audio->type());
        $this->assertSame('Test Audio', $audio->title());
        $this->assertSame('test-audio', $audio->slug());
        $this->assertSame('Test Author', $audio->author());
        $this->assertSame(3600, $audio->durationSeconds());
        $this->assertSame('mp3', $audio->format());
    }

    public function test_it_can_update_duration(): void
    {
        $id = new EntityId('audio-123');
        $audio = new Audio($id, 'Test Audio', 'test-audio');
        
        $audio->updateDuration(7200);
        
        $this->assertSame(7200, $audio->durationSeconds());
    }

    public function test_it_can_update_format(): void
    {
        $id = new EntityId('audio-123');
        $audio = new Audio($id, 'Test Audio', 'test-audio');
        
        $audio->updateFormat('wav');
        
        $this->assertSame('wav', $audio->format());
    }

    public function test_type_name_is_audio(): void
    {
        $this->assertSame('audio', Audio::typeName());
    }
}

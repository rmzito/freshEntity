<?php

declare(strict_types=1);

namespace Domain\Content;

use Domain\ValueObject\ContentNodeId;

/**
 * ContentNodeType enum defining the types of content nodes.
 * 
 * @label VERIFIED - Legacy system supports chapter, section, note, reference, image, audio, video node types
 */
enum ContentNodeType: string
{
    case CHAPTER = 'chapter';
    case SECTION = 'section';
    case SUBSECTION = 'subsection';
    case PARAGRAPH = 'paragraph';
    case NOTE = 'note';
    case REFERENCE = 'reference';
    case IMAGE = 'image';
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case TRANSCRIPT = 'transcript';
    case ATTACHMENT = 'attachment';

    public function isContainer(): bool
    {
        return in_array($this, [
            self::CHAPTER,
            self::SECTION,
            self::SUBSECTION,
        ], true);
    }

    public function isLeaf(): bool
    {
        return !$this->isContainer();
    }
}

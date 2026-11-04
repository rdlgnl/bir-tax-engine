<?php

namespace App\Assistant\Providers;

/**
 * A document for the assistant to read: a PDF or a photo.
 */
final readonly class Document
{
    public function __construct(
        public string $mediaType,
        public string $contents,
    ) {}

    public function isPdf(): bool
    {
        return $this->mediaType === 'application/pdf';
    }

    public function base64(): string
    {
        return base64_encode($this->contents);
    }
}

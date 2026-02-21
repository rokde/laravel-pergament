<?php

declare(strict_types=1);

it('returns 404 for a missing docs media file', function (): void {
    $this->get('/docs/media/nonexistent-file.png')->assertStatus(404);
});

it('shows a documentation page when the url ends with .md', function (): void {
    $this->get('/docs/getting-started/introduction.md')->assertStatus(200);
});

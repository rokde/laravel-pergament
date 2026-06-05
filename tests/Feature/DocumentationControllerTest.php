<?php

declare(strict_types=1);

it('returns 404 for a missing docs media file', function (): void {
    $this->get('/docs/media/nonexistent-file.png')->assertStatus(404);
});

it('serves a download file from a docs chapter directory', function (): void {
    $this->get('/docs/media/getting-started/document.txt')->assertStatus(200);
});

it('shows a documentation page when the url ends with .md', function (): void {
    $this->get('/docs/getting-started/introduction.md')->assertStatus(200);
});

it('renders sidecar css and js inline on the doc page response', function (): void {
    $response = $this->get('/docs/getting-started/introduction');

    $response->assertOk()
        ->assertSee('<style>.doc-note { background: lightyellow; }</style>', false)
        ->assertSee("<script>console.log('intro doc');</script>", false);
});

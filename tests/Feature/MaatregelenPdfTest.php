<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaatregelenPdfTest extends TestCase
{
    public function test_pdf_download_returns_valid_pdf(): void
    {
        $response = $this->post(route('maatregelen.pdf'), [
            'niveau_gebied' => '1',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaatregelenPreviewTest extends TestCase
{
    public function test_preview_includes_volume_vergelijking_for_passing_water_measures(): void
    {
        $response = $this->postJson(route('maatregelen.preview'), [
            'niveau_gebied' => '1',
            'niveau_gebouw' => '1',
            'verhard_m2' => '200',
            'bergingsnorm_mm' => '60',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'volume_vergelijking' => [
                '*' => [
                    'id',
                    'naam',
                    'eenheid',
                    'eenheid_label',
                    'qty_min',
                    'qty_max',
                    'kosten_min',
                    'kosten_max',
                ],
            ],
        ]);

        $rows = $response->json('volume_vergelijking');
        $this->assertNotEmpty($rows);

        $sedum = collect($rows)->firstWhere('id', 'sedumdak');
        $this->assertNotNull($sedum);
        $this->assertSame('m2', $sedum['eenheid']);
        $this->assertGreaterThan(0, $sedum['qty_max']);
        $this->assertGreaterThan(0, $sedum['kosten_max']);
    }
}

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

        $this->assertNull(collect($rows)->firstWhere('id', 'waterdoorlatendheid-vegetatie'));
        $this->assertNull(collect($rows)->firstWhere('id', 'tegels-eruit-groen-erin'));
        $this->assertNull(collect($rows)->firstWhere('id', 'vegetatie-lokaal-klimaat'));
    }

    public function test_sedumdak_passes_when_available_roof_is_smaller_than_required_retention(): void
    {
        $response = $this->postJson(route('maatregelen.preview'), [
            'niveau_gebouw' => '1',
            'risicos' => ['wateroverlast'],
            'verhard_m2' => '1000',
            'bergingsnorm_mm' => '60',
            'beschikbaar_dak_m2' => '100',
        ]);

        $response->assertOk();

        $sedum = collect($response->json('items'))->firstWhere('id', 'sedumdak');
        $this->assertNotNull($sedum);
        $this->assertTrue($sedum['pass']);
        $this->assertNotEmpty($sedum['warnings']);
    }

    public function test_waterbergende_verharding_passes_from_one_square_meter_available_area(): void
    {
        $response = $this->postJson(route('maatregelen.preview'), [
            'niveau_gebied' => '1',
            'risicos' => ['wateroverlast'],
            'verhard_m2' => '200',
            'bergingsnorm_mm' => '60',
            'beschikbaar_gebied_m2' => '10',
        ]);

        $response->assertOk();

        $verharding = collect($response->json('items'))->firstWhere('id', 'waterbergende-verharding');
        $this->assertNotNull($verharding);
        $this->assertTrue($verharding['pass']);
    }
}

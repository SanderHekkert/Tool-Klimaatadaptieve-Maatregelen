<?php

namespace App\Http\Controllers;

use App\Services\MaatregelFilterService;
use App\Support\BijlageExcelLegendaReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaatregelenToolController extends Controller
{
    public function __construct(
        private readonly MaatregelFilterService $filterService,
        private readonly BijlageExcelLegendaReader $legendaReader,
    ) {}

    public function index(): View
    {
        return view('maatregelen-tool.index', [
            'risicoOpties' => config('maatregelen.risico_opties', []),
            'legenda' => $this->legendaReader->read((string) config('bijlage_e.xlsx_path')),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $input = $this->normalizePreviewInput($request);
        $analysis = $this->filterService->analyze($input);

        return response()->json([
            'filter_chips' => $this->buildFilterChips($input),
            'meta' => $analysis['meta'],
            'items' => $analysis['items'],
        ]);
    }

    private function normalizePreviewInput(Request $request): array
    {
        $commaFields = ['budget', 'verhard_m2', 'bergingsnorm_m3_per_m2', 'beschikbaar_gebied_m2', 'beschikbaar_dak_m2'];
        $data = $request->all();
        foreach ($commaFields as $f) {
            if (isset($data[$f]) && is_string($data[$f])) {
                $data[$f] = str_replace(',', '.', trim($data[$f]));
            }
        }

        $risicos = $data['risicos'] ?? [];
        if (! is_array($risicos)) {
            $risicos = $risicos !== null && $risicos !== '' ? [$risicos] : [];
        }

        return [
            'budget' => isset($data['budget']) && $data['budget'] !== '' ? (float) $data['budget'] : null,
            'aantal_toepasbare_stuks' => $data['aantal_toepasbare_stuks'] ?? null,
            'niveau_gebied' => $request->boolean('niveau_gebied'),
            'niveau_gebouw' => $request->boolean('niveau_gebouw'),
            'risicos' => array_values(array_intersect(
                array_keys(config('maatregelen.risico_opties', [])),
                $risicos
            )),
            'verhard_m2' => isset($data['verhard_m2']) && $data['verhard_m2'] !== '' ? (float) $data['verhard_m2'] : null,
            'bergingsnorm_m3_per_m2' => isset($data['bergingsnorm_m3_per_m2']) && $data['bergingsnorm_m3_per_m2'] !== '' ? (float) $data['bergingsnorm_m3_per_m2'] : null,
            'beschikbaar_gebied_m2' => isset($data['beschikbaar_gebied_m2']) && $data['beschikbaar_gebied_m2'] !== '' ? (float) $data['beschikbaar_gebied_m2'] : null,
            'beschikbaar_dak_m2' => isset($data['beschikbaar_dak_m2']) && $data['beschikbaar_dak_m2'] !== '' ? (float) $data['beschikbaar_dak_m2'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{label: string, value: string}>
     */
    private function buildFilterChips(array $input): array
    {
        $risicoOpties = config('maatregelen.risico_opties', []);
        $risicoKeys = is_array($input['risicos'] ?? null) ? $input['risicos'] : [];
        $risicoLabels = [];
        foreach ($risicoKeys as $key) {
            $risicoLabels[] = $risicoOpties[$key] ?? (string) $key;
        }

        $niveauDelen = [];
        if (! empty($input['niveau_gebied'])) {
            $niveauDelen[] = 'Gebied';
        }
        if (! empty($input['niveau_gebouw'])) {
            $niveauDelen[] = 'Gebouw';
        }

        $budget = $input['budget'] ?? null;
        $budgetDisplay = $budget !== null
            ? '€ '.number_format((float) $budget, 0, ',', '.')
            : '—';

        $stuks = $input['aantal_toepasbare_stuks'] ?? null;
        $stuksDisplay = ($stuks !== null && $stuks !== '')
            ? (string) (int) $stuks
            : '—';

        return [
            ['label' => 'Budget', 'value' => $budgetDisplay],
            ['label' => 'Stuks', 'value' => $stuksDisplay],
            ['label' => 'Niveau', 'value' => $niveauDelen !== [] ? implode(' + ', $niveauDelen) : '—'],
            ['label' => 'Risico', 'value' => $risicoLabels !== [] ? implode(', ', $risicoLabels) : 'Geen filter'],
            ['label' => 'Verhard', 'value' => $this->formatOptionalNumber($input['verhard_m2'] ?? null, 0).' m²'],
            ['label' => 'Norm', 'value' => $this->formatOptionalNumber($input['bergingsnorm_m3_per_m2'] ?? null, 2).' m³/m²'],
            ['label' => 'Gebied', 'value' => $this->formatOptionalNumber($input['beschikbaar_gebied_m2'] ?? null, 0).' m²'],
            ['label' => 'Dak', 'value' => $this->formatOptionalNumber($input['beschikbaar_dak_m2'] ?? null, 0).' m²'],
        ];
    }

    private function formatOptionalNumber(mixed $value, int $decimals): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\MaatregelFilterService;
use App\Support\BijlageExcelLegendaReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MaatregelenToolController extends Controller
{
    public function __construct(
        private readonly MaatregelFilterService $filterService,
        private readonly BijlageExcelLegendaReader $legendaReader,
    ) {}

    public function home(): View
    {
        return view('maatregelen-tool.home');
    }

    public function index(): View
    {
        return view('maatregelen-tool.start', [
            'maatregelOpties' => $this->maatregelOpties(),
        ]);
    }

    public function start(Request $request): Response
    {
        $data = $request->validate([
            'overweeg_alle_maatregelen' => ['required', 'in:0,1'],
            'maatregel_ids' => ['array'],
            'maatregel_ids.*' => ['string'],
        ]);
        $allIds = array_map(
            static fn (array $maatregel): string => (string) ($maatregel['id'] ?? ''),
            config('maatregelen.maatregelen', [])
        );
        $allIds = array_values(array_filter($allIds));
        $overweegAlle = ($data['overweeg_alle_maatregelen'] ?? '1') === '1';
        $selected = $overweegAlle
            ? []
            : array_values(array_intersect($allIds, $data['maatregel_ids'] ?? []));
        if (! $overweegAlle && $selected === []) {
            return redirect()->route('maatregelen.index')->withErrors([
                'maatregel_ids' => 'Selecteer minimaal 1 maatregel.',
            ])->withInput();
        }

        session([
            'maatregelen_overweeg_alle' => $overweegAlle,
            'maatregelen_selectie_ids' => $selected,
        ]);

        return response()->redirectToRoute('maatregelen.tool');
    }

    public function tool(Request $request): Response|View
    {
        if (! $request->session()->has('maatregelen_overweeg_alle')) {
            return response()->redirectToRoute('maatregelen.index');
        }

        return view('maatregelen-tool.index', [
            'risicoOpties' => config('maatregelen.risico_opties', []),
            'legenda' => $this->legendaReader->read((string) config('bijlage_e.xlsx_path')),
            'overweegAlleMaatregelen' => (bool) session('maatregelen_overweeg_alle', true),
            'geselecteerdeMaatregelIds' => array_values((array) session('maatregelen_selectie_ids', [])),
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

    public function downloadPdf(Request $request): Response
    {
        $input = $this->normalizePreviewInput($request);
        $analysis = $this->filterService->analyze($input);
        $planQty = $this->normalizePlanQty($request->input('plan_qty', []));
        $passed = [];
        $totalCostMin = 0.0;
        $totalCostMax = 0.0;
        $totalWaterMin = 0.0;
        $hasPlanInput = false;
        foreach ($analysis['items'] as $item) {
            if (! empty($item['pass'])) {
                $qty = (float) ($planQty[$item['id'] ?? ''] ?? 0);
                $item['plan_qty'] = $qty;
                $planner = is_array($item['planner'] ?? null) ? $item['planner'] : [];
                $item['plan_cost_min'] = null;
                $item['plan_cost_max'] = null;
                if ($qty > 0) {
                    $hasPlanInput = true;
                    if (($planner['kosten_min_per_eenheid'] ?? null) !== null) {
                        $cMin = (float) $planner['kosten_min_per_eenheid'] * $qty;
                        $cMaxPer = (float) ($planner['kosten_max_per_eenheid'] ?? $planner['kosten_min_per_eenheid']);
                        $cMax = $cMaxPer * $qty;
                        $item['plan_cost_min'] = $cMin;
                        $item['plan_cost_max'] = $cMax;
                        $totalCostMin += $cMin;
                        $totalCostMax += $cMax;
                    }
                    if (($planner['water_min_per_eenheid'] ?? null) !== null) {
                        $totalWaterMin += (float) $planner['water_min_per_eenheid'] * $qty;
                    }
                }
                $passed[] = $item;
            }
        }

        $generatedAt = now()->timezone(config('app.timezone', 'UTC'))->format('d-m-Y \o\m H:i');

        $pdf = Pdf::loadView('pdf.maatregelen-rapport', [
            'title' => 'Passende klimaatadaptieve maatregelen (Bijlage E)',
            'generatedAt' => $generatedAt,
            'filterChips' => $this->buildFilterChips($input),
            'meta' => $analysis['meta'],
            'items' => $passed,
            'planSummary' => [
                'has_input' => $hasPlanInput,
                'total_cost_min' => $totalCostMin,
                'total_cost_max' => $totalCostMax,
                'remaining_water_m3' => max(0, (float) ($analysis['meta']['volume_m3'] ?? 0) - $totalWaterMin),
                'has_volume_target' => ($analysis['meta']['volume_m3'] ?? null) !== null,
            ],
        ])->setPaper('a4', 'portrait');

        $filename = 'maatregelen-bijlage-e-'.now()->format('Y-m-d-His').'.pdf';

        return $pdf->download($filename);
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

        $maatregelIds = $data['maatregel_ids'] ?? [];
        if (! is_array($maatregelIds)) {
            $maatregelIds = $maatregelIds !== null && $maatregelIds !== '' ? [$maatregelIds] : [];
        }
        $maatregelIdKeys = [];
        foreach (config('maatregelen.maatregelen', []) as $maatregel) {
            if (is_string($maatregel['id'] ?? null)) {
                $maatregelIdKeys[] = $maatregel['id'];
            }
        }

        $overweegAlleMaatregelen = ($data['overweeg_alle_maatregelen'] ?? '1') !== '0';

        return [
            'budget' => isset($data['budget']) && $data['budget'] !== '' ? (float) $data['budget'] : null,
            'aantal_toepasbare_stuks' => $data['aantal_toepasbare_stuks'] ?? null,
            'niveau_gebied' => $request->boolean('niveau_gebied'),
            'niveau_gebouw' => $request->boolean('niveau_gebouw'),
            'risicos' => array_values(array_intersect(
                array_keys(config('maatregelen.risico_opties', [])),
                $risicos
            )),
            'overweeg_alle_maatregelen' => $overweegAlleMaatregelen,
            'maatregel_ids' => $overweegAlleMaatregelen
                ? []
                : array_values(array_intersect($maatregelIdKeys, $maatregelIds)),
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

        $maatregelIds = is_array($input['maatregel_ids'] ?? null) ? $input['maatregel_ids'] : [];
        $maatregelKeuzeDisplay = ! empty($input['overweeg_alle_maatregelen'])
            ? 'Alle maatregelen'
            : ($maatregelIds === [] ? 'Geen maatregelen geselecteerd' : count($maatregelIds).' geselecteerd');

        return [
            ['label' => 'Maatregelen', 'value' => $maatregelKeuzeDisplay],
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

    /**
     * @return array<string, float>
     */
    private function normalizePlanQty(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $id => $qty) {
            if (! is_string($id)) {
                continue;
            }
            $q = is_string($qty) ? str_replace(',', '.', trim($qty)) : $qty;
            $value = is_numeric($q) ? (float) $q : 0.0;
            if ($value > 0) {
                $out[$id] = $value;
            }
        }

        return $out;
    }

    /**
     * @return list<array{id: string, naam: string}>
     */
    private function maatregelOpties(): array
    {
        $maatregelOpties = [];
        foreach (config('maatregelen.maatregelen', []) as $maatregel) {
            $id = $maatregel['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $maatregelOpties[] = [
                    'id' => $id,
                    'naam' => (string) ($maatregel['naam'] ?? $id),
                ];
            }
        }

        return $maatregelOpties;
    }
}

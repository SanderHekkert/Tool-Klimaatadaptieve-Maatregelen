<?php

namespace App\Http\Controllers;

use App\Services\MaatregelFilterService;
use App\Support\BasisgidsStorage;
use App\Support\BijlageExcelLegendaReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

    public function basisgids(): Response
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Basisgids-Klimaatadaptatie-Van-Wijnen.pdf"',
        ];

        try {
            $externalUrl = config('basisgids.external_url');
            if (is_string($externalUrl) && $externalUrl !== '') {
                return redirect()->away($externalUrl);
            }

            $fromStorage = $this->basisgidsFromObjectStorage($headers);
            if ($fromStorage !== null) {
                return $fromStorage;
            }

            foreach (config('basisgids.local_paths', []) as $path) {
                if (! is_string($path) || ! $this->isReadablePdf($path)) {
                    continue;
                }

                return response()->file($path, $headers);
            }
        } catch (Throwable $e) {
            report($e);

            abort(
                503,
                config('app.debug')
                    ? 'Basisgids-fout: '.$e->getMessage()
                    : 'De Basisgids kan nu niet worden geladen. Controleer object storage en BASISGIDS_DISK / BASISGIDS_STORAGE_PATH.',
            );
        }

        abort(
            503,
            'De Basisgids is niet gevonden in object storage. Controleer of het bestand in de bucket staat als documents/basisgids-klimaatadaptatie.pdf (of de oorspronkelijke bestandsnaam). Zet BASISGIDS_DISK op de disk-naam uit Laravel Cloud (vaak gelijk aan FILESYSTEM_DISK), of gebruik BASISGIDS_PDF_URL.',
        );
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function basisgidsFromObjectStorage(array $headers): ?Response
    {
        foreach (BasisgidsStorage::diskCandidates() as $diskName) {
            foreach (BasisgidsStorage::storagePaths() as $path) {
                $response = BasisgidsStorage::responseFromDisk($diskName, $path, $headers);
                if ($response !== null) {
                    return $response;
                }
            }
        }

        return null;
    }

    private function isReadablePdf(string $path): bool
    {
        if (! is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $magic = fread($handle, 4);
        fclose($handle);

        return $magic === '%PDF';
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
            'volume_vergelijking' => $analysis['volume_vergelijking'] ?? [],
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
            if (empty($item['pass'])) {
                continue;
            }

            $planner = is_array($item['planner'] ?? null) ? $item['planner'] : [];
            $canPlan = ! empty($planner['invoer_eenheid']);
            $qty = $canPlan ? (float) ($planQty[$item['id'] ?? ''] ?? 0) : 0.0;

            $item['plan_qty'] = $qty > 0 ? $qty : null;
            $item['plan_cost_min'] = null;
            $item['plan_cost_max'] = null;
            $item['plan_water_effect'] = null;

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

                $planWater = $this->buildPlanWaterEffect($planner, $qty);
                if ($planWater !== null) {
                    $item['plan_water_effect'] = $planWater;
                    $totalWaterMin += (float) $planWater['min_m3'];
                }
            }

            $passed[] = $item;
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
        $commaFields = [
            'budget',
            'verhard_m2',
            'bergingsnorm_mm',
            'beschikbaar_gebied_m2',
            'beschikbaar_dak_m2',
        ];
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
            'bergingsnorm_mm' => isset($data['bergingsnorm_mm']) && $data['bergingsnorm_mm'] !== '' ? (float) $data['bergingsnorm_mm'] : null,
            'bergingsnorm_m3_per_m2' => isset($data['bergingsnorm_mm']) && $data['bergingsnorm_mm'] !== ''
                ? (float) $data['bergingsnorm_mm'] / 1000
                : null,
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
            ['label' => 'Norm', 'value' => $this->formatOptionalNumber($input['bergingsnorm_mm'] ?? null, 0).' mm'],
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
     * @param  array<string, mixed>  $planner
     * @return array{min_m3: float, max_m3: float, text: string}|null
     */
    private function buildPlanWaterEffect(array $planner, float $qty): ?array
    {
        if ($qty <= 0 || ($planner['water_min_per_eenheid'] ?? null) === null) {
            return null;
        }

        $effectMin = (float) $planner['water_min_per_eenheid'];
        $effectMax = (float) ($planner['water_max_per_eenheid'] ?? $effectMin);
        $waterMin = $effectMin * $qty;
        $waterMax = $effectMax * $qty;

        $invoerEenheid = ($planner['invoer_eenheid'] ?? null) === 'm2' ? 'm²' : 'stuks';
        $effectEenheid = match ($planner['water_soort'] ?? null) {
            'per_boom' => 'm³/boom',
            'per_stuk' => 'm³/stuk',
            default => 'm³/m²',
        };
        $qtyLabel = number_format($qty, 1, ',', '.');

        if (abs($effectMax - $effectMin) < 0.000001) {
            $text = sprintf(
                '%s m³ (%s %s × %s %s)',
                number_format($waterMin, 2, ',', '.'),
                $qtyLabel,
                $invoerEenheid,
                number_format($effectMin, 3, ',', '.'),
                $effectEenheid,
            );
        } else {
            $text = sprintf(
                '%s–%s m³ (%s %s × %s–%s %s)',
                number_format($waterMin, 2, ',', '.'),
                number_format($waterMax, 2, ',', '.'),
                $qtyLabel,
                $invoerEenheid,
                number_format($effectMin, 3, ',', '.'),
                number_format($effectMax, 3, ',', '.'),
                $effectEenheid,
            );
        }

        return [
            'min_m3' => $waterMin,
            'max_m3' => $waterMax,
            'text' => $text,
        ];
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaatregelenToolRequest;
use App\Services\MaatregelFilterService;
use App\Support\BijlageExcelLegendaReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaatregelenToolController extends Controller
{
    private const SESSION_PAYLOAD_KEY = 'maatregelen_tool_payload';

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

    public function result(MaatregelenToolRequest $request): RedirectResponse
    {
        $input = $this->normalizedInput($request);

        $result = $this->filterService->filter($input);

        $request->session()->put(self::SESSION_PAYLOAD_KEY, [
            'input' => $input,
            'result' => $result,
        ]);

        return redirect()->route('maatregelen.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $payload = $request->session()->get(self::SESSION_PAYLOAD_KEY);
        if (! is_array($payload) || ! isset($payload['result'], $payload['input'])) {
            return redirect()
                ->route('maatregelen.index')
                ->with('warning', 'Er is nog geen resultaat. Vul het formulier in en klik op “Toon maatregelen”.');
        }

        /** @var array<string, mixed> $input */
        $input = $payload['input'];
        /** @var array{rows: list<array<string, mixed>>, meta: array<string, mixed>} $result */
        $result = $payload['result'];

        return view('maatregelen-tool.show', [
            'result' => $result,
            'filterChips' => $this->buildFilterChips($input),
        ]);
    }

    private function normalizedInput(MaatregelenToolRequest $request): array
    {
        return [
            'budget' => $request->input('budget') !== null && $request->input('budget') !== ''
                ? (float) $request->input('budget') : null,
            'aantal_toepasbare_stuks' => $request->input('aantal_toepasbare_stuks'),
            'niveau_gebied' => $request->boolean('niveau_gebied'),
            'niveau_gebouw' => $request->boolean('niveau_gebouw'),
            'risicos' => $request->input('risicos', []),
            'verhard_m2' => $request->input('verhard_m2') !== null && $request->input('verhard_m2') !== ''
                ? (float) $request->input('verhard_m2') : null,
            'bergingsnorm_m3_per_m2' => $request->input('bergingsnorm_m3_per_m2') !== null && $request->input('bergingsnorm_m3_per_m2') !== ''
                ? (float) $request->input('bergingsnorm_m3_per_m2') : null,
            'beschikbaar_gebied_m2' => $request->input('beschikbaar_gebied_m2') !== null && $request->input('beschikbaar_gebied_m2') !== ''
                ? (float) $request->input('beschikbaar_gebied_m2') : null,
            'beschikbaar_dak_m2' => $request->input('beschikbaar_dak_m2') !== null && $request->input('beschikbaar_dak_m2') !== ''
                ? (float) $request->input('beschikbaar_dak_m2') : null,
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

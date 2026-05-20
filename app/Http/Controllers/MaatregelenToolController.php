<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaatregelenToolRequest;
use App\Services\MaatregelFilterService;
use Illuminate\View\View;

class MaatregelenToolController extends Controller
{
    public function __construct(
        private readonly MaatregelFilterService $filterService
    ) {}

    public function index(): View
    {
        return view('maatregelen-tool.index', [
            'risicoOpties' => config('maatregelen.risico_opties', []),
            'result' => null,
        ]);
    }

    public function result(MaatregelenToolRequest $request): View
    {
        $input = [
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

        $result = $this->filterService->filter($input);

        return view('maatregelen-tool.index', [
            'risicoOpties' => config('maatregelen.risico_opties', []),
            'result' => $result,
            'input' => $input,
        ]);
    }
}

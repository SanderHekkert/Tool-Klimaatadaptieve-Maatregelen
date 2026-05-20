<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klimaatadaptieve maatregelen — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; --bg: #f6f7f9; --card: #fff; --text: #1a1d26; --muted: #5c6475; --border: #e2e6ef; --accent: #0f766e; --accent2: #115e59; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        .lead { color: var(--muted); margin: 0 0 1.5rem; max-width: 75ch; }
        .grid { display: grid; gap: 1rem; }
        @media (min-width: 720px) { .grid-2 { grid-template-columns: 1fr 1fr; } }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; }
        .card h2 { font-size: 1rem; margin: 0 0 0.75rem; }
        label { display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; }
        .hint { font-size: 0.8125rem; color: var(--muted); font-weight: 400; margin-top: 0.25rem; }
        input[type="text"], input[type="number"] { width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; }
        .checks { display: flex; flex-wrap: wrap; gap: 0.75rem 1.25rem; }
        .checks label { font-weight: 500; display: flex; align-items: center; gap: 0.4rem; margin: 0; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; background: var(--accent); color: #fff; border: 0; padding: 0.65rem 1.1rem; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: var(--accent2); }
        .error { color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { border-bottom: 1px solid var(--border); padding: 0.6rem 0.5rem; vertical-align: top; text-align: left; }
        th { background: #f0f4f8; font-weight: 600; }
        .badge { display: inline-block; font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: 6px; background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc; }
        .note { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.9rem; margin: 1rem 0; }
        .muted { color: var(--muted); }
        .stack { margin-top: 1rem; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Overzicht klimaatadaptieve maatregelen (Bijlage E)</h1>
    <p class="lead">
        Vul de gegevens in zoals in het Excel-overzicht <em>overzicht_aspecten_maatregelen_bijlage_E</em> is beschreven.
        Op basis van schaalniveau, klimaatrisico, budget, waterbergingsnorm en beschikbare oppervlakken filtert deze tool de passende maatregelen.
    </p>

    @if ($errors->any())
        <div class="note">
            <strong>Controleer de invoer.</strong>
            <ul style="margin:0.5rem 0 0 1rem;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('maatregelen.result') }}" class="card stack">
        @csrf
        <h2>1. Te besteden kosten (€)</h2>
        <div class="grid grid-2">
            <div>
                <label for="budget">Te besteden kosten</label>
                <input id="budget" name="budget" type="text" value="{{ old('budget') }}" placeholder="bijv. 8000">
                <p class="hint">Als je een bedrag invult, worden alleen maatregelen getoond die daarbij passen (minimaal één eenheid tegen de laagste investering). Bij kosten per stuk kun je het aantal toepasbare stuks invullen.</p>
            </div>
            <div>
                <label for="aantal_toepasbare_stuks">Aantal toepasbare stuks (optioneel)</label>
                <input id="aantal_toepasbare_stuks" name="aantal_toepasbare_stuks" type="number" min="1" value="{{ old('aantal_toepasbare_stuks') }}" placeholder="bijv. 10">
            </div>
        </div>

        <h2 class="stack">2. Schaalniveau</h2>
        <div class="checks">
            <label><input type="checkbox" name="niveau_gebied" value="1" @checked(old('niveau_gebied'))> Gebiedsniveau</label>
            <label><input type="checkbox" name="niveau_gebouw" value="1" @checked(old('niveau_gebouw'))> Gebouwniveau</label>
        </div>
        <p class="hint">Minimaal één niveau aanvinken. Maatregelen op “gebouw- en gebiedsniveau” worden getoond zodra één van beide past.</p>

        <h2 class="stack">3. Klimaatrisico</h2>
        <div class="checks">
            @foreach ($risicoOpties as $key => $label)
                <label>
                    <input type="checkbox" name="risicos[]" value="{{ $key }}" @checked(in_array($key, old('risicos', []), true))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <p class="hint">Meerdere risico’s mogen. Laat alles leeg om niet op risico te filteren. In Bijlage E zijn geen maatregelen gekoppeld aan alleen “overstromingsgevaar”.</p>

        <h2 class="stack">4. Verhard oppervlak (m²)</h2>
        <div>
            <label for="verhard_m2">Aandeel ‘hard’ oppervlak</label>
            <input id="verhard_m2" name="verhard_m2" type="text" value="{{ old('verhard_m2') }}" placeholder="bijv. 200">
            <p class="hint">Wordt gebruikt voor de som met de waterbergingsnorm (zie hieronder).</p>
        </div>

        <h2 class="stack">5. Te behalen waterbergingsnorm (m³ per m²)</h2>
        <div>
            <label for="bergingsnorm_m3_per_m2">Norm (m³/m²)</label>
            <input id="bergingsnorm_m3_per_m2" name="bergingsnorm_m3_per_m2" type="text" value="{{ old('bergingsnorm_m3_per_m2') }}" placeholder="bijv. 0,06">
            <p class="hint">Voorbeeld uit Bijlage E: 200 m² × 0,06 m³/m² = 12 m³ te bergen. Daarmee worden voor waterbergende maatregelen indicatieve oppervlakken of aantallen berekend.</p>
        </div>

        <h2 class="stack">6. Beschikbaar oppervlak in het gebied (m²)</h2>
        <div>
            <label for="beschikbaar_gebied_m2">Beschikbaar gebied</label>
            <input id="beschikbaar_gebied_m2" name="beschikbaar_gebied_m2" type="text" value="{{ old('beschikbaar_gebied_m2') }}" placeholder="bijv. 40">
            <p class="hint">Gebiedsmaatregelen vallen weg als het benodigde oppervlak (volgens norm en effect) groter is dan dit getal, of bij minimum-eisen (bijv. stadsbos, wadi/regen percentages).</p>
        </div>

        <h2 class="stack">7. Beschikbaar dakoppervlak (m², optioneel)</h2>
        <div>
            <label for="beschikbaar_dak_m2">Dakoppervlak</label>
            <input id="beschikbaar_dak_m2" name="beschikbaar_dak_m2" type="text" value="{{ old('beschikbaar_dak_m2') }}" placeholder="bijv. 120">
            <p class="hint">Alleen relevant voor retentiedaken: vergelijking met benodigde m² uit de waterberekening.</p>
        </div>

        <div class="stack">
            <button type="submit" class="btn">Toon maatregelen</button>
        </div>
    </form>

    @isset($result)
        @if (($result['meta']['volume_m3'] ?? null) !== null)
            <div class="note">
                <strong>Berekend te bergen volume:</strong>
                {{ number_format($result['meta']['volume_m3'], 2, ',', '.') }} m³
                (verhard oppervlak × norm).
            </div>
        @endif

        @if (count($result['meta']['risicos'] ?? []) === 1 && ($result['meta']['risicos'][0] ?? null) === 'overstromingsgevaar')
            <div class="note">
                Er staan in Bijlage E geen maatregelen die uitsluitend op “overstromingsgevaar” zijn gekoppeld.
                Vink extra risico’s aan of laat risico’s leeg om het volledige overzicht te zien.
            </div>
        @endif

        @if (count($result['rows']) === 0)
            <p class="card stack muted">Geen maatregelen voldoen aan alle criteria. Probeer een hoger budget, andere niveaus/risico’s of ruimere beschikbare oppervlakken.</p>
        @else
            <div class="card stack">
                <h2 style="margin-top:0;">Resultaat ({{ count($result['rows']) }} maatregelen)</h2>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>Maatregel</th>
                            <th>Investering</th>
                            <th>Onderhoud / jaar</th>
                            <th>Effect</th>
                            <th>Niveau</th>
                            <th>Technisch</th>
                            <th>Water / toelichting</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($result['rows'] as $row)
                            <tr>
                                <td>
                                    {{ $row['naam'] }}
                                    @if (!empty($row['bijlage_onvolledig']))
                                        <div><span class="badge">Bijlage E onvolledig</span></div>
                                    @endif
                                </td>
                                <td>{{ $row['investering_tekst'] }}</td>
                                <td>{{ $row['onderhoud_jaar'] }}</td>
                                <td>{{ $row['effect_tekst'] }}</td>
                                <td>
                                    @foreach ($row['niveaus'] ?? [] as $n)
                                        @if ($n === 'beide') Gebouw- en gebiedsniveau
                                        @elseif ($n === 'gebied') Gebiedsniveau
                                        @else Gebouwniveau
                                        @endif
                                        @if (!$loop->last), @endif
                                    @endforeach
                                </td>
                                <td>{{ $row['technisch'] }}</td>
                                <td>
                                    @if (!empty($row['_water']['toelichting']))
                                        {{ $row['_water']['toelichting'] }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                    @foreach ($row['_warnings'] ?? [] as $w)
                                        <div class="hint">{{ $w }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endisset
</div>
</body>
</html>

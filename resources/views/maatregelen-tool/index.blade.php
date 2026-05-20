@extends('maatregelen-tool.layout')

@section('title', 'Overzicht klimaatadaptieve maatregelen (Bijlage E)')

@section('content')
    <h1 class="mt-page-title">Overzicht klimaatadaptieve maatregelen</h1>
    <p class="mt-lead">
        Vul hieronder de parameters in zoals beschreven in <strong>Bijlage E</strong> van het overzicht klimaatadaptieve maatregelen.
        De tool past dezelfde filter- en rekenlogica toe en toont alleen maatregelen die bij jouw schaalniveau, risico’s, budget en beschikbare ruimte passen.
        Na verzenden ga je naar een <strong>resultaatpagina</strong> met je keuzes compact bovenaan.
        De <a href="#legenda">legenda</a> komt uit hetzelfde Excel-bestand (tab <strong>Legenda</strong>).
    </p>

    @include('maatregelen-tool.partials.legenda')

    @if (session('warning'))
        <div class="mt-alert mt-alert--warn">{{ session('warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-alert mt-alert--danger" role="alert">
            <strong>Controleer de invoer.</strong>
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('maatregelen.result') }}" class="mt-card mt-form">
        @csrf

        <section class="mt-section">
            <h2 class="mt-section__title">Te besteden kosten (€)</h2>
            <div class="mt-grid mt-grid--2">
                <div>
                    <label class="mt-label" for="budget">Te besteden kosten</label>
                    <input class="mt-input" id="budget" name="budget" type="text" value="{{ old('budget') }}" placeholder="bijv. 8000" autocomplete="off" inputmode="decimal">
                    <p class="mt-hint">Vul je een bedrag in, dan tonen we alleen maatregelen die daarbij passen (minimaal één eenheid tegen de laagste investering). Bij kosten per stuk kun je het aantal toepasbare stuks invullen.</p>
                </div>
                <div>
                    <label class="mt-label" for="aantal_toepasbare_stuks">Aantal toepasbare stuks (optioneel)</label>
                    <input class="mt-input" id="aantal_toepasbare_stuks" name="aantal_toepasbare_stuks" type="number" min="1" value="{{ old('aantal_toepasbare_stuks') }}" placeholder="bijv. 10" autocomplete="off">
                </div>
            </div>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Schaalniveau</h2>
            <div class="mt-chip-grid">
                <label class="mt-check"><input type="checkbox" name="niveau_gebied" value="1" @checked(old('niveau_gebied'))> Gebiedsniveau</label>
                <label class="mt-check"><input type="checkbox" name="niveau_gebouw" value="1" @checked(old('niveau_gebouw'))> Gebouwniveau</label>
            </div>
            <p class="mt-hint">Minimaal één niveau aanvinken. Maatregelen op “gebouw- en gebiedsniveau” verschijnen zodra één van beide niveaus past.</p>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Klimaatrisico</h2>
            <div class="mt-chip-grid">
                @foreach ($risicoOpties as $key => $label)
                    <label class="mt-check">
                        <input type="checkbox" name="risicos[]" value="{{ $key }}" @checked(in_array($key, old('risicos', []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="mt-hint">Meerdere risico’s mogen. Laat alles leeg om niet op risico te filteren. In Bijlage E zijn geen maatregelen gekoppeld aan alleen “overstromingsgevaar”.</p>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Verhard oppervlak (m²)</h2>
            <div>
                <label class="mt-label" for="verhard_m2">Aandeel ‘hard’ oppervlak</label>
                <input class="mt-input" id="verhard_m2" name="verhard_m2" type="text" value="{{ old('verhard_m2') }}" placeholder="bijv. 200" autocomplete="off" inputmode="decimal">
                <p class="mt-hint">Wordt gebruikt voor de som met de waterbergingsnorm.</p>
            </div>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Te behalen waterbergingsnorm (m³ per m²)</h2>
            <div>
                <label class="mt-label" for="bergingsnorm_m3_per_m2">Norm (m³/m²)</label>
                <input class="mt-input" id="bergingsnorm_m3_per_m2" name="bergingsnorm_m3_per_m2" type="text" value="{{ old('bergingsnorm_m3_per_m2') }}" placeholder="bijv. 0,06" autocomplete="off" inputmode="decimal">
                <p class="mt-hint">Voorbeeld uit Bijlage E: 200 m² × 0,06 m³/m² = 12 m³ te bergen. Daarmee rekent de tool voor waterbergende maatregelen indicatief m² of aantallen uit.</p>
            </div>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Beschikbaar oppervlak in het gebied (m²)</h2>
            <div>
                <label class="mt-label" for="beschikbaar_gebied_m2">Beschikbaar gebied</label>
                <input class="mt-input" id="beschikbaar_gebied_m2" name="beschikbaar_gebied_m2" type="text" value="{{ old('beschikbaar_gebied_m2') }}" placeholder="bijv. 40" autocomplete="off" inputmode="decimal">
                <p class="mt-hint">Gebiedsmaatregelen vallen weg als het benodigde oppervlak groter is dan dit getal, of bij minimum-eisen (bijv. stadsbos, wadi/regen-percentages).</p>
            </div>
        </section>

        <section class="mt-section">
            <h2 class="mt-section__title">Beschikbaar dakoppervlak (m², optioneel)</h2>
            <div>
                <label class="mt-label" for="beschikbaar_dak_m2">Dakoppervlak</label>
                <input class="mt-input" id="beschikbaar_dak_m2" name="beschikbaar_dak_m2" type="text" value="{{ old('beschikbaar_dak_m2') }}" placeholder="bijv. 120" autocomplete="off" inputmode="decimal">
                <p class="mt-hint">Alleen relevant voor retentiedaken: vergelijking met benodigde m² uit de waterberekening.</p>
            </div>
        </section>

        <div class="mt-form-actions">
            <button type="submit" class="mt-btn mt-btn--primary mt-btn--block">Toon maatregelen</button>
        </div>
    </form>
@endsection

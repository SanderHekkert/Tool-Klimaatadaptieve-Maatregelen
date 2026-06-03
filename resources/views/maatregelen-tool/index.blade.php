@extends('maatregelen-tool.layout')

@section('title', 'Overzicht klimaatadaptieve maatregelen (Bijlage E)')
@section('hide_header', '1')

@section('content')
    <p class="mt-gids-intro">
        Deze tool bevat een selectie van maatregelen; voor een volledige toelichting op deze maatregelen en een uitgebreid overzicht van alle klimaatadaptieve maatregelen, zie de
        <a href="{{ asset('documents/DUU- Basisgids klimaatadaptatie.pdf') }}" target="_blank" rel="noopener noreferrer">Basisgids Klimaatadaptatie van Van Wijnen</a>
        <span class="mt-gids-intro__meta">(PDF)</span>.
    </p>

    <div class="mt-split">
        <div class="mt-split__col mt-split__col--form">
            <h1 class="mt-page-title">Overzicht klimaatadaptieve maatregelen</h1>
            <p class="mt-lead">
                Links vul je de parameters in zoals in <strong>Bijlage E</strong>. Rechts zie je direct welke maatregelen <strong>voldoen</strong> en welke <strong>wegvallen</strong>, inclusief korte redenen.
            </p>

            <form
                id="maatregelen-form"
                class="mt-card mt-form mt-form--compact"
                onsubmit="return false;"
                data-preview-url="{{ route('maatregelen.preview') }}"
                data-pdf-url="{{ route('maatregelen.pdf') }}"
            >
                @csrf
                <input type="hidden" name="overweeg_alle_maatregelen" value="{{ !empty($overweegAlleMaatregelen) ? '1' : '0' }}">
                @if (empty($overweegAlleMaatregelen))
                    @foreach ($geselecteerdeMaatregelIds as $maatregelId)
                        <input type="hidden" name="maatregel_ids[]" value="{{ $maatregelId }}">
                    @endforeach
                @endif

                <section class="mt-section">
                    <h2 class="mt-section__title">Schaalniveau</h2>
                    <div class="mt-chip-grid">
                        <label class="mt-check"><input type="checkbox" name="niveau_gebied" value="1"> Gebiedsniveau</label>
                        <label class="mt-check"><input type="checkbox" name="niveau_gebouw" value="1"> Gebouwniveau</label>
                    </div>
                    <p class="mt-hint">Minimaal één niveau aanvinken om te kunnen filteren.</p>
                </section>

                <section class="mt-section">
                    <h2 class="mt-section__title">Klimaatrisico</h2>
                    <p class="mt-section__intro">
                        Inzicht in klimaatrisico’s via Klimaatstresstesten, het Nationaal Dashboard Toekomstbestendige Leefomgeving en NL Greenlabel; bij vragen kunnen de duurzaamheidsadviseurs uit jouw regio worden geraadpleegd.
                    </p>
                    <div class="mt-chip-grid">
                        @foreach ($risicoOpties as $key => $label)
                            <label class="mt-check">
                                <input type="checkbox" name="risicos[]" value="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-hint">Leeg laten = geen filter op risico.</p>
                </section>

                <section class="mt-section">
                    <h2 class="mt-section__title">Verhard oppervlak (m²)</h2>
                    <div>
                        <label class="mt-label" for="verhard_m2">Aandeel verhard oppervlak</label>
                        <p class="mt-field__intro">
                            Het totale oppervlak van daken, verharding en andere niet-doorlatende delen waar regenwater niet kan infiltreren.
                        </p>
                        <input class="mt-input" id="verhard_m2" name="verhard_m2" type="text" placeholder="bijv. 200 m²" autocomplete="off" inputmode="decimal">
                        <p class="mt-hint">Gebruikt voor de som met de waterbergingsnorm.</p>
                    </div>
                </section>

                <section class="mt-section">
                    <h2 class="mt-section__title">Te behalen waterbergingsnorm (liter per m²)</h2>
                    <div>
                        <label class="mt-label" for="bergingsnorm_mm">Norm (mm)</label>
                        <p class="mt-field__intro">
                            De bergingsnorm is een gemeentelijke eis; indien deze niet bekend is, kan worden uitgegaan van 60&nbsp;mm (60&nbsp;l/m²).
                        </p>
                        <input class="mt-input" id="bergingsnorm_mm" name="bergingsnorm_mm" type="text" placeholder="bijv. 60 mm" autocomplete="off" inputmode="decimal">
                        <p class="mt-hint">Bijvoorbeeld 200 m² × 60 mm = 12 m³ te bergen.</p>
                    </div>
                </section>

                <section class="mt-section">
                    <h2 class="mt-section__title">Beschikbaar oppervlak in het gebied (m²)</h2>
                    <div>
                        <label class="mt-label" for="beschikbaar_gebied_m2">Beschikbaar gebied</label>
                        <input class="mt-input" id="beschikbaar_gebied_m2" name="beschikbaar_gebied_m2" type="text" placeholder="bijv. 40 m²" autocomplete="off" inputmode="decimal">
                        <p class="mt-hint">Te klein = gebiedsmaatregelen kunnen wegvallen (oppervlak, percentages, bergings-m²).</p>
                    </div>
                </section>

                <section class="mt-section">
                    <h2 class="mt-section__title">Beschikbaar dakoppervlak (m²)</h2>
                    <div>
                        <label class="mt-label" for="beschikbaar_dak_m2">Dakoppervlak</label>
                        <input class="mt-input" id="beschikbaar_dak_m2" name="beschikbaar_dak_m2" type="text" placeholder="bijv. 120 m²" autocomplete="off" inputmode="decimal">
                        <p class="mt-hint">Alleen voor retentiedaken: vergelijking met benodigde m².</p>
                    </div>
                </section>

            </form>

        </div>

        <aside class="mt-split__col mt-split__col--results" aria-label="Live resultaat">
            <div class="mt-results-panel">
                <div class="mt-results-panel__head">
                    <h2 class="mt-results-panel__title">Resultaat</h2>
                    <p class="mt-results-panel__sub">Alle maatregelen uit Bijlage E. Voldoen of wegvallen met reden.</p>
                    <div id="mt-live-status" class="mt-live-status">Laden…</div>
                    <div class="mt-results-panel__actions">
                        <button type="button" id="mt-pdf-btn" class="mt-btn mt-btn--secondary mt-btn--small">Download als PDF</button>
                    </div>
                </div>
                <div class="mt-results-panel__body">
                    <div id="mt-live-chips" class="mt-filter"></div>
                    <div id="mt-live-meta"></div>
                    <div id="mt-live-plan"></div>
                    <div id="mt-live-pass"></div>
                    <div id="mt-live-fail"></div>
                </div>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/maatregelen-tool.js') }}?v=7" defer></script>
@endpush

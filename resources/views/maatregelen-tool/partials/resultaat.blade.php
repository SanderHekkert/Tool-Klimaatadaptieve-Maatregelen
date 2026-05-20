@if (($result['meta']['volume_m3'] ?? null) !== null || (count($result['meta']['risicos'] ?? []) === 1 && ($result['meta']['risicos'][0] ?? null) === 'overstromingsgevaar'))
    <div class="mt-result-notes">
        @if (($result['meta']['volume_m3'] ?? null) !== null)
            <div class="mt-alert mt-alert--warn">
                <strong>Berekend te bergen volume:</strong>
                {{ number_format($result['meta']['volume_m3'], 2, ',', '.') }} m³
                <span class="mt-muted">(verhard oppervlak × norm).</span>
            </div>
        @endif
        @if (count($result['meta']['risicos'] ?? []) === 1 && ($result['meta']['risicos'][0] ?? null) === 'overstromingsgevaar')
            <div class="mt-alert mt-alert--warn">
                Er staan in Bijlage E geen maatregelen die uitsluitend op “overstromingsgevaar” zijn gekoppeld.
                Vink extra risico’s aan of laat risico’s leeg om het volledige overzicht te zien.
            </div>
        @endif
    </div>
@endif

@if (count($result['rows']) === 0)
    <div class="mt-card mt-empty mt-stack" role="status">
        <strong>Geen maatregelen gevonden</strong>
        Geen enkele maatregel voldoet aan alle criteria. Probeer een hoger budget, andere niveaus of risico’s, of ruimere beschikbare oppervlakken.
    </div>
@else
    <div class="mt-card mt-card--flush">
        <div class="mt-card__head">
            <h2>Passende maatregelen</h2>
            <span class="mt-pill">{{ count($result['rows']) }} {{ count($result['rows']) === 1 ? 'maatregel' : 'maatregelen' }}</span>
        </div>
        <div class="mt-table-wrap mt-result-scroll">
            <table class="mt-table">
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
                        <td class="mt-cell-strong">
                            {{ $row['naam'] }}
                            @if (!empty($row['bijlage_onvolledig']))
                                <div class="mt-badge-row"><span class="mt-badge">Bijlage E onvolledig</span></div>
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
                                <span class="mt-muted">—</span>
                            @endif
                            @foreach ($row['_warnings'] ?? [] as $w)
                                <div class="mt-hint">{{ $w }}</div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

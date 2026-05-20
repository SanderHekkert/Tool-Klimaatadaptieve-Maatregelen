<section class="mt-card mt-stack" id="legenda" aria-labelledby="legenda-heading">
    <h2 class="mt-panel-title" id="legenda-heading">Legenda</h2>
    <p class="mt-legenda-intro">Teksten komen rechtstreeks uit de tab <strong>Legenda</strong> van het werkblad <em>overzicht_aspecten_maatregelen_bijlage_E.xlsx</em> (kolommen zoals in het bestand).</p>

    @if (($legenda['rows'] ?? []) === [])
        <div class="mt-alert mt-alert--warn">
            De legenda kon niet worden ingelezen. Controleer of het bestand staat op
            <code>resources/reference/overzicht_aspecten_maatregelen_bijlage_E.xlsx</code>
            (zie <code>config/bijlage_e.php</code>).
        </div>
    @else
        <div class="mt-legenda-wrap">
            <table class="mt-table mt-legenda-table">
                <thead>
                <tr>
                    <th>{{ $legenda['columns'][0] ?? 'Onderdeel' }}</th>
                    <th>{{ $legenda['columns'][1] ?? 'Toelichting' }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($legenda['rows'] as $row)
                    <tr>
                        <td>{{ $row['onderdeel'] }}</td>
                        <td>{!! nl2br(e($row['toelichting'])) !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

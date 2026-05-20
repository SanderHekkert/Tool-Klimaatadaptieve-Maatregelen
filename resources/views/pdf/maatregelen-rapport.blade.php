<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.35;
            color: #111;
        }
        h1 {
            font-size: 15pt;
            margin: 0 0 4pt;
            font-weight: 700;
        }
        .muted { color: #444; font-size: 9pt; margin: 0 0 14pt; }
        .note {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8pt 10pt;
            margin: 0 0 14pt;
            font-size: 9pt;
        }
        h2 {
            font-size: 11pt;
            margin: 16pt 0 6pt;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3pt;
        }
        table.filters { width: 100%; border-collapse: collapse; margin: 0 0 4pt; }
        table.filters td { padding: 4pt 6pt; border: 1px solid #ddd; vertical-align: top; }
        table.filters td.k { width: 28%; font-weight: 700; background: #f9fafb; }
        .alert {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            padding: 6pt 8pt;
            margin: 0 0 10pt;
            font-size: 9pt;
        }
        table.measures { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        table.measures th, table.measures td {
            border: 1px solid #ddd;
            padding: 5pt 6pt;
            vertical-align: top;
            text-align: left;
        }
        table.measures th { background: #f3f4f6; font-size: 9pt; }
        .m-name { font-weight: 700; }
        .small { font-size: 8.5pt; color: #333; }
        ul.warns { margin: 4pt 0 0 16pt; padding: 0; font-size: 8.5pt; }
        .empty { padding: 10pt; border: 1px dashed #ccc; text-align: center; color: #555; font-size: 9.5pt; }
        .badge { font-size: 7.5pt; font-weight: 700; color: #92400e; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">Gegenereerd op {{ $generatedAt }} — {{ config('app.name') }}</p>

    <div class="note">
        Dit document bevat <strong>alleen maatregelen die aan de ingevulde filters voldoen</strong>.
        Maatregelen die wegvallen staan hier <strong>niet</strong> in.
    </div>

    <h2>Huidige filters</h2>
    <table class="filters">
        @foreach ($filterChips as $chip)
            <tr>
                <td class="k">{{ e($chip['label']) }}</td>
                <td>{{ e($chip['value']) }}</td>
            </tr>
        @endforeach
    </table>

    @if (!empty($meta['volume_m3']))
        <div class="alert">
            <strong>Te bergen volume:</strong>
            {{ number_format((float) $meta['volume_m3'], 2, ',', '.') }} m³
        </div>
    @endif

    @if (!empty($meta['risicos']) && count($meta['risicos']) === 1 && $meta['risicos'][0] === 'overstromingsgevaar')
        <div class="alert">
            In Bijlage E zijn geen maatregelen die uitsluitend op “overstromingsgevaar” zijn gekoppeld.
        </div>
    @endif

    @if (empty($meta['niveau_selected']))
        <div class="alert">
            Er is geen schaalniveau geselecteerd; er kunnen daarom geen passende maatregelen worden bepaald.
        </div>
    @endif

    <h2>Passende maatregelen ({{ count($items) }})</h2>

    @if (count($items) === 0)
        <p class="empty">Er zijn geen maatregelen die aan alle filters voldoen. Pas de invoer links in de tool aan en download opnieuw.</p>
    @else
        <table class="measures">
            <thead>
                <tr>
                    <th style="width:22%">Maatregel</th>
                    <th style="width:14%">Investering</th>
                    <th style="width:14%">Niveau</th>
                    <th style="width:18%">Effect</th>
                    <th style="width:32%">Water / opmerkingen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $row)
                    <tr>
                        <td>
                            <div class="m-name">{{ e($row['naam']) }}</div>
                            @if (!empty($row['bijlage_onvolledig']))
                                <div class="badge">Bijlage E onvolledig</div>
                            @endif
                        </td>
                        <td class="small">{{ e($row['investering_tekst']) }}</td>
                        <td class="small">{{ e($row['niveau_label']) }}</td>
                        <td class="small">{{ e($row['effect_tekst']) }}</td>
                        <td class="small">
                            @if (!empty($row['water']['toelichting']))
                                <div>{{ e($row['water']['toelichting']) }}</div>
                            @endif
                            @if (!empty($row['warnings']))
                                <ul class="warns">
                                    @foreach ($row['warnings'] as $w)
                                        <li>{{ e($w) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (empty($row['water']['toelichting']) && empty($row['warnings']))
                                —
                            @endif
                        </td>
                    </tr>
                    @if (!empty($row['technisch']))
                        <tr>
                            <td colspan="5" class="small" style="background:#fafafa;border-top:none;padding-top:3pt;">
                                <strong>Technisch:</strong> {{ e($row['technisch']) }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>

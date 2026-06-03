<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 14mm 14mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.42;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        /* Filters = pagina 1; maatregelen starten altijd op een nieuwe pagina */
        .pdf-page--measures {
            page-break-before: always;
            break-before: page;
        }

        /* ——— Hero ——— */
        .hero-wrap {
            width: 100%;
            margin: 0 0 14pt;
            border-collapse: separate;
            border-spacing: 0;
        }

        .hero-wrap td {
            background: #0f766e;
            color: #ffffff;
            padding: 14pt 16pt 12pt;
            vertical-align: top;
        }

        .hero-kicker {
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.92;
            margin: 0 0 5pt;
        }

        .hero-title {
            font-size: 14.5pt;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin: 0 0 6pt;
        }

        .hero-meta {
            font-size: 8.5pt;
            opacity: 0.9;
            margin: 0;
            padding-top: 4pt;
            border-top: 1px solid rgba(255, 255, 255, 0.28);
        }

        .hero-pill {
            display: inline-block;
            margin-top: 6pt;
            padding: 3pt 8pt;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 3pt;
        }

        /* ——— Callout ——— */
        .callout {
            margin: 0 0 14pt;
            padding: 9pt 11pt 9pt 12pt;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-left: 3.5pt solid #14b8a6;
            border-radius: 0 4pt 4pt 0;
            font-size: 8.75pt;
            color: #134e4a;
            line-height: 1.45;
        }

        .callout strong {
            color: #0f766e;
        }

        /* ——— Section ——— */
        .sec {
            margin: 0 0 12pt;
        }

        .sec-head {
            margin: 0 0 8pt;
            padding: 0 0 4pt;
            border-bottom: 2pt solid #0f766e;
        }

        .sec-title {
            font-size: 10.5pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .sec-sub {
            font-size: 8pt;
            color: #64748b;
            margin: 2pt 0 0;
            font-weight: 600;
        }

        /* ——— Filters ——— */
        .filter-shell {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 4pt;
            overflow: hidden;
        }

        .filter-shell tr:nth-child(odd) td {
            background: #fafbfc;
        }

        .filter-shell tr:nth-child(even) td {
            background: #ffffff;
        }

        .filter-shell td {
            padding: 6pt 10pt;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .filter-shell tr:last-child td {
            border-bottom: none;
        }

        .filter-k {
            width: 30%;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }

        .filter-v {
            font-size: 9.25pt;
            font-weight: 600;
            color: #0f172a;
        }

        /* ——— Alerts ——— */
        .alerts {
            margin: 0 0 12pt;
        }

        .alert {
            padding: 7pt 9pt 7pt 10pt;
            margin: 0 0 6pt;
            font-size: 8.5pt;
            line-height: 1.4;
            border-radius: 3pt;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #78350f;
        }

        .alert:last-child {
            margin-bottom: 0;
        }

        .alert strong {
            color: #92400e;
        }

        /* ——— Empty ——— */
        .empty-box {
            text-align: center;
            padding: 22pt 16pt;
            border: 1px dashed #cbd5e1;
            border-radius: 4pt;
            background: #f8fafc;
            color: #475569;
            font-size: 9.25pt;
            line-height: 1.5;
        }

        .empty-box strong {
            display: block;
            color: #0f172a;
            font-size: 10pt;
            margin-bottom: 4pt;
        }

        /* ——— Measure cards ——— */
        .mc {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0 0 10pt;
            border: 1px solid #e2e8f0;
            border-radius: 4pt;
            overflow: hidden;
        }

        .mc:last-child {
            margin-bottom: 0;
        }

        .mc-h {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 8pt 11pt;
            vertical-align: top;
        }

        .mc-title {
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 3pt;
            line-height: 1.25;
        }

        .mc-badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #b45309;
            background: #fff7ed;
            border: 1px solid #fdba74;
            padding: 2pt 5pt;
            border-radius: 2pt;
            margin-top: 2pt;
        }

        .mc-body {
            padding: 8pt 11pt 9pt;
            vertical-align: top;
            background: #ffffff;
        }

        .kv {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 6pt;
        }

        .kv td {
            padding: 4pt 8pt 4pt 0;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }

        .kv tr:last-child td {
            border-bottom: none;
            padding-bottom: 0;
        }

        .kv-l {
            width: 22%;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            white-space: nowrap;
        }

        .kv-v {
            font-size: 8.75pt;
            color: #334155;
            font-weight: 600;
        }

        .mc-block {
            margin: 6pt 0 0;
            padding: 6pt 8pt;
            background: #f8fafc;
            border-radius: 3pt;
            border: 1px solid #e8eef4;
            font-size: 8.25pt;
            color: #475569;
            line-height: 1.42;
        }

        .mc-block--water {
            border-left: 2.5pt solid #2dd4bf;
        }

        .mc-block-label {
            font-size: 6.75pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0f766e;
            margin: 0 0 3pt;
        }

        ul.warns {
            margin: 4pt 0 0;
            padding-left: 14pt;
            font-size: 8pt;
            color: #92400e;
        }

        ul.warns li {
            margin: 0 0 2pt;
        }

        .mc-tech {
            margin: 0;
            padding: 7pt 11pt 8pt;
            background: #fafafa;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #475569;
            line-height: 1.45;
            vertical-align: top;
        }

        .mc-tech strong {
            color: #334155;
            font-weight: 700;
        }

        .dash {
            color: #cbd5e1;
        }

        .plan-box {
            margin: 0 0 12pt;
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            border-radius: 4pt;
            padding: 8pt 10pt;
            font-size: 8.5pt;
            color: #065f46;
        }
    </style>
</head>
<body>

<div class="pdf-page pdf-page--filters">

    <table class="hero-wrap" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <p class="hero-kicker">{{ config('app.name') }} · Bijlage E</p>
                <h1 class="hero-title">{{ $title }}</h1>
                <p class="hero-meta">Gegenereerd op {{ $generatedAt }}</p>
                <span class="hero-pill">Alleen voldoende maatregelen</span>
            </td>
        </tr>
    </table>

    <div class="callout">
        Dit rapport toont <strong>uitsluitend maatregelen die aan alle actieve filters voldoen</strong>.
        Maatregelen die in de tool als “wegvallen” worden getoond, worden in dit document bewust weggelaten.
    </div>

    <div class="sec">
        <div class="sec-head">
            <p class="sec-title">Invoer &amp; filters</p>
            <p class="sec-sub">Zelfde parameters als in het live-overzicht</p>
        </div>
        <table class="filter-shell" cellspacing="0" cellpadding="0">
            @foreach ($filterChips as $chip)
                <tr>
                    <td class="filter-k">{{ e($chip['label']) }}</td>
                    <td class="filter-v">{{ e($chip['value']) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    @if (! empty($meta['volume_m3']) || empty($meta['niveau_selected']))
        <div class="alerts">
            @if (! empty($meta['volume_m3']))
                <div class="alert">
                    <strong>Te bergen volume</strong> —
                    {{ number_format((float) $meta['volume_m3'], 2, ',', '.') }} m³ (verhard oppervlak × norm in mm).
                </div>
            @endif
            @if (empty($meta['niveau_selected']))
                <div class="alert">
                    <strong>Geen schaalniveau</strong> — vink in de tool minimaal gebied en/of gebouw aan om betrouwbare resultaten te krijgen.
                </div>
            @endif
        </div>
    @endif

    @if (!empty($planSummary['has_input']))
        <div class="plan-box">
            <strong>Totaal kosten (indicatie):</strong>
            @if (abs((float) ($planSummary['total_cost_min'] ?? 0) - (float) ($planSummary['total_cost_max'] ?? 0)) < 0.01)
                € {{ number_format((float) ($planSummary['total_cost_min'] ?? 0), 0, ',', '.') }}
            @else
                € {{ number_format((float) ($planSummary['total_cost_min'] ?? 0), 0, ',', '.') }}
                –
                € {{ number_format((float) ($planSummary['total_cost_max'] ?? 0), 0, ',', '.') }}
            @endif
            @if (!empty($planSummary['has_volume_target']))
                <br>
                <strong>Nog te bergen water:</strong>
                {{ number_format((float) ($planSummary['remaining_water_m3'] ?? 0), 2, ',', '.') }} m³
            @endif
        </div>
    @endif

</div>

<div class="pdf-page pdf-page--measures">

    <div class="sec">
        <div class="sec-head">
            <p class="sec-title">Passende maatregelen</p>
            <p class="sec-sub">{{ count($items) }} van {{ $meta['pass_count'] ?? count($items) }} passende maatregelen</p>
        </div>

        @if (count($items) === 0)
            <div class="empty-box">
                <strong>Geen passende maatregelen</strong>
                Er voldoen momenteel geen maatregelen aan alle filters. Pas de parameters in de tool aan en genereer opnieuw een PDF.
            </div>
        @else
            @foreach ($items as $row)
                <table class="mc" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="mc-h">
                            <p class="mc-title">{{ e($row['naam']) }}</p>
                            @if (! empty($row['bijlage_onvolledig']))
                                <span class="mc-badge">Bijlage E onvolledig</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="mc-body">
                            <table class="kv" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="kv-l">Investering</td>
                                    <td class="kv-v">{{ e($row['investering_tekst'] ?? '—') }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-l">Niveau</td>
                                    <td class="kv-v">{{ e($row['niveau_label'] ?? '—') }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-l">Effect</td>
                                    <td class="kv-v">{{ e($row['effect_tekst'] ?? '—') }}</td>
                                </tr>
                                @if (!empty($row['plan_qty']))
                                    <tr>
                                        <td class="kv-l">Jouw invoer</td>
                                        <td class="kv-v">
                                            {{ number_format((float) $row['plan_qty'], 2, ',', '.') }}
                                            {{ ($row['planner']['invoer_eenheid'] ?? null) === 'm2' ? 'm²' : 'stuks' }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            @if (($row['plan_cost_min'] ?? null) !== null)
                                <div class="mc-block">
                                    <p class="mc-block-label">Kosten bij jouw invoer</p>
                                    @if (abs((float) $row['plan_cost_min'] - (float) ($row['plan_cost_max'] ?? $row['plan_cost_min'])) < 0.01)
                                        € {{ number_format((float) $row['plan_cost_min'], 0, ',', '.') }}
                                    @else
                                        € {{ number_format((float) $row['plan_cost_min'], 0, ',', '.') }}
                                        –
                                        € {{ number_format((float) ($row['plan_cost_max'] ?? 0), 0, ',', '.') }}
                                    @endif
                                </div>
                            @endif

                            @if (! empty($row['plan_water_effect']['text']))
                                <div class="mc-block mc-block--water">
                                    <p class="mc-block-label">Waterbergingseffect bij jouw invoer</p>
                                    {{ e($row['plan_water_effect']['text']) }}
                                </div>
                            @endif

                            @if (! empty($row['warnings']))
                                <div class="mc-block" style="border-left:2.5pt solid #f59e0b;">
                                    <p class="mc-block-label" style="color:#b45309;">Let op</p>
                                    <ul class="warns">
                                        @foreach ($row['warnings'] as $w)
                                            <li>{{ e($w) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($row['water']['toelichting']))
                                <div class="mc-block mc-block--water">
                                    <p class="mc-block-label">Waterberging (indicatie)</p>
                                    {{ e($row['water']['toelichting']) }}
                                </div>
                            @endif

                            @if (empty($row['planner']['invoer_eenheid'] ?? null))
                                <p class="dash" style="margin:0;font-size:8.5pt;">Geen m²/stuks-invoer voor deze maatregel.</p>
                            @elseif (empty($row['plan_water_effect']['text']) && empty($row['warnings']) && ($row['plan_cost_min'] ?? null) === null && empty($row['water']['toelichting']))
                                <p class="dash" style="margin:0;font-size:8.5pt;">Vul optioneel m² of stuks in de tool in voor kosten en waterberging bij jouw invoer.</p>
                            @endif
                        </td>
                    </tr>
                    @if (! empty($row['technisch']))
                        <tr>
                            <td class="mc-tech">
                                <strong>Technisch</strong> — {{ e($row['technisch']) }}
                            </td>
                        </tr>
                    @endif
                </table>
            @endforeach
        @endif
    </div>

</div>

</body>
</html>

<?php

namespace App\Services;

class MaatregelFilterService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{rows: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function filter(array $input): array
    {
        $measures = config('maatregelen.maatregelen', []);
        $userGebied = ! empty($input['niveau_gebied']);
        $userGebouw = ! empty($input['niveau_gebouw']);
        $risicos = array_values(array_intersect(
            array_keys(config('maatregelen.risico_opties', [])),
            $input['risicos'] ?? []
        ));
        $budget = $input['budget'] ?? null;
        $aantalStuks = $input['aantal_toepasbare_stuks'] ?? null;
        $verhardM2 = $input['verhard_m2'] ?? null;
        $norm = $input['bergingsnorm_m3_per_m2'] ?? null;
        $beschikbaarGebied = $input['beschikbaar_gebied_m2'] ?? null;
        $beschikbaarDak = $input['beschikbaar_dak_m2'] ?? null;

        $volumeM3 = null;
        if ($verhardM2 !== null && $verhardM2 > 0 && $norm !== null && $norm > 0) {
            $volumeM3 = (float) $verhardM2 * (float) $norm;
        }

        $rows = [];
        foreach ($measures as $m) {
            $reasons = [];
            if (! $this->matchesNiveau($m, $userGebied, $userGebouw)) {
                continue;
            }
            if (! $this->matchesRisico($m, $risicos)) {
                continue;
            }
            if ($budget !== null && ! $this->matchesBudget($m, (float) $budget, $aantalStuks ? (int) $aantalStuks : null, $reasons)) {
                continue;
            }

            $water = $this->waterberekening($m, $volumeM3);
            if ((($water ?? [])['exclude'] ?? false) === true) {
                continue;
            }

            if (! $this->matchesBeschikbaarGebied($m, $volumeM3, $verhardM2, $beschikbaarGebied, $water, $reasons)) {
                continue;
            }

            if (! $this->matchesBeschikbaarDak($m, $water, $beschikbaarDak, $reasons)) {
                continue;
            }

            $rows[] = array_merge($m, [
                '_water' => $water,
                '_warnings' => $reasons,
            ]);
        }

        return [
            'rows' => $rows,
            'meta' => [
                'volume_m3' => $volumeM3,
                'risicos' => $risicos,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $m
     */
    private function matchesNiveau(array $m, bool $userGebied, bool $userGebouw): bool
    {
        if (! $userGebied && ! $userGebouw) {
            return false;
        }

        foreach ($m['niveaus'] ?? [] as $lvl) {
            if ($lvl === 'beide' && ($userGebied || $userGebouw)) {
                return true;
            }
            if ($lvl === 'gebied' && $userGebied) {
                return true;
            }
            if ($lvl === 'gebouw' && $userGebouw) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $m
     * @param  list<string>  $risicos
     */
    private function matchesRisico(array $m, array $risicos): bool
    {
        if ($risicos === []) {
            return true;
        }

        $mr = $m['risicos'] ?? [];
        if ($mr === []) {
            return false;
        }

        return count(array_intersect($mr, $risicos)) > 0;
    }

    /**
     * @param  array<string, mixed>  $m
     * @param  list<string>  $reasons
     */
    private function matchesBudget(array $m, float $budget, ?int $aantalStuks, array &$reasons): bool
    {
        $eenheid = $m['investering_eenheid'] ?? 'onbekend';
        $min = $m['investering_min'] ?? null;
        $max = $m['investering_max'] ?? null;

        if ($eenheid === 'onbekend' || $min === null) {
            return true;
        }

        if ($eenheid === 'stuk') {
            $stuks = max(1, $aantalStuks ?? 1);
            if ($budget + 1e-6 < $min * $stuks) {
                return false;
            }
            if ($max !== null && $budget + 1e-6 < $max && $aantalStuks === null) {
                $reasons[] = 'Let op: er is geen aantal stuks ingevuld; controle gebruikt minimaal 1 stuk tegen de minimale investering.';
            }

            return true;
        }

        if ($eenheid === 'm2') {
            return $budget + 1e-6 >= (float) $min;
        }

        if ($eenheid === 'project') {
            return $budget + 1e-6 >= (float) $min;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $m
     * @return array<string, mixed>|null
     */
    private function waterberekening(array $m, ?float $volumeM3): ?array
    {
        if ($volumeM3 === null || $volumeM3 <= 0) {
            return null;
        }

        $wb = $m['waterberging'] ?? null;
        if (! is_array($wb)) {
            return null;
        }

        $soort = $wb['soort'] ?? null;
        if ($soort === 'per_m2') {
            $eMin = (float) ($wb['min_m3'] ?? 0);
            $eMax = (float) ($wb['max_m3'] ?? $eMin);
            if ($eMin <= 0) {
                return null;
            }
            $m2Gunstig = $volumeM3 / $eMax;
            $m2Ongunstig = $volumeM3 / $eMin;

            return [
                'soort' => 'per_m2',
                'volume_m3' => $volumeM3,
                'm2_bij_meeste_effect' => round($m2Gunstig, 1),
                'm2_bij_minste_effect' => round($m2Ongunstig, 1),
                'toelichting' => sprintf(
                    'Benodigde oppervlakte (waterberging): ca. %s m² bij hoogste effect (%s m³/m²) tot ca. %s m² bij laagste effect (%s m³/m²).',
                    number_format($m2Gunstig, 1, ',', '.'),
                    number_format($eMax, 3, ',', '.'),
                    number_format($m2Ongunstig, 1, ',', '.'),
                    number_format($eMin, 3, ',', '.'),
                ),
            ];
        }

        if ($soort === 'per_boom') {
            $eMin = (float) ($wb['min_m3'] ?? 0);
            $eMax = (float) ($wb['max_m3'] ?? $eMin);
            if ($eMin <= 0) {
                return null;
            }
            $bomenMin = (int) ceil($volumeM3 / $eMax);
            $bomenMax = (int) ceil($volumeM3 / $eMin);

            return [
                'soort' => 'per_boom',
                'volume_m3' => $volumeM3,
                'bomen_bij_meeste_effect' => $bomenMin,
                'bomen_bij_minste_effect' => $bomenMax,
                'toelichting' => sprintf(
                    'Benodigde aantal bomen (indicatief): minimaal %d bij %s m³/boom tot maximaal %d bij %s m³/boom.',
                    $bomenMin,
                    number_format($eMax, 2, ',', '.'),
                    $bomenMax,
                    number_format($eMin, 2, ',', '.'),
                ),
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $water
     * @param  list<string>  $reasons
     */
    private function matchesBeschikbaarGebied(
        array $m,
        ?float $volumeM3,
        mixed $verhardM2,
        mixed $beschikbaarGebied,
        ?array $water,
        array &$reasons
    ): bool {
        if ($beschikbaarGebied === null || $beschikbaarGebied === '') {
            return true;
        }
        $besch = (float) $beschikbaarGebied;
        if ($besch <= 0) {
            return true;
        }

        $isGebied = in_array('gebied', $m['niveaus'] ?? [], true) || in_array('beide', $m['niveaus'] ?? [], true);
        if (! $isGebied) {
            return true;
        }

        if (isset($m['min_oppervlak_gebied_m2'])) {
            $minG = (float) $m['min_oppervlak_gebied_m2'];
            if ($besch + 1e-6 < $minG) {
                return false;
            }
        }

        if ($volumeM3 !== null && $verhardM2 !== null && isset($m['min_oppervlak_pct_verhard'])) {
            $pct = $m['min_oppervlak_pct_verhard'];
            $pMax = (float) ($pct['max'] ?? 0);
            if ($pMax > 0) {
                $nodig = (float) $verhardM2 * $pMax;
                if ($besch + 1e-6 < $nodig) {
                    return false;
                }
            }
        }

        if ($water !== null && ($water['soort'] ?? null) === 'per_m2') {
            $nodigM2 = (float) ($water['m2_bij_minste_effect'] ?? 0);
            if ($nodigM2 > 0 && $besch + 1e-6 < $nodigM2) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $water
     * @param  list<string>  $reasons
     */
    private function matchesBeschikbaarDak(array $m, ?array $water, mixed $beschikbaarDak, array &$reasons): bool
    {
        if ($beschikbaarDak === null || $beschikbaarDak === '') {
            if (($m['id'] ?? '') === 'groen-blauwe-daken' && isset($m['min_dakoppervlak_tip_m2'])) {
                $reasons[] = 'Tip uit Bijlage E: retentiedak is nuttig vanaf circa '.$m['min_dakoppervlak_tip_m2'].' m² dakoppervlak.';
            }

            return true;
        }

        $dak = (float) $beschikbaarDak;
        if ($dak <= 0) {
            return true;
        }

        if (($m['id'] ?? '') !== 'groen-blauwe-daken') {
            return true;
        }

        if ($water !== null && ($water['soort'] ?? null) === 'per_m2') {
            $nodig = (float) ($water['m2_bij_minste_effect'] ?? 0);
            if ($nodig > 0 && $dak + 1e-6 < $nodig) {
                return false;
            }
        }

        return true;
    }
}

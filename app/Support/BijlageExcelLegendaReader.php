<?php

namespace App\Support;

use ZipArchive;

/**
 * Leest de tab "Legenda" uit het Bijlage E xlsx (Office Open XML).
 */
class BijlageExcelLegendaReader
{
    private const WORKBOOK_MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @return array{columns: list<string>, rows: list<array{onderdeel: string, toelichting: string}>}
     */
    public function read(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            return ['columns' => ['Onderdeel', 'Toelichting'], 'rows' => []];
        }

        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            return ['columns' => ['Onderdeel', 'Toelichting'], 'rows' => []];
        }

        try {
            $sheetPath = $this->resolveLegendaWorksheetPath($zip);
            if ($sheetPath === null) {
                return ['columns' => ['Onderdeel', 'Toelichting'], 'rows' => []];
            }

            $sharedStrings = $this->readSharedStrings($zip);
            $grid = $this->readWorksheetGrid($zip, $sheetPath, $sharedStrings);

            return $this->gridToLegendaTable($grid);
        } finally {
            $zip->close();
        }
    }

    private function resolveLegendaWorksheetPath(ZipArchive $zip): ?string
    {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        if ($wbXml === false) {
            return null;
        }

        $wb = simplexml_load_string($wbXml);
        if ($wb === false) {
            return null;
        }

        $wb->registerXPathNamespace('m', self::WORKBOOK_MAIN_NS);
        $wb->registerXPathNamespace('r', self::REL_NS);

        $matches = $wb->xpath("//m:sheets/m:sheet[@name='Legenda']");
        if ($matches === false || $matches === [] || ! isset($matches[0])) {
            return null;
        }

        /** @var \SimpleXMLElement $sheetEl */
        $sheetEl = $matches[0];
        $rid = (string) $sheetEl->attributes(self::REL_NS)['id'];
        if ($rid === '') {
            return null;
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return null;
        }

        $rels = simplexml_load_string($relsXml);
        if ($rels === false) {
            return null;
        }

        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $rid) {
                $target = (string) $rel['Target'];
                $target = ltrim($target, '/');
                if (str_starts_with($target, 'xl/')) {
                    return $target;
                }

                return 'xl/'.$target;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $ss = simplexml_load_string($xml);
        if ($ss === false) {
            return [];
        }

        $strings = [];
        foreach ($ss->si as $si) {
            $t = '';
            if (isset($si->t)) {
                $t .= (string) $si->t;
            }
            if (isset($si->r)) {
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $t .= (string) $r->t;
                    }
                }
            }
            $strings[] = str_replace(["\r\n", "\r"], "\n", $t);
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return array<int, array<string, string>> rowIndex => [ 'A' => '...', 'B' => '...' ]
     */
    private function readWorksheetGrid(ZipArchive $zip, string $worksheetPath, array $sharedStrings): array
    {
        $xml = $zip->getFromName($worksheetPath);
        if ($xml === false) {
            return [];
        }

        $sheet = simplexml_load_string($xml);
        if ($sheet === false) {
            return [];
        }

        $sheet->registerXPathNamespace('m', self::WORKBOOK_MAIN_NS);
        $rows = $sheet->xpath('//m:sheetData/m:row');
        if ($rows === false) {
            return [];
        }

        $grid = [];
        foreach ($rows as $row) {
            $r = (int) $row['r'];
            $grid[$r] = $grid[$r] ?? [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                if ($ref === '') {
                    continue;
                }
                $col = $this->columnLettersFromCellRef($ref);
                $type = (string) $c['t'];
                $raw = isset($c->v) ? (string) $c->v : '';
                if ($type === 's' && $raw !== '') {
                    $grid[$r][$col] = $sharedStrings[(int) $raw] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $grid[$r][$col] = (string) $c->is->t;
                } else {
                    $grid[$r][$col] = $raw;
                }
            }
        }

        ksort($grid);

        return $grid;
    }

    private function columnLettersFromCellRef(string $ref): string
    {
        return preg_replace('/\d+/', '', $ref) ?: '';
    }

    /**
     * @param  array<int, array<string, string>>  $grid
     * @return array{columns: list<string>, rows: list<array{onderdeel: string, toelichting: string}>}
     */
    private function gridToLegendaTable(array $grid): array
    {
        if ($grid === []) {
            return ['columns' => ['Onderdeel', 'Toelichting'], 'rows' => []];
        }

        $rowIndices = array_keys($grid);
        sort($rowIndices);
        $firstRow = $grid[$rowIndices[0]] ?? [];
        $colA = $firstRow['A'] ?? 'Onderdeel';
        $colB = $firstRow['B'] ?? 'Toelichting';

        $body = [];
        for ($i = 1; $i < count($rowIndices); $i++) {
            $idx = $rowIndices[$i];
            $line = $grid[$idx] ?? [];
            $body[] = [
                'onderdeel' => $line['A'] ?? '',
                'toelichting' => $line['B'] ?? '',
            ];
        }

        return [
            'columns' => [$colA, $colB],
            'rows' => $body,
        ];
    }
}

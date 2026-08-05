<?php

namespace App\Services\ImportExport;

use DateTimeInterface;
use Generator;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

/**
 * Reads an uploaded .xlsx into associative rows keyed by the expected header
 * names. The header row must match the template exactly (same names, same
 * order) so files exported by this app round-trip without surprises.
 */
class SpreadsheetReader
{
    /** Hard ceiling — imports run synchronously in the request. */
    public const MAX_ROWS = 5000;

    /**
     * @param  list<string>  $expectedHeaders
     * @return Generator<int, array<string, string>> spreadsheet row number => [header => trimmed value]
     *
     * @throws ImportException on unreadable file, header mismatch, or row cap
     */
    public function rows(string $path, array $expectedHeaders): Generator
    {
        $reader = new Reader();

        try {
            $reader->open($path);
        } catch (Throwable) {
            throw new ImportException('The file could not be read as an Excel (.xlsx) spreadsheet.');
        }

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $headerSeen = false;
                $dataRows = 0;

                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    $cells = array_map($this->stringify(...), $row->toArray());

                    if (! $headerSeen) {
                        $this->assertHeader($cells, $expectedHeaders);
                        $headerSeen = true;

                        continue;
                    }

                    // Pad/trim to the header width, then skip fully blank rows.
                    $cells = array_slice(array_pad($cells, count($expectedHeaders), ''), 0, count($expectedHeaders));
                    if (trim(implode('', $cells)) === '') {
                        continue;
                    }

                    if (++$dataRows > self::MAX_ROWS) {
                        throw new ImportException('The file has more than ' . number_format(self::MAX_ROWS) . ' rows — split it into smaller files.');
                    }

                    yield $rowNumber => array_combine($expectedHeaders, $cells);
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
        }
    }

    private function assertHeader(array $cells, array $expected): void
    {
        $got = array_map(fn ($c) => mb_strtolower(trim((string) $c)), array_slice($cells, 0, count($expected)));

        if ($got !== $expected) {
            throw new ImportException(
                'The header row does not match the template. Expected columns: ' . implode(', ', $expected)
                . '. Download the template from the import page and paste your data into it.'
            );
        }
    }

    /** Excel cells arrive typed (DateTime, float, bool) — flatten to trimmed strings. */
    private function stringify(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_float($value)) {
            // 12.0 → "12", 12.50 → "12.5" — avoids "12.0" failing integer-ish validation.
            return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    // Cell normalizers shared by the importers ---------------------------------

    /** 1/0/yes/no/true/false → bool; blank → null (= keep existing / column default). */
    public static function bool(string $value): ?bool
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            '' => null,
            '1', 'yes', 'true', 'y' => true,
            '0', 'no', 'false', 'n' => false,
            default => null,
        };
    }

    /** Blank → null, anything else trimmed. */
    public static function nullableString(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}

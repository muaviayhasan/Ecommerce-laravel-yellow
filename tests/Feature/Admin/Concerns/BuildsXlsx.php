<?php

namespace Tests\Feature\Admin\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/** Build .xlsx uploads for import tests and read exported .xlsx responses back. */
trait BuildsXlsx
{
    /** @param list<string> $headers @param list<array> $rows */
    private function xlsxUpload(array $headers, array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn ($c) => $c === null ? '' : $c, $row)));
        }
        $writer->close();

        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /** Capture a streamed xlsx download and return all its rows as arrays of strings. */
    private function xlsxRows(TestResponse $response): array
    {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'exp') . '.xlsx';
        file_put_contents($path, $content);

        $reader = new Reader();
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($c) => is_scalar($c) || $c === null ? trim((string) $c) : $c, $row->toArray());
            }
            break;
        }
        $reader->close();
        @unlink($path);

        return $rows;
    }

    /** Map a header row + data row into [header => value]. */
    private function rowAssoc(array $headers, array $row): array
    {
        return array_combine($headers, array_slice(array_pad($row, count($headers), ''), 0, count($headers)));
    }
}

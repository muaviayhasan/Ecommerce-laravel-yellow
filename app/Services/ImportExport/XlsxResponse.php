<?php

namespace App\Services\ImportExport;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams headers + rows as an .xlsx download without buffering the workbook
 * in memory — openspout writes each row straight to php://output.
 */
class XlsxResponse
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array>  $rows  each a flat list of scalar cells in header order
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_map(
                    fn ($cell) => $cell === null ? '' : $cell,
                    $row
                )));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

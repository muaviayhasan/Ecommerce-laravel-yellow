<?php

namespace App\Services\ImportExport;

/**
 * Outcome of one spreadsheet import: counters plus the per-row error list the
 * import page renders back to the admin. Errors carry the 1-based spreadsheet
 * row number so the admin can find the cell in Excel.
 */
class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $failed = 0;

    /** @var array<int, array{row: int, message: string}> */
    public array $errors = [];

    public function error(int $row, string $message): void
    {
        $this->failed++;
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    /** A non-fatal note (e.g. unknown image path) — listed without counting the row as failed. */
    public function warning(int $row, string $message): void
    {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }
}

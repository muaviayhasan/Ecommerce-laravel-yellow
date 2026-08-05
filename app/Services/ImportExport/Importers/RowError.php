<?php

namespace App\Services\ImportExport\Importers;

use RuntimeException;

/**
 * One bad spreadsheet row (or product group). Thrown inside the row's
 * transaction so only that row rolls back; the batch continues and the
 * message lands in the ImportResult error list.
 */
class RowError extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $row = null)
    {
        parent::__construct($message);
    }
}

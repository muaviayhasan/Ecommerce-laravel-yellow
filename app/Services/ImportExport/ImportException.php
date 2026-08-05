<?php

namespace App\Services\ImportExport;

use RuntimeException;

/**
 * A whole-file problem (unreadable xlsx, wrong header row, row cap breached).
 * The message is admin-facing — controllers flash it verbatim.
 */
class ImportException extends RuntimeException
{
}

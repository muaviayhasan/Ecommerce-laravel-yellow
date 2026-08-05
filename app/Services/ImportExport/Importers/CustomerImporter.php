<?php

namespace App\Services\ImportExport\Importers;

use App\Models\Customer;
use App\Services\ImportExport\Exporters\CustomerExporter;
use App\Services\ImportExport\ImportResult;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Upserts customers from an .xlsx (headers = CustomerExporter::HEADERS).
 * Match ladder: email (case-insensitive, when non-blank) → phone (when
 * non-blank) → always create. A key shared by 2+ customers = row error.
 *
 * Validation mirrors App\Http\Requests\Admin\CustomerRequest. user_id (the
 * storefront-login link) is never touched.
 */
class CustomerImporter
{
    public function __construct(private readonly SpreadsheetReader $reader)
    {
    }

    public function import(string $path): ImportResult
    {
        $result = new ImportResult();

        foreach ($this->reader->rows($path, CustomerExporter::HEADERS) as $rowNumber => $row) {
            try {
                DB::transaction(function () use ($row, $result) {
                    $this->upsertRow($row, $result);
                });
            } catch (RowError $e) {
                $result->error($rowNumber, $e->getMessage());
            }
        }

        return $result;
    }

    /** @throws RowError */
    private function upsertRow(array $row, ImportResult $result): void
    {
        $existing = $this->match($row);

        // Blank cells never overwrite an existing value; on create they use defaults.
        $keep = fn (string $col, mixed $default = null) => $row[$col] !== '' ? $row[$col] : ($existing?->{$col} ?? $default);

        $data = [
            'name' => $row['name'] !== '' ? $row['name'] : ($existing->name ?? ''),
            'email' => $keep('email'),
            'phone' => $keep('phone'),
            'address' => $keep('address'),
            'type' => $row['type'] !== '' ? mb_strtolower($row['type']) : ($existing->type ?? 'retail'),
            'price_tier' => $row['price_tier'] !== '' ? mb_strtolower($row['price_tier']) : ($existing->price_tier ?? 'retail'),
            'opening_balance' => $keep('opening_balance', 0),
            'notes' => $keep('notes'),
        ];

        $isActive = SpreadsheetReader::bool($row['is_active']);
        if ($isActive !== null || ! $existing) {
            $data['is_active'] = $isActive ?? ($existing->is_active ?? true);
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:retail,wholesale'],
            'price_tier' => ['required', 'in:retail,wholesale'],
            'opening_balance' => ['required', 'numeric', 'between:-9999999999999,9999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            throw new RowError($validator->errors()->first());
        }

        if ($existing) {
            $existing->update($data);
            $result->updated++;
        } else {
            Customer::create($data);
            $result->created++;
        }
    }

    /** @throws RowError on an ambiguous key */
    private function match(array $row): ?Customer
    {
        foreach ([['email', mb_strtolower(trim($row['email']))], ['phone', trim($row['phone'])]] as [$column, $value]) {
            if ($value === '') {
                continue;
            }

            $matches = Customer::whereRaw("LOWER({$column}) = ?", [$value])->limit(2)->get();

            if ($matches->count() > 1) {
                throw new RowError("Ambiguous match — more than one customer shares the {$column} '{$value}'. Update those customers manually.");
            }
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        return null;
    }
}

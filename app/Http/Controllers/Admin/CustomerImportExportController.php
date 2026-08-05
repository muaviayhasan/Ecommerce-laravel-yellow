<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportExport\Exporters\CustomerExporter;
use App\Services\ImportExport\ImportException;
use App\Services\ImportExport\Importers\CustomerImporter;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CustomerImportExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:customers.export', only: ['export']),
            new Middleware('can:customers.import', only: ['importForm', 'import', 'template']),
        ];
    }

    public function export(Request $request, CustomerExporter $exporter): Response|RedirectResponse
    {
        if ($request->string('format')->toString() === 'pdf') {
            return $exporter->pdf($request)
                ?? back()->with('error', 'PDF export is capped at ' . number_format(CustomerExporter::PDF_ROW_CAP) . ' rows — use the Excel export instead.');
        }

        return $exporter->xlsx($request);
    }

    public function importForm(): View
    {
        return view('admin.import.show', $this->page());
    }

    public function import(Request $request, CustomerImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']]);

        try {
            $result = $importer->import($request->file('file')->getRealPath());
        } catch (ImportException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('status', "Import finished — {$result->created} created, {$result->updated} updated" . ($result->failed ? ", {$result->failed} failed" : '') . '.')
            ->with('import_result', $result->toArray());
    }

    public function template(CustomerExporter $exporter): Response
    {
        return $exporter->template();
    }

    private function page(): array
    {
        return [
            'title' => 'Import Customers',
            'entity' => 'customers',
            'indexRoute' => 'admin.customers.index',
            'postRoute' => 'admin.customers.import.store',
            'templateRoute' => 'admin.customers.import.template',
            'maxRows' => SpreadsheetReader::MAX_ROWS,
            'columns' => [
                ['name', 'Yes', 'Customer or business name.'],
                ['email', 'No', 'First match key (case-insensitive).'],
                ['phone', 'No', 'Second match key, used when email is blank.'],
                ['address', 'No', 'Up to 1,000 characters.'],
                ['type', 'No', 'retail or wholesale. Blank keeps the current value (new customers: retail).'],
                ['price_tier', 'No', 'retail or wholesale. Blank keeps the current value (new customers: retail).'],
                ['opening_balance', 'No', 'Receivable balance. Blank keeps the current value (new customers: 0).'],
                ['is_active', 'No', '1/0 (also yes/no). Blank keeps the current value.'],
                ['notes', 'No', 'Up to 2,000 characters.'],
            ],
            'notes' => [
                'Rows are matched by email first, then by phone when email is blank; rows with neither always create a new customer.',
                'If an email or phone matches more than one existing customer, the row is skipped with an error — update those customers manually.',
                'Blank cells never overwrite existing data.',
                'The link between a customer and their storefront login is never changed by an import.',
            ],
        ];
    }
}

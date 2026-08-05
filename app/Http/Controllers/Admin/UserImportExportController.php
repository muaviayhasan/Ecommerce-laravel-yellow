<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportExport\Exporters\UserExporter;
use App\Services\ImportExport\ImportException;
use App\Services\ImportExport\Importers\UserImporter;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UserImportExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.export', only: ['export']),
            new Middleware('can:users.import', only: ['importForm', 'import', 'template']),
        ];
    }

    public function export(Request $request, UserExporter $exporter): Response|RedirectResponse
    {
        if ($request->string('format')->toString() === 'pdf') {
            return $exporter->pdf($request)
                ?? back()->with('error', 'PDF export is capped at ' . number_format(UserExporter::PDF_ROW_CAP) . ' rows — use the Excel export instead.');
        }

        return $exporter->xlsx($request);
    }

    public function importForm(): View
    {
        return view('admin.import.show', $this->page());
    }

    public function import(Request $request, UserImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']]);

        try {
            $result = $importer->import($request->file('file')->getRealPath(), $request->user());
        } catch (ImportException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('status', "Import finished — {$result->created} created, {$result->updated} updated" . ($result->failed ? ", {$result->failed} failed" : '') . '.')
            ->with('import_result', $result->toArray());
    }

    public function template(UserExporter $exporter): Response
    {
        return $exporter->template();
    }

    private function page(): array
    {
        return [
            'title' => 'Import Users',
            'entity' => 'users',
            'indexRoute' => 'admin.users.index',
            'postRoute' => 'admin.users.import.store',
            'templateRoute' => 'admin.users.import.template',
            'maxRows' => SpreadsheetReader::MAX_ROWS,
            'columns' => [
                ['name', 'Yes', 'Full name.'],
                ['email', 'Yes', 'Match key. An existing email updates that user; a new one creates an account.'],
                ['phone', 'No', 'Contact number.'],
                ['password', 'No', 'Plaintext, min 8 characters — stored hashed. Blank keeps the current password (new accounts get a random unguessable one; no email is sent).'],
                ['roles', 'No', 'Pipe-separated role names, e.g. cashier|sales-rep. Blank leaves roles unchanged.'],
                ['is_active', 'No', '1/0 (also yes/no). Blank keeps the current value.'],
            ],
            'notes' => [
                'Passwords and security columns are never included in exports.',
                'A row can never deactivate your own account, remove your own super-admin role, or remove the last active super-admin.',
                'New accounts are marked email-verified, same as users created from the admin form.',
                'Nothing is ever deleted by an import.',
            ],
        ];
    }
}

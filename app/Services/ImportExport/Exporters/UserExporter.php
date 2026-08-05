<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\User;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExporter
{
    /** Shared with the importer. `password` is import-only and always exported blank. */
    public const HEADERS = ['name', 'email', 'phone', 'password', 'roles', 'is_active'];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with('roles:id,name')->orderBy('id')->lazy(500) as $u) {
                yield [
                    $u->name,
                    $u->email,
                    $u->phone,
                    '', // never export credentials
                    $u->roles->pluck('name')->implode('|'),
                    $u->is_active ? 1 : 0,
                ];
            }
        };

        return XlsxResponse::stream('users-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.users', [
            'users' => $query->with('roles:id,name')->orderBy('id')->get(),
            'generatedAt' => now(),
        ])->download('users-' . now()->format('Y-m-d') . '.pdf');
    }

    public function template(): StreamedResponse
    {
        return XlsxResponse::stream('users-template.xlsx', self::HEADERS, [
            ['Ali Raza', 'ali@example.com', '0300-1234567', '', 'editor', 1],
            ['Sana Khan', 'sana@example.com', '', 'S3cret!pass', 'cashier|sales-rep', 1],
        ]);
    }

    /** Mirrors UserController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'));
    }
}

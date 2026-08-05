<?php

namespace App\Services\ImportExport\Importers;

use App\Models\User;
use App\Services\ImportExport\Exporters\UserExporter;
use App\Services\ImportExport\ImportResult;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Upserts users (staff + login accounts) from an .xlsx (headers =
 * UserExporter::HEADERS). Match key: email.
 *
 * Safety rails: passwords are plaintext in the file and hashed by the model
 * cast; a blank password creates the account with an unguessable random one
 * (no email is sent); blank roles leave existing roles untouched; a row can
 * never deactivate the importing admin, drop their super-admin role, or
 * remove/deactivate the last active super-admin.
 */
class UserImporter
{
    public function __construct(private readonly SpreadsheetReader $reader)
    {
    }

    public function import(string $path, User $actor): ImportResult
    {
        $result = new ImportResult();
        $roleNames = Role::pluck('name')->all();

        foreach ($this->reader->rows($path, UserExporter::HEADERS) as $rowNumber => $row) {
            try {
                DB::transaction(function () use ($row, $actor, $roleNames, $result) {
                    $this->upsertRow($row, $actor, $roleNames, $result);
                });
            } catch (RowError $e) {
                $result->error($rowNumber, $e->getMessage());
            }
        }

        return $result;
    }

    /** @throws RowError */
    private function upsertRow(array $row, User $actor, array $roleNames, ImportResult $result): void
    {
        $email = mb_strtolower(trim($row['email']));

        $existing = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing?->trashed()) {
            throw new RowError("The email '{$email}' belongs to a deleted user — restore that account first.");
        }

        $roles = $row['roles'] !== ''
            ? collect(explode('|', $row['roles']))->map(fn ($r) => trim($r))->filter()->unique()->values()->all()
            : null; // blank = leave roles untouched

        $data = [
            'name' => $row['name'] !== '' ? $row['name'] : ($existing->name ?? ''),
            'email' => $email,
            // Blank never overwrites an existing phone.
            'phone' => $row['phone'] !== '' ? trim($row['phone']) : ($existing->phone ?? null),
            'password' => $row['password'] !== '' ? $row['password'] : null,
            'roles' => $roles ?? [],
        ];

        $isActive = SpreadsheetReader::bool($row['is_active']);

        // Rules mirror App\Http\Requests\Admin\UserRequest.
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', 'in:' . implode(',', $roleNames)],
        ], [
            'roles.*.in' => 'Unknown role — valid roles are: ' . implode(', ', $roleNames) . '.',
        ]);

        if ($validator->fails()) {
            throw new RowError($validator->errors()->first());
        }

        $this->guard($existing, $actor, $roles, $isActive);

        if ($existing) {
            $existing->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]);
            if ($isActive !== null) {
                $existing->is_active = $isActive;
            }
            if ($data['password'] !== null) {
                $existing->password = $data['password']; // hashed by the model cast
            }
            $existing->save();

            if ($roles !== null) {
                $existing->syncRoles($roles);
            }

            $result->updated++;

            return;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'],
            'password' => $data['password'] ?? Str::random(32),
            'is_active' => $isActive ?? true,
        ]);
        // Same as admin-created accounts (UserController::store) — no verification email.
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles($roles ?? []);

        $result->created++;
    }

    /** @throws RowError */
    private function guard(?User $existing, User $actor, ?array $roles, ?bool $isActive): void
    {
        if (! $existing) {
            return;
        }

        $isSelf = $existing->id === $actor->id;

        if ($isSelf && $isActive === false) {
            throw new RowError('You cannot deactivate your own account through an import.');
        }
        if ($isSelf && $roles !== null && $actor->hasRole('super-admin') && ! in_array('super-admin', $roles, true)) {
            throw new RowError('You cannot remove your own super-admin role through an import.');
        }

        // Never remove or deactivate the last active super-admin.
        if ($existing->hasRole('super-admin')) {
            $others = User::role('super-admin')->where('is_active', true)->whereKeyNot($existing->id)->count();
            $losesRole = $roles !== null && ! in_array('super-admin', $roles, true);

            if ($others === 0 && ($losesRole || $isActive === false)) {
                throw new RowError('This row would leave the system with no active super-admin.');
            }
        }
    }
}

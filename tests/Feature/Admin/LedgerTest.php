<?php

namespace Tests\Feature\Admin;

use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function seedEntries(): void
    {
        LedgerEntry::create(['entry_date' => now(), 'account' => 'inventory', 'debit' => 1000, 'credit' => 0, 'memo' => 'Marker inv']);
        LedgerEntry::create(['entry_date' => now(), 'account' => 'cash', 'debit' => 0, 'credit' => 600, 'memo' => 'Marker cash']);
        LedgerEntry::create(['entry_date' => now(), 'account' => 'accounts_payable', 'debit' => 0, 'credit' => 400, 'memo' => 'Marker payable']);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.ledger.index'))->assertForbidden();
    }

    public function test_admin_sees_the_ledger_with_a_balanced_trial(): void
    {
        $this->seedEntries();

        $this->actingAs($this->admin())->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Trial balance')
            ->assertSee('Marker inv')
            ->assertViewHas('trialTotals', fn ($t) => abs($t['debit'] - $t['credit']) < 0.01);
    }

    public function test_filtering_by_account_limits_the_entries(): void
    {
        $this->seedEntries();

        $this->actingAs($this->admin())->get(route('admin.ledger.index', ['account' => 'inventory']))
            ->assertOk()
            ->assertSee('Marker inv')
            ->assertDontSee('Marker cash');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\ErrorLog;
use App\Models\User;
use App\Services\ErrorLogger;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    private function log(array $overrides = []): ErrorLog
    {
        return ErrorLog::create(array_merge([
            'fingerprint' => hash('sha256', Str::random(24)),
            'level' => 'error',
            'type' => RuntimeException::class,
            'message' => 'Something broke',
            'occurrences' => 1,
            'last_seen_at' => now(),
        ], $overrides));
    }

    // --- Capture ---------------------------------------------------------------

    public function test_the_report_hook_persists_an_exception(): void
    {
        app(ExceptionHandler::class)->report(new RuntimeException('captured via handler'));

        $this->assertDatabaseHas('error_logs', [
            'type' => RuntimeException::class,
            'message' => 'captured via handler',
        ]);
    }

    public function test_the_same_error_is_deduplicated_and_counted(): void
    {
        $logger = app(ErrorLogger::class);
        $e = new RuntimeException('dedup me'); // same file+line => same fingerprint

        $logger->log($e);
        $logger->log($e);

        $row = ErrorLog::where('message', 'dedup me')->get();
        $this->assertCount(1, $row);
        $this->assertSame(2, $row->first()->occurrences);
    }

    public function test_expected_exceptions_are_not_stored(): void
    {
        $logger = app(ErrorLogger::class);
        $logger->log(ValidationException::withMessages(['x' => 'y']));
        $logger->log(new NotFoundHttpException('missing'));

        $this->assertSame(0, ErrorLog::where('message', 'like', '%missing%')->count());
        $this->assertDatabaseMissing('error_logs', ['type' => ValidationException::class]);
    }

    public function test_a_resolved_error_reopens_when_it_recurs(): void
    {
        $logger = app(ErrorLogger::class);
        $e = new RuntimeException('flaps open again');

        $logger->log($e);
        $row = ErrorLog::where('message', 'flaps open again')->firstOrFail();
        $row->update(['resolved_at' => now()]);

        $logger->log($e);

        $row->refresh();
        $this->assertNull($row->resolved_at);
        $this->assertSame(2, $row->occurrences);
    }

    // --- Admin -----------------------------------------------------------------

    public function test_super_admin_sees_the_error_log(): void
    {
        $log = $this->log(['message' => 'Listed on the index']);

        $this->actingAs($this->admin())
            ->get(route('admin.error-logs.index'))
            ->assertOk()
            ->assertSee('Listed on the index');

        $this->actingAs($this->admin())
            ->get(route('admin.error-logs.show', $log))
            ->assertOk()
            ->assertSee('RuntimeException');
    }

    public function test_resolve_toggles_and_reopen(): void
    {
        $log = $this->log();

        $this->actingAs($this->admin())->patch(route('admin.error-logs.resolve', $log))->assertRedirect();
        $this->assertNotNull($log->fresh()->resolved_at);

        $this->actingAs($this->admin())->patch(route('admin.error-logs.resolve', $log))->assertRedirect();
        $this->assertNull($log->fresh()->resolved_at);
    }

    public function test_delete_removes_the_log(): void
    {
        $log = $this->log();

        $this->actingAs($this->admin())
            ->delete(route('admin.error-logs.destroy', $log))
            ->assertRedirect(route('admin.error-logs.index'));

        $this->assertDatabaseMissing('error_logs', ['id' => $log->id]);
    }

    public function test_clear_resolved_prunes_only_resolved(): void
    {
        $open = $this->log(['message' => 'still open']);
        $done = $this->log(['message' => 'done', 'resolved_at' => now()]);

        $this->actingAs($this->admin())->delete(route('admin.error-logs.clear'))->assertRedirect();

        $this->assertDatabaseHas('error_logs', ['id' => $open->id]);
        $this->assertDatabaseMissing('error_logs', ['id' => $done->id]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.error-logs.index'))
            ->assertForbidden();
    }
}

<x-docs.prose>
    <p>
        The application is a <strong>Laravel</strong> monolith. The storefront is server-rendered Blade; the
        admin panel is Blade plus a few <strong>Livewire</strong> screens, with <strong>Alpine.js</strong> for
        light client interactivity and <strong>Tailwind CSS v4</strong> for styling. There is no separate
        front-end app — pages are rendered on the server.
    </p>

    <x-docs.section id="request-path" title="The path of a request" icon="route">
        <p>Every admin action travels the same layers. Understanding this order explains where each kind of logic lives:</p>
        <x-docs.steps>
            <x-docs.step num="1" title="Route">
                <x-docs.pill tone="route">routes/web.php</x-docs.pill> maps a URL to a controller. Admin routes
                live under the <code>admin</code> prefix and <code>admin.</code> name, all behind the
                <code>auth</code> middleware.
            </x-docs.step>
            <x-docs.step num="2" title="Middleware / RBAC gate">
                Each controller declares <code>can:{permission}</code> middleware. If the signed-in user lacks
                the permission, the request is rejected before any logic runs (fail-closed). See
                <a href="{{ route('admin.docs.show', 'permissions') }}">Roles &amp; permissions</a>.
            </x-docs.step>
            <x-docs.step num="3" title="FormRequest (validation)">
                Writes are validated by a dedicated <code>FormRequest</code> class (e.g. <code>ProductRequest</code>).
                Controllers use <code>$request->validated()</code> — never raw input.
            </x-docs.step>
            <x-docs.step num="4" title="Controller">
                Thin coordinator. It reads/queries for display, or hands mutations that touch money or stock to
                a <strong>service</strong>. It does not contain accounting or inventory rules itself.
            </x-docs.step>
            <x-docs.step num="5" title="Service layer">
                The shared business engines — <a href="{{ route('admin.docs.show', 'services') }}">Sales, Stock,
                Costing, Ledger, Purchase, Production, Inventory</a> and more. All money/stock changes run here,
                inside a database transaction.
            </x-docs.step>
            <x-docs.step num="6" title="Models (Eloquent)">
                One model per table. They define relationships, casts and query scopes. This is the only layer
                that talks to the database.
            </x-docs.step>
            <x-docs.step num="7" title="View (Blade)">
                The result renders through <code>layouts.admin</code> using shared
                <code>x-admin.*</code> components and design tokens, in both light and dark themes.
            </x-docs.step>
        </x-docs.steps>
    </x-docs.section>

    <x-docs.section id="folders" title="Where things live" icon="folder">
        <table>
            <thead><tr><th>Folder</th><th>What it holds</th></tr></thead>
            <tbody>
                <tr><td><x-docs.pill>app/Http/Controllers/Admin</x-docs.pill></td><td>One controller per admin screen/module.</td></tr>
                <tr><td><x-docs.pill>app/Http/Requests</x-docs.pill></td><td>FormRequest validation classes.</td></tr>
                <tr><td><x-docs.pill>app/Services</x-docs.pill></td><td>The business-logic engines (see the Service layer page).</td></tr>
                <tr><td><x-docs.pill>app/Models</x-docs.pill></td><td>Eloquent models — the data model.</td></tr>
                <tr><td><x-docs.pill>app/Support/helpers.php</x-docs.pill></td><td>Global helpers: <code>setting()</code>, <code>format_money()</code>, <code>per_page()</code>, dates.</td></tr>
                <tr><td><x-docs.pill>config/navigation.php</x-docs.pill></td><td>The admin sidebar tree.</td></tr>
                <tr><td><x-docs.pill>resources/views/admin</x-docs.pill></td><td>Admin Blade views, grouped by module.</td></tr>
                <tr><td><x-docs.pill>resources/views/components/admin</x-docs.pill></td><td>Shared UI: sidebar, header, panel, stat-card, pagination, badges.</td></tr>
                <tr><td><x-docs.pill>database/seeders</x-docs.pill></td><td>Seed data, incl. <code>RolePermissionSeeder</code>.</td></tr>
            </tbody>
        </table>
    </x-docs.section>

    <x-docs.section id="cross-cutting" title="Cross-cutting foundations" icon="foundation">
        <x-docs.cards :cols="2">
            <x-docs.card title="Theme system" icon="palette">
                The admin uses a Material <strong>blue</strong> palette scoped to <code>.admin-scope</code>;
                the storefront keeps its own yellow. Every screen ships in <strong>light and dark</strong> via
                design tokens (<code>bg-surface</code>, <code>text-on-surface</code>…). The toggle persists to
                <code>localStorage</code> and is applied before paint.
            </x-docs.card>
            <x-docs.card title="Settings-driven" icon="tune">
                Currency, dates, timezone, tax, pagination and more are never hardcoded — they read the
                <code>settings</code> table through helpers. Change them in <strong>Settings</strong>, not in code.
            </x-docs.card>
            <x-docs.card title="Queues &amp; email" icon="mail">
                Email (verification, receipts, campaigns, abandoned-cart reminders) is sent through queued jobs,
                tiered high/low, with Horizon in production.
            </x-docs.card>
            <x-docs.card title="Observability" icon="monitor_heart">
                An <strong>activity log</strong> records staff mutations; an <strong>error log</strong> captures
                unhandled exceptions to the database for review. Both are in the System area.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>
</x-docs.prose>

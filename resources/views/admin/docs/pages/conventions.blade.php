<x-docs.prose>
    <p>
        Every module in this codebase follows one shared rulebook (<code>CONVENTIONS.md</code> at the repo
        root). Knowing the rules means each screen behaves predictably — this page summarises them so admins and
        developers share the same mental model.
    </p>

    <x-docs.section id="ui" title="Interface rules" icon="widgets">
        <ul>
            <li>Every dropdown is a searchable <strong>Select2</strong> control (unless deliberately opted out for Alpine-bound selects).</li>
            <li>Phone and ID fields are <strong>input-masked</strong>; a field's <code>maxlength</code> matches its database column and its validation rule.</li>
            <li>File uploads use a shared drag-and-drop control; images are chosen from the <strong>Gallery</strong> via the media/image pickers.</li>
            <li>Lists are always <strong>paginated</strong>, filterable and sortable; index screens open with stat cards.</li>
            <li>Everything clickable shows a pointer cursor, and every screen works in <strong>light and dark</strong> mode.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="data" title="Data rules" icon="database">
        <ul>
            <li>Money is stored as <strong>decimal</strong>; statuses/enums are strings validated with <code>in:</code> lists.</li>
            <li>Filtered, sorted or joined columns are <strong>indexed</strong>; related data is eager-loaded to avoid N+1 queries.</li>
            <li>Reads paginate; writes go through a <code>FormRequest</code> and use <code>validated()</code>, never <code>all()</code>.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="security" title="Security &amp; money rules" icon="lock">
        <ul>
            <li><strong>RBAC is fail-closed.</strong> Permissions are named <code>{resource}.{action}</code> and seeded in <code>RolePermissionSeeder</code>. No permission &rarr; no access.</li>
            <li>Secrets (SMTP passwords, API keys) are <strong>encrypted</strong> and shown masked; blank on save keeps the stored value.</li>
            <li>Mutating admin actions are written to the <strong>activity log</strong>.</li>
            <li>Every money or stock change goes through the <strong>service layer</strong>, inside a database transaction, and (for money) posts to the <strong>ledger</strong>. Controllers never move money directly.</li>
        </ul>
        <x-docs.callout tone="key" title="The one rule that matters most">
            If an action changes stock or money, it must run through a service (Sales, Purchase, Production,
            Stock, Inventory) so the ledger and stock movements stay consistent. This is why the same sale looks
            correct on the order, in inventory, and in the ledger at the same time.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="settings-first" title="Settings, not hardcoding" icon="settings">
        <p>
            Currency (PKR/Rs), date &amp; time format, timezone (Asia/Karachi), tax rate, per-page counts and
            document number prefixes all come from the <code>settings</code> table via helpers
            (<code>format_money()</code>, <code>format_date()</code>, <code>per_page()</code>, <code>setting()</code>).
            To change how the whole system formats or numbers things, use <strong>Settings</strong> — never edit a view.
        </p>
    </x-docs.section>
</x-docs.prose>

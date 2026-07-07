<x-docs.prose>
    <p>
        The system area governs <strong>how the platform behaves</strong> and <strong>who may do what</strong>,
        plus the tools to observe both. Most of it lives under the Settings heading in the sidebar.
    </p>

    <x-docs.section id="settings" title="Settings" icon="settings">
        <p>
            One tabbed screen holds all configuration, grouped into: <strong>General, Store, Payment, Shipping,
            Tax, SEO, Mail, Inventory, Pricing, Numbering, POS, Quotation, Social login</strong> and
            <strong>System</strong>. Values are read across the whole app through helpers — change them here, never
            in code. Secrets (SMTP passwords, API keys) are stored encrypted and shown masked; leaving a secret
            blank on save keeps the existing value.
        </p>
        <p>Some settings take effect immediately: numbering prefixes, tax and shipping, negative-stock policy, costing method, receipt footer, and the invoice/thermal bill format.</p>
        <x-docs.pill tone="route">admin.settings.*</x-docs.pill>
        <x-docs.pill tone="model">Setting</x-docs.pill>
        <x-docs.pill tone="perm">settings.view · edit</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="users-roles" title="Staff users & roles" icon="manage_accounts">
        <x-docs.cards :cols="2">
            <x-docs.card title="Users" icon="badge">
                Staff/admin accounts (the <code>users</code> table, separate from customers). Managed with their
                assigned roles; passwords are hashed. Guards prevent deleting yourself or the last super-admin.
                <br><x-docs.pill tone="perm">users.*</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Roles & permissions" icon="admin_panel_settings">
                A role is a named bundle of permissions, edited through a grouped permission matrix. Super-admin
                is protected; a role with users can't be deleted.
                <br><x-docs.pill tone="perm">roles.*</x-docs.pill>
            </x-docs.card>
        </x-docs.cards>
        <p>Full detail is on the <a href="{{ route('admin.docs.show', 'permissions') }}">Roles &amp; permissions</a> reference page.</p>
    </x-docs.section>

    <x-docs.section id="observability" title="Activity log & error logs" icon="monitor_heart">
        <x-docs.cards :cols="2">
            <x-docs.card title="Activity log" icon="history">
                A read-only audit trail. An observer records create/update/delete on the major models — with who,
                when, IP, and a sanitised before/after diff (secrets redacted). Filter by event, user or text.
                <br><x-docs.pill tone="perm">audit.view</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Error logs" icon="bug_report">
                Unhandled exceptions captured to the database by <code>ErrorLogger</code>, de-duplicated by a
                fingerprint (recurrences bump a counter and re-open a resolved error). Review, resolve, delete, and
                auto-prune old resolved logs.
                <br><x-docs.pill tone="perm">error-logs.view · resolve · delete</x-docs.pill>
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="profile" title="Your profile" icon="account_circle">
        <p>
            Every staff member can edit their own details, photo and password from the profile screen (reachable
            in the header user menu) without needing user-management permission.
        </p>
        <x-docs.pill tone="route">admin.profile.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How the system area connects" icon="hub">
        <ul>
            <li><strong>&rarr; Everything:</strong> Settings values and permission checks apply on every screen.</li>
            <li><strong>&larr; Everything:</strong> the activity and error logs observe all modules' mutations and failures.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

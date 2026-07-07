<x-docs.prose>
    <p>
        Access is governed by <strong>role-based permissions</strong> and is <em>fail-closed</em> — if a user's
        roles don't grant a permission, the screen is hidden and the route is blocked. This page lists how it
        works, the roles, and who can do what.
    </p>

    <x-docs.section id="how" title="How it works" icon="key">
        <ul>
            <li>Permissions are named <code>{resource}.{action}</code> — e.g. <code>products.edit</code>, <code>orders.refund</code>, <code>ledger.view</code>.</li>
            <li>Each screen's controller declares <code>can:{permission}</code> middleware; the sidebar hides items a user can't access.</li>
            <li>A <strong>role</strong> is a bundle of permissions. Users can hold several roles; their permissions are the union.</li>
            <li><strong>super-admin</strong> additionally bypasses all checks via a global gate — it can do anything.</li>
            <li>Permissions and role bundles are defined in <code>RolePermissionSeeder</code>; the Roles screen edits assignments through a permission matrix.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="roles" title="The roles" icon="badge">
        <table>
            <thead><tr><th>Role</th><th>Scope</th></tr></thead>
            <tbody>
                <tr><td><strong>super-admin</strong></td><td>Everything, plus a gate bypass. The owner account.</td></tr>
                <tr><td><strong>admin</strong></td><td>Every permission (including this documentation), but no gate bypass.</td></tr>
                <tr><td><strong>catalog-manager</strong></td><td>Products, variants, categories, brands, attributes, gallery/media, reviews, and home-page content (hero slides, promo cards, info bar).</td></tr>
                <tr><td><strong>procurement</strong></td><td>Suppliers, purchases, stock.</td></tr>
                <tr><td><strong>production-manager</strong></td><td>BOMs, production, stock.</td></tr>
                <tr><td><strong>inventory-manager</strong></td><td>Stock, products, variants.</td></tr>
                <tr><td><strong>cashier</strong></td><td>POS, customers, orders.</td></tr>
                <tr><td><strong>sales-rep</strong></td><td>Quotations, orders, customers.</td></tr>
                <tr><td><strong>order-manager</strong></td><td>Orders, customers, reports, support, subscribers, campaigns, abandoned carts.</td></tr>
                <tr><td><strong>accountant</strong></td><td>Ledger, reports, orders, purchases.</td></tr>
                <tr><td><strong>editor</strong></td><td>Blog posts, categories, tags, comments, gallery/media.</td></tr>
                <tr><td><strong>customer</strong></td><td>No admin access — storefront account only.</td></tr>
            </tbody>
        </table>
        <p class="text-sm">Every staff role above also carries <code>dashboard.view</code> and <code>documentation.view</code>, so all staff can reach the dashboard and this handbook.</p>
    </x-docs.section>

    <x-docs.section id="resources" title="Permission catalogue" icon="list">
        <p>The actions available per resource (a role gets some subset of these):</p>
        <table>
            <thead><tr><th>Resource</th><th>Actions</th></tr></thead>
            <tbody>
                <tr><td>dashboard</td><td>view</td></tr>
                <tr><td>documentation</td><td>view</td></tr>
                <tr><td>products / categories / brands / attributes</td><td>view · create · edit · delete</td></tr>
                <tr><td>hero-slides / promo-cards / info-bar-items</td><td>view · create · edit · delete</td></tr>
                <tr><td>variants</td><td>edit</td></tr>
                <tr><td>media / gallery</td><td>view · create · edit · delete</td></tr>
                <tr><td>suppliers</td><td>view · create · edit · delete</td></tr>
                <tr><td>purchases</td><td>view · create · edit · delete · receive · pay</td></tr>
                <tr><td>boms</td><td>view · create · edit · delete</td></tr>
                <tr><td>production</td><td>view · create · edit · delete · complete</td></tr>
                <tr><td>stock</td><td>view · adjust · transfer</td></tr>
                <tr><td>customers</td><td>view · create · edit · delete</td></tr>
                <tr><td>quotations</td><td>view · create · edit · delete · convert</td></tr>
                <tr><td>pos</td><td>access · sell · refund</td></tr>
                <tr><td>orders</td><td>view · create · edit · refund · fulfil</td></tr>
                <tr><td>coupons</td><td>view · create · edit · delete</td></tr>
                <tr><td>reviews</td><td>view · moderate</td></tr>
                <tr><td>support</td><td>view · reply</td></tr>
                <tr><td>subscribers</td><td>view · delete · export</td></tr>
                <tr><td>campaigns</td><td>view · create · edit · delete · send</td></tr>
                <tr><td>abandoned-carts</td><td>view · delete</td></tr>
                <tr><td>blog-posts / blog-categories / blog-tags</td><td>view · create · edit · delete</td></tr>
                <tr><td>blog-comments</td><td>view · moderate · reply · delete</td></tr>
                <tr><td>ledger</td><td>view</td></tr>
                <tr><td>reports</td><td>view · export</td></tr>
                <tr><td>audit</td><td>view</td></tr>
                <tr><td>error-logs</td><td>view · resolve · delete</td></tr>
                <tr><td>settings</td><td>view · edit</td></tr>
                <tr><td>users / roles</td><td>view · create · edit · delete</td></tr>
            </tbody>
        </table>
        <x-docs.callout tone="warning" title="Adding a permission">
            New permissions and role assignments are added to <code>RolePermissionSeeder</code> and applied by
            re-seeding. Don't invent permission checks that have no seeded permission behind them, or the check
            will always fail.
        </x-docs.callout>
    </x-docs.section>
</x-docs.prose>

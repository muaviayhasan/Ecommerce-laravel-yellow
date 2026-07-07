<x-docs.prose>
    <p>
        This is the wiring diagram of the whole system: which modules feed which, and through what. Most
        connections are not direct — they pass through a <strong>service</strong> (a shared engine) or land in a
        shared <strong>hub</strong> table. Learn the four hubs below and the rest of the map falls into place.
    </p>

    <x-docs.section id="hubs" title="The four hubs" icon="hub">
        <p>Four things sit in the middle of the system. Almost every module connects to at least one of them:</p>
        <x-docs.cards :cols="2">
            <x-docs.card title="Products &amp; variants" icon="inventory_2">
                The catalog. A <strong>Product</strong> has one or more <strong>ProductVariant</strong> rows —
                the actual sellable/stockable units. Sales, stock, purchasing and manufacturing all reference a
                <em>variant</em>, not just a product.
            </x-docs.card>
            <x-docs.card title="Orders" icon="receipt_long">
                Every sale — web checkout, POS, vendor sale, converted quotation — becomes an
                <strong>Order</strong> with <strong>OrderItems</strong>, <strong>Payments</strong> and a status
                history. Orders, reports, support and accounts all read it.
            </x-docs.card>
            <x-docs.card title="Stock movements" icon="sync_alt">
                Every change in on-hand quantity is one <strong>StockMovement</strong> row written by
                <code>StockService</code>. Purchases, sales, production and manual adjustments all land here; the
                variant's cached quantity is derived from it.
            </x-docs.card>
            <x-docs.card title="Ledger" icon="account_balance">
                The financial source of truth. Every money event posts a balanced <strong>LedgerEntry</strong>
                via <code>LedgerService</code>. Sales, purchases, production and inventory adjustments all post
                here; Finance reads it.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="dependency-table" title="Dependency table" icon="table_chart">
        <p>Read this as &ldquo;the module on the left <em>relies on / writes to</em> the things on the right.&rdquo;</p>
        <table>
            <thead><tr><th>Module</th><th>Depends on / connects to</th><th>Via</th></tr></thead>
            <tbody>
                <tr><td><strong>Checkout (web)</strong></td><td>Cart, Customers, Products, Coupons, Orders, Stock, Ledger</td><td><x-docs.pill tone="service">SalesService</x-docs.pill></td></tr>
                <tr><td><strong>POS</strong></td><td>Products, Customers, Orders, Stock, Ledger, Payments</td><td><x-docs.pill tone="service">SalesService</x-docs.pill></td></tr>
                <tr><td><strong>Vendor sale</strong></td><td>Products, Customers, Orders, Stock, Ledger (A/R)</td><td><x-docs.pill tone="service">SalesService</x-docs.pill></td></tr>
                <tr><td><strong>Quotations</strong></td><td>Products, Customers &rarr; Orders (on convert)</td><td><x-docs.pill tone="service">QuotationService</x-docs.pill> &rarr; <x-docs.pill tone="service">SalesService</x-docs.pill></td></tr>
                <tr><td><strong>Purchases</strong></td><td>Suppliers, Products, Stock, Costing, Ledger</td><td><x-docs.pill tone="service">PurchaseService</x-docs.pill></td></tr>
                <tr><td><strong>Production</strong></td><td>BOMs, Products, Stock, Costing, Ledger</td><td><x-docs.pill tone="service">ProductionService</x-docs.pill></td></tr>
                <tr><td><strong>Inventory adjust</strong></td><td>Products, Stock, Ledger</td><td><x-docs.pill tone="service">InventoryService</x-docs.pill></td></tr>
                <tr><td><strong>Orders</strong></td><td>Customers, Products, Payments, Stock (via sale)</td><td>reads the hub</td></tr>
                <tr><td><strong>Reports / Ledger view</strong></td><td>Orders, Payments, LedgerEntries</td><td>read-only</td></tr>
                <tr><td><strong>Reviews</strong></td><td>Products, Customers/Users</td><td>direct</td></tr>
                <tr><td><strong>Marketing</strong></td><td>Subscribers, Customers, Orders (abandoned carts)</td><td>queued mail</td></tr>
                <tr><td><strong>Catalog content</strong></td><td>Products, Categories, Brands, Gallery &rarr; Storefront</td><td>direct</td></tr>
                <tr><td><strong>Blog</strong></td><td>Blog categories/tags, Comments &rarr; Storefront</td><td>direct</td></tr>
            </tbody>
        </table>
    </x-docs.section>

    <x-docs.section id="feeds" title="What feeds stock, and what drains it" icon="conveyor_belt">
        <x-docs.cards :cols="2">
            <x-docs.card title="Increases stock (+)" icon="add_circle">
                <ul class="!my-0">
                    <li><strong>Purchase received</strong> — goods in from a supplier</li>
                    <li><strong>Production output</strong> — finished goods manufactured</li>
                    <li><strong>Inventory adjustment</strong> — a counted gain</li>
                </ul>
            </x-docs.card>
            <x-docs.card title="Decreases stock (&minus;)" icon="remove_circle">
                <ul class="!my-0">
                    <li><strong>Sale</strong> — web, POS or vendor order placed</li>
                    <li><strong>Production consume</strong> — components used up</li>
                    <li><strong>Inventory adjustment</strong> — a counted loss / write-off</li>
                </ul>
            </x-docs.card>
        </x-docs.cards>
        <p>
            All six run through <code>StockService::move()</code>, so on-hand quantity, cost and the movement
            history stay in agreement. See <a href="{{ route('admin.docs.show', 'inventory-flow') }}">Stock &amp; costing flow</a>.
        </p>
    </x-docs.section>

    <x-docs.section id="shared" title="Shared services every area touches" icon="share">
        <ul>
            <li><strong>Gallery / Media</strong> — the single image library; products, brands, blog, hero slides and promo cards all pick from it.</li>
            <li><strong>Settings</strong> — currency, tax, numbering, email, SEO: read across the entire system.</li>
            <li><strong>Roles &amp; permissions</strong> — gate every screen.</li>
            <li><strong>Activity &amp; error logs</strong> — observe every module's mutations and failures.</li>
        </ul>
        <x-docs.callout tone="info">
            Because the shared services enforce the rules, two modules rarely talk to each other directly. When
            you wonder &ldquo;how does X affect Y?&rdquo;, the answer is almost always &ldquo;through a service and
            a hub table.&rdquo;
        </x-docs.callout>
    </x-docs.section>
</x-docs.prose>

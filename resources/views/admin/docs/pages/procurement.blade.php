<x-docs.prose>
    <p>
        Procurement is how goods <strong>enter</strong> the business: buying from suppliers, receiving stock, and
        watching live inventory. Receiving is the mirror image of a sale — it stocks in, re-blends cost, and posts
        to the ledger.
    </p>

    <x-docs.section id="suppliers" title="Suppliers" icon="contacts">
        <p>
            Vendor records with an opening balance. The index shows each supplier's <strong>payable balance</strong>
            (opening + received purchases &minus; payments). A supplier with purchases against it can't be deleted.
        </p>
        <x-docs.pill tone="route">admin.suppliers.*</x-docs.pill>
        <x-docs.pill tone="model">Supplier</x-docs.pill>
        <x-docs.pill tone="perm">suppliers.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="purchases" title="Purchase orders" icon="shopping_cart">
        <p>A purchase has a lifecycle:</p>
        <x-docs.steps>
            <x-docs.step num="1" title="Draft">Build lines (variant + qty + unit cost) with live subtotal/tax/total. Editable and deletable while draft.</x-docs.step>
            <x-docs.step num="2" title="Received">
                <code>PurchaseService::receive()</code> runs in one transaction: for each line it re-computes cost
                (<code>CostingService</code>) then stocks in (<code>StockService</code>), and posts
                <em>inventory = cash paid + payable</em> to the ledger.
            </x-docs.step>
            <x-docs.step num="3" title="Payments">Supplier payments can be recorded against the received purchase, reducing the payable.</x-docs.step>
            <x-docs.step num="4" title="Cancelled">
                <code>cancel()</code> reverses the stock (rejected if it has since been consumed/sold) and reverses
                the ledger entry.
            </x-docs.step>
        </x-docs.steps>
        <x-docs.pill tone="route">admin.purchases.*</x-docs.pill>
        <x-docs.pill tone="model">Purchase</x-docs.pill>
        <x-docs.pill tone="model">PurchaseItem</x-docs.pill>
        <x-docs.pill tone="perm">purchases.view · create · edit · delete · receive · pay</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="inventory" title="Inventory & stock" icon="inventory">
        <p>
            The inventory screen lists every active, stock-tracked variant with on-hand, reserved, available,
            cost and value, plus In / Low / Out badges and a live total stock value. Staff can post a manual
            <strong>adjustment</strong> (set or add) with a required reason — routed through
            <code>InventoryService</code> so it writes a movement and posts to the ledger. Each variant has a full
            paginated <strong>movement history</strong>.
        </p>
        <x-docs.pill tone="route">admin.inventory.*</x-docs.pill>
        <x-docs.pill tone="model">StockMovement</x-docs.pill>
        <x-docs.pill tone="perm">stock.view · adjust</x-docs.pill>
        <x-docs.callout tone="info">
            See <a href="{{ route('admin.docs.show', 'inventory-flow') }}">Stock &amp; costing flow</a> for how the
            numbers here are derived and kept honest.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="connects" title="How procurement connects" icon="hub">
        <ul>
            <li><strong>&larr; Catalog:</strong> purchases and adjustments target product variants.</li>
            <li><strong>&rarr; Inventory:</strong> receiving is the main way stock increases.</li>
            <li><strong>&rarr; Finance:</strong> receiving and adjustments post to the ledger; the accountant role reads purchases.</li>
            <li><strong>&rarr; Manufacturing:</strong> received components are what production runs consume.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

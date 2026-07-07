<x-docs.prose>
    <p>
        Stock is tracked per <strong>variant</strong>, not per product. Each variant carries a cached on-hand
        quantity and a unit cost, but the truth is the ledger of <strong>stock movements</strong> behind it —
        every increase or decrease is one immutable row, and the cached quantity is simply the running balance.
    </p>

    <x-docs.section id="one-door" title="One door for every change" icon="door_front">
        <p>
            No screen edits a variant's quantity directly. Every change goes through
            <code>StockService::move(variant, type, signedQty, unitCost?, reference?, reason?)</code>, which:
        </p>
        <ul>
            <li>writes a <code>StockMovement</code> row stamped with the movement <strong>type</strong>, the signed quantity, and the resulting <strong>balance after</strong>;</li>
            <li>updates the variant's cached <code>stock_quantity</code>;</li>
            <li>refuses to drive stock negative unless Settings explicitly allows it.</li>
        </ul>
        <x-docs.callout tone="info">
            Because there is exactly one door, the <a href="{{ route('admin.docs.show', 'procurement') }}">Inventory</a>
            screen can show a complete, trustworthy movement history for any variant, and on-hand numbers never
            drift from reality.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="types" title="Movement types" icon="category">
        <table>
            <thead><tr><th>Type</th><th>Direction</th><th>Raised by</th></tr></thead>
            <tbody>
                <tr><td>Purchase in</td><td>+</td><td>Purchase received (<x-docs.pill tone="service">PurchaseService</x-docs.pill>)</td></tr>
                <tr><td>Sale out</td><td>&minus;</td><td>Order placed (<x-docs.pill tone="service">SalesService</x-docs.pill>)</td></tr>
                <tr><td>Production consume</td><td>&minus;</td><td>Production completed (<x-docs.pill tone="service">ProductionService</x-docs.pill>)</td></tr>
                <tr><td>Production output</td><td>+</td><td>Production completed</td></tr>
                <tr><td>Adjustment</td><td>+ / &minus;</td><td>Manual count (<x-docs.pill tone="service">InventoryService</x-docs.pill>)</td></tr>
            </tbody>
        </table>
    </x-docs.section>

    <x-docs.section id="costing" title="Costing (moving average)" icon="calculate">
        <p>
            When stock comes <em>in</em> at a new price, <code>CostingService</code> re-blends the variant's unit
            cost as a <strong>moving average</strong> of the old stock's cost and the new receipt's cost. It runs
            <em>before</em> the stock-in so it uses the pre-receipt quantity.
        </p>
        <p>
            When stock goes <em>out</em> on a sale, that current unit cost is <strong>snapshotted</strong> onto the
            order item. That snapshot becomes the COGS posted to the ledger, so profit is correct even if the cost
            later changes. This is why the same sale reconciles across the order, inventory value, and the
            <a href="{{ route('admin.docs.show', 'money-flow') }}">ledger</a>.
        </p>
    </x-docs.section>

    <x-docs.section id="lifecycle" title="A unit's life, end to end" icon="timeline">
        <x-docs.steps>
            <x-docs.step num="1" title="Arrives">A purchase is received &rarr; stock in, cost re-blended, inventory debited in the ledger.</x-docs.step>
            <x-docs.step num="2" title="(Optional) Transformed">Production consumes components and outputs a finished variant, moving cost from raw to finished inventory.</x-docs.step>
            <x-docs.step num="3" title="Counted">A stock take posts an adjustment for any gain or loss, with a reason.</x-docs.step>
            <x-docs.step num="4" title="Sold">A sale draws it down at snapshotted cost &rarr; revenue and COGS post to the ledger.</x-docs.step>
            <x-docs.step num="5" title="Recorded forever">Each step left a stock-movement row; the variant's on-hand and value are always the sum of them.</x-docs.step>
        </x-docs.steps>
    </x-docs.section>
</x-docs.prose>

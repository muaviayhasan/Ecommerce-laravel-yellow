<x-docs.prose>
    <p>
        Manufacturing turns raw components into finished goods. It reuses the same stock, costing and ledger
        engines as everything else — a production run is just a coordinated set of stock movements (components
        out, finished unit in) with the cost carried across.
    </p>

    <x-docs.section id="boms" title="Bills of materials (BOMs)" icon="account_tree">
        <p>
            A <strong>BOM</strong> is the recipe for a finished product: its component variants, the quantity of
            each, a waste percentage, and labour + overhead. <code>BomService</code> computes the unit cost as
            <em>(&Sigma; component cost &times; qty &times; (1 + waste%) + labour + overhead) &divide; output
            quantity</em>, shown live as you build the recipe.
        </p>
        <p>Choosing a product for a BOM marks it <strong>manufacturable</strong>. A BOM with production orders can't be deleted.</p>
        <x-docs.pill tone="route">admin.boms.*</x-docs.pill>
        <x-docs.pill tone="model">Bom</x-docs.pill>
        <x-docs.pill tone="model">BomItem</x-docs.pill>
        <x-docs.pill tone="perm">boms.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="production" title="Production runs" icon="precision_manufacturing">
        <p>A production order goes draft &rarr; completed &rarr; (or) cancelled:</p>
        <x-docs.steps>
            <x-docs.step num="1" title="Plan (draft)">
                Pick a BOM and an output quantity. The form shows live <strong>component needs vs. available
                stock</strong> (shortfalls flagged) and a cost estimate before you commit.
            </x-docs.step>
            <x-docs.step num="2" title="Complete">
                <code>ProductionService::complete()</code> (one transaction) consumes each component out of stock
                at moving-average cost, records the consumption, computes the finished unit cost
                (components + labour + overhead), stocks the finished units in, and posts
                <em>finished-inventory = raw-inventory + labour + overhead</em> to the ledger. If a component is
                short, the whole run rolls back.
            </x-docs.step>
            <x-docs.step num="3" title="Cancel">
                Reverses everything — finished units removed (rejected if already sold), components returned, ledger reversed.
            </x-docs.step>
        </x-docs.steps>
        <x-docs.pill tone="route">admin.production.*</x-docs.pill>
        <x-docs.pill tone="model">ProductionOrder</x-docs.pill>
        <x-docs.pill tone="model">ProductionConsumption</x-docs.pill>
        <x-docs.pill tone="perm">production.view · create · edit · delete · complete</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How manufacturing connects" icon="hub">
        <ul>
            <li><strong>&larr; Catalog:</strong> BOM components and the finished product are variants.</li>
            <li><strong>&larr; Procurement:</strong> the components consumed were stocked in by purchases.</li>
            <li><strong>&rarr; Inventory:</strong> completing a run both decreases (components) and increases (finished goods) stock.</li>
            <li><strong>&rarr; Finance:</strong> cost moves from raw to finished inventory in the ledger.</li>
            <li><strong>&rarr; Sales:</strong> the finished goods are then sold like any other product.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

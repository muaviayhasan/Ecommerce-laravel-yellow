<x-docs.prose>
    <p>
        The finance area doesn't <em>create</em> financial data — it <strong>reads</strong> what every other
        module has already posted. Because sales, purchases, production and adjustments all post to the ledger
        through services, these screens are always consistent with the operations behind them.
    </p>

    <x-docs.section id="ledger" title="Ledger" icon="account_balance">
        <p>The financial source of truth, read-only. Grouping all <code>LedgerEntry</code> rows by account gives:</p>
        <ul>
            <li><strong>Financial position</strong> — cash &amp; bank, inventory value, accounts payable, accounts receivable.</li>
            <li><strong>Profit &amp; loss</strong> — revenue, cost of goods sold, gross profit, tax collected, refunds.</li>
            <li><strong>Trial balance</strong> — debit and credit per account, with a &ldquo;Balanced&rdquo; check that flags any imbalance.</li>
            <li><strong>Entries log</strong> — every posted line, filterable by account and date; each entry links back to the purchase, order or production run that created it.</li>
        </ul>
        <x-docs.pill tone="route">admin.ledger.index</x-docs.pill>
        <x-docs.pill tone="model">LedgerEntry</x-docs.pill>
        <x-docs.pill tone="perm">ledger.view</x-docs.pill>
        <x-docs.callout tone="info">
            See <a href="{{ route('admin.docs.show', 'money-flow') }}">Financial flow</a> for the accounts and
            exactly what each business event posts.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="reports" title="Reports" icon="bar_chart">
        <p>
            An analytics dashboard built from order and payment data: KPI cards (revenue, orders, customers) with
            month-over-month trend and sparklines, revenue-vs-profit and monthly-sales charts, a sales-vs-returns
            line chart, recent orders and top products. The data can be <strong>exported to CSV</strong>.
        </p>
        <x-docs.pill tone="route">admin.reports.*</x-docs.pill>
        <x-docs.pill tone="perm">reports.view · export</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="dashboard" title="Dashboard" icon="dashboard">
        <p>
            The admin home screen is a lighter, real-time summary of the same signals — headline stats with
            trend, an orders chart, an earnings (revenue + profit) chart, top products, top customers and recent
            activity — so staff get the pulse of the business the moment they sign in.
        </p>
        <x-docs.pill tone="route">admin.dashboard</x-docs.pill>
        <x-docs.pill tone="perm">dashboard.view</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How finance connects" icon="hub">
        <ul>
            <li><strong>&larr; Sales:</strong> orders and payments are the raw material for revenue, COGS and reports.</li>
            <li><strong>&larr; Procurement &amp; Manufacturing:</strong> receiving and production runs post inventory and cost entries.</li>
            <li><strong>&larr; Inventory:</strong> adjustments post gains/write-offs.</li>
            <li>These screens never write — to change a number, record the real business action and let its service post the entry.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

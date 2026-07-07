<x-docs.prose>
    <p>
        The system keeps real <strong>double-entry</strong> books. Every financial event posts a balanced set of
        journal lines — total debits always equal total credits — into <code>ledger_entries</code> through
        <code>LedgerService</code>. Nothing writes to the ledger by hand; it is always a by-product of a business
        action (a sale, a purchase, a production run, an adjustment).
    </p>

    <x-docs.callout tone="info" title="Why double-entry">
        Because each event balances, the books always balance. The <a href="{{ route('admin.docs.show', 'finance') }}">Ledger</a>
        screen can then show a trial balance, a financial position and a profit &amp; loss that are guaranteed
        internally consistent with the orders and purchases that produced them.
    </x-docs.callout>

    <x-docs.section id="accounts" title="The accounts" icon="account_tree">
        <table>
            <thead><tr><th>Account</th><th>Type</th><th>Moves when…</th></tr></thead>
            <tbody>
                <tr><td><x-docs.pill>cash</x-docs.pill> / <x-docs.pill>bank</x-docs.pill></td><td>Asset</td><td>a sale is paid, a purchase is paid</td></tr>
                <tr><td><x-docs.pill>inventory</x-docs.pill> (raw / finished)</td><td>Asset</td><td>stock comes in or goes out</td></tr>
                <tr><td><x-docs.pill>accounts_receivable</x-docs.pill></td><td>Asset</td><td>a credit / vendor sale is unpaid</td></tr>
                <tr><td><x-docs.pill>accounts_payable</x-docs.pill></td><td>Liability</td><td>a purchase is received but not fully paid</td></tr>
                <tr><td><x-docs.pill>tax_payable</x-docs.pill></td><td>Liability</td><td>tax is collected on a sale</td></tr>
                <tr><td><x-docs.pill>sales_revenue</x-docs.pill></td><td>Income</td><td>goods are sold</td></tr>
                <tr><td><x-docs.pill>cogs</x-docs.pill></td><td>Expense</td><td>sold goods leave inventory (at cost)</td></tr>
                <tr><td><x-docs.pill>refunds</x-docs.pill></td><td>Contra-income</td><td>a sale is refunded</td></tr>
            </tbody>
        </table>
    </x-docs.section>

    <x-docs.section id="events" title="What each event posts" icon="receipt">
        <h4>A sale (checkout / POS / vendor)</h4>
        <p>Two balanced posts happen together inside <code>SalesService</code>:</p>
        <ul>
            <li><strong>The money side:</strong> debit cash <em>or</em> accounts-receivable = credit sales-revenue + tax-payable + shipping-income.</li>
            <li><strong>The cost side:</strong> debit COGS = credit inventory (at the snapshotted unit cost).</li>
        </ul>
        <h4>A purchase received</h4>
        <p>Debit inventory (the full goods value) = credit cash (amount paid) + accounts-payable (the rest).</p>
        <h4>A production run completed</h4>
        <p>Debit finished-inventory = credit raw-inventory + labour + overhead.</p>
        <h4>An inventory adjustment</h4>
        <p>A counted gain debits inventory / credits an adjustment account; a write-off reverses it.</p>
    </x-docs.section>

    <x-docs.section id="reads" title="Who reads the ledger" icon="visibility">
        <p>
            The ledger is written by services and <strong>read</strong> by the Finance area. The
            <a href="{{ route('admin.docs.show', 'finance') }}">Ledger screen</a> groups entries by account to
            derive cash, inventory value, payables/receivables, revenue, COGS and gross profit, plus a trial
            balance that flags if anything is ever out of balance. Reports layers sales analytics on top of the
            same order and payment data.
        </p>
        <x-docs.callout tone="warning" title="Read-only by design">
            There is no &ldquo;edit ledger entry&rdquo; screen. To correct the books you record the real business
            action (a refund, an adjustment, a cancellation) and let the service post the reversing entry. This
            keeps an honest audit trail.
        </x-docs.callout>
    </x-docs.section>
</x-docs.prose>

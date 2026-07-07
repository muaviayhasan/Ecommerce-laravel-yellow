<x-docs.prose>
    <p>
        The service layer (<code>app/Services</code>) is where the real business logic lives. Controllers stay
        thin and delegate here. Every service that changes money or stock does its work inside a
        <strong>database transaction</strong>, so a failure rolls the whole operation back rather than leaving
        half-finished records.
    </p>

    <x-docs.section id="money-stock" title="Money &amp; stock engines" icon="savings">
        <x-docs.cards :cols="1">
            <x-docs.card title="StockService" icon="sync_alt">
                The <strong>only</strong> place stock quantity changes. <code>move(variant, type, signedQty, …)</code>
                writes a <code>StockMovement</code> row (with the running balance) and updates the variant's cached
                quantity. Rejects going negative unless negative stock is allowed in Settings. Every other
                stock-touching service calls this one.
            </x-docs.card>
            <x-docs.card title="CostingService" icon="calculate">
                Maintains each variant's unit cost using a <strong>moving-average</strong> method. Called with the
                old quantity <em>before</em> a stock-in re-blends the average cost. Sales snapshot this cost so
                profit is calculated correctly even if costs change later.
            </x-docs.card>
            <x-docs.card title="LedgerService" icon="account_balance">
                Posts balanced <strong>double-entry</strong> journal lines to <code>ledger_entries</code>
                (throws if debits &ne; credits). Every financial service posts through it, so the ledger is always
                in balance. See <a href="{{ route('admin.docs.show', 'money-flow') }}">Financial flow</a>.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="transactional" title="Transaction engines" icon="engineering">
        <x-docs.cards :cols="2">
            <x-docs.card title="SalesService" icon="point_of_sale">
                The heart of selling. <code>place(channel, customer, lines, opts)</code> creates the order +
                items (with cost snapshot), draws down stock, records a payment, and posts revenue, tax, COGS and
                receivable/cash to the ledger — in one transaction. Used by <strong>checkout, POS, vendor sales</strong>
                and quotation conversion.
            </x-docs.card>
            <x-docs.card title="PurchaseService" icon="local_shipping">
                <code>receive()</code> re-computes cost then stocks each line in, and posts inventory =
                cash + payable to the ledger. <code>cancel()</code> reverses the stock and ledger.
            </x-docs.card>
            <x-docs.card title="ProductionService" icon="precision_manufacturing">
                <code>complete()</code> consumes BOM components out of stock at cost, produces the finished unit
                in, and posts finished-inventory = raw + labour + overhead. <code>cancel()</code> reverses it.
            </x-docs.card>
            <x-docs.card title="InventoryService" icon="inventory">
                <code>adjust()</code> applies a manual stock gain/loss and posts the matching inventory /
                adjustment ledger entry, with a required reason.
            </x-docs.card>
            <x-docs.card title="BomService" icon="account_tree">
                Computes a bill-of-materials unit cost (components &times; qty &times; waste + labour + overhead,
                per output unit) — used when planning and completing production.
            </x-docs.card>
            <x-docs.card title="QuotationService" icon="request_quote">
                <code>convert()</code> turns an accepted quotation into a credit order by handing its lines to
                <code>SalesService</code>.
            </x-docs.card>
            <x-docs.card title="PricingService" icon="sell">
                Suggests retail from cost (markup %) and wholesale from retail (discount %), using the pricing
                percentages in Settings. Powers the &ldquo;Suggest from cost&rdquo; buttons on the product form.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="storefront-services" title="Storefront &amp; support engines" icon="storefront">
        <x-docs.cards :cols="2">
            <x-docs.card title="CartService" icon="shopping_cart">
                The session-based cart. Resolves stored variant ids against the live catalog on every read, so
                stale or unsellable items drop out automatically.
            </x-docs.card>
            <x-docs.card title="AbandonedCartService" icon="restore">
                Snapshots carts that stall at checkout and drives the recovery email + one-click restore link.
            </x-docs.card>
            <x-docs.card title="WishlistService / CompareService" icon="favorite">
                Session product shortlists (compare is capped at four), resolved against the live catalog.
            </x-docs.card>
            <x-docs.card title="SupportBot" icon="smart_toy">
                Backs the live customer support chat with automated first-line responses.
            </x-docs.card>
            <x-docs.card title="ErrorLogger" icon="bug_report">
                Captures unhandled exceptions into the <code>error_logs</code> table (de-duplicated, sensitive
                data redacted) so staff can review failures. Never throws itself.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.callout tone="key" title="Rule of thumb">
        If a controller is about to change stock or money, it should be calling one of these services — not
        writing to models directly. That guarantee is what keeps orders, inventory and the ledger telling the
        same story.
    </x-docs.callout>
</x-docs.prose>

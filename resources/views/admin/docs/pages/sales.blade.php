<x-docs.prose>
    <p>
        Everything that sells goods ends up as an <strong>Order</strong>. There are several ways to create one —
        the web store, the POS counter, a wholesale credit sale, or a converted quotation — but they all funnel
        through <code>SalesService</code>, so each one draws down stock, records payment and posts to the ledger
        identically.
    </p>

    <x-docs.section id="channels" title="Ways a sale happens" icon="call_split">
        <x-docs.cards :cols="2">
            <x-docs.card title="Web checkout" icon="shopping_cart_checkout">
                A customer's session cart becomes an order on the <code>web</code> channel. Payment is COD or
                bank transfer (recorded pending); shipping comes from Settings.
                <br><x-docs.pill tone="route">checkout.store</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Point of Sale (POS)" icon="point_of_sale">
                A fast counter screen (Alpine + JSON search) for in-person sales, paid in full. Prints a receipt.
                <br><x-docs.pill tone="route">admin.pos.*</x-docs.pill> <x-docs.pill tone="perm">pos.access</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Vendor / wholesale sale" icon="sell">
                A POS-style screen on the <code>vendor</code> channel at wholesale prices, with a required
                customer and deferred payment &rarr; accounts receivable.
                <br><x-docs.pill tone="route">admin.vendor-sales.*</x-docs.pill> <x-docs.pill tone="perm">orders.create</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Quotation → order" icon="request_quote">
                A quotation moves draft &rarr; sent &rarr; accepted; converting it produces a credit order via
                <code>QuotationService</code> &rarr; <code>SalesService</code>.
                <br><x-docs.pill tone="route">admin.quotations.*</x-docs.pill> <x-docs.pill tone="perm">quotations.*</x-docs.pill>
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="orders" title="Orders" icon="receipt_long">
        <p>
            Orders are <strong>read-mostly</strong> in the admin — they are created by the flows above, not typed
            in by hand. The order screens let staff view, filter and progress an order without ever editing its
            financial lines.
        </p>
        <ul>
            <li><strong>Status</strong> flows pending &rarr; processing &rarr; shipped &rarr; delivered / completed, plus cancelled and refunded. Each change is logged to the order's history.</li>
            <li><strong>Delivery</strong> (courier + tracking) and <strong>payments</strong> can be recorded against the order.</li>
            <li><strong>Print</strong> produces an A4 invoice or an 80&nbsp;mm thermal receipt depending on the Store setting.</li>
        </ul>
        <x-docs.pill tone="route">admin.orders.*</x-docs.pill>
        <x-docs.pill tone="model">Order</x-docs.pill>
        <x-docs.pill tone="model">OrderItem</x-docs.pill>
        <x-docs.pill tone="model">Payment</x-docs.pill>
        <x-docs.pill tone="perm">orders.view · edit · refund · fulfil</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="coupons" title="Coupons" icon="confirmation_number">
        <p>
            Percentage or fixed discounts with a minimum subtotal, total usage cap, per-customer cap and a
            validity window. Codes are auto-uppercased; a coupon with orders against it can't be deleted. Applied
            during checkout.
        </p>
        <x-docs.pill tone="route">admin.coupons.*</x-docs.pill>
        <x-docs.pill tone="perm">coupons.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How sales connects" icon="hub">
        <ul>
            <li><strong>&larr; Catalog:</strong> every line sells a product variant.</li>
            <li><strong>&larr; Customers:</strong> web checkout finds/creates a customer by email; POS/vendor pick one.</li>
            <li><strong>&rarr; Inventory:</strong> placing a sale draws stock down through <code>StockService</code>.</li>
            <li><strong>&rarr; Finance:</strong> revenue, tax, COGS and cash/receivable post to the ledger; Reports read the resulting orders.</li>
            <li><strong>&rarr; Marketing:</strong> a stalled checkout can trigger abandoned-cart recovery.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

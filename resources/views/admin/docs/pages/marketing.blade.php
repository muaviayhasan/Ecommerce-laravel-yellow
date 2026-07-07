<x-docs.prose>
    <p>
        Marketing is about bringing customers back. It sends email — through queued jobs so the admin never
        blocks on delivery — and recovers sales that were nearly lost at checkout.
    </p>

    <x-docs.section id="subscribers" title="Newsletter subscribers" icon="mail">
        <p>
            People who opted in via the storefront footer. The list is searchable and exportable to CSV; each has
            a one-click unsubscribe token honoured by the storefront. Deletable individually.
        </p>
        <x-docs.pill tone="route">admin.subscribers.*</x-docs.pill>
        <x-docs.pill tone="model">NewsletterSubscriber</x-docs.pill>
        <x-docs.pill tone="perm">subscribers.view · delete · export</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="campaigns" title="Email campaigns" icon="campaign">
        <p>
            Compose an email campaign and <strong>send</strong> it to subscribers. Delivery is queued and tracked
            per recipient (<code>CampaignRecipient</code>), so large sends don't tie up the request and you can
            see who received what.
        </p>
        <x-docs.pill tone="route">admin.campaigns.*</x-docs.pill>
        <x-docs.pill tone="model">EmailCampaign</x-docs.pill>
        <x-docs.pill tone="perm">campaigns.view · create · edit · delete · send</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="abandoned" title="Abandoned-cart recovery" icon="restore">
        <p>
            When a guest enters their email at checkout but doesn't complete, the cart is snapshotted. A recovery
            email (gated by the <em>abandoned cart</em> email toggle) sends a one-click link that rehydrates the
            saved cart and drops them back at checkout. The admin screen lists captured carts for review.
        </p>
        <x-docs.pill tone="route">admin.abandoned-carts.*</x-docs.pill>
        <x-docs.pill tone="model">AbandonedCart</x-docs.pill>
        <x-docs.pill tone="service">AbandonedCartService</x-docs.pill>
        <x-docs.pill tone="perm">abandoned-carts.view · delete</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="email-system" title="The email system behind it" icon="outgoing_mail">
        <p>
            All mail — verification, password reset, order receipts, campaigns, abandoned-cart reminders,
            quotation sends — flows through configurable SMTP set in <strong>Settings &rarr; Mail</strong>, with
            per-email-type toggles. Jobs run on tiered queues (high/low), with Horizon supervising in production.
        </p>
    </x-docs.section>

    <x-docs.section id="connects" title="How marketing connects" icon="hub">
        <ul>
            <li><strong>&larr; Customers &amp; Subscribers:</strong> the audience for campaigns.</li>
            <li><strong>&larr; Sales / Checkout:</strong> abandoned carts originate from stalled checkouts.</li>
            <li><strong>&rarr; Storefront:</strong> recovery links and unsubscribe tokens are handled by public routes.</li>
            <li><strong>&larr; Settings:</strong> SMTP config and per-email toggles govern what actually sends.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

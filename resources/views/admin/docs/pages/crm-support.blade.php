<x-docs.prose>
    <p>
        These modules manage the <strong>people</strong> who buy and the conversations with them: customer
        records, the reviews they leave, and the live chat they start.
    </p>

    <x-docs.section id="customers" title="Customers" icon="groups">
        <p>
            The <code>customers</code> table holds storefront buyers (distinct from staff <strong>Users</strong>).
            A customer has a type, price tier (retail/wholesale — which the sales engine uses to pick prices),
            opening balance, addresses and order history. Web checkout finds or creates a customer by email; a
            signed-in customer is linked to their user account. A customer with orders can't be deleted.
        </p>
        <x-docs.pill tone="route">admin.customers.*</x-docs.pill>
        <x-docs.pill tone="model">Customer</x-docs.pill>
        <x-docs.pill tone="model">CustomerAddress</x-docs.pill>
        <x-docs.pill tone="perm">customers.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="reviews" title="Reviews" icon="reviews">
        <p>
            Customers submit star reviews on product pages (only signed-in customers; every edit is re-moderated).
            The admin queue lets staff <strong>approve</strong>, <strong>unapprove</strong> or <strong>delete</strong>
            — approval is a simple boolean, so &ldquo;reject&rdquo; means delete. Only approved reviews show on the
            storefront and count toward a product's rating.
        </p>
        <x-docs.pill tone="route">admin.reviews.*</x-docs.pill>
        <x-docs.pill tone="model">Review</x-docs.pill>
        <x-docs.pill tone="perm">reviews.view · moderate</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="support" title="Support inbox (live chat)" icon="support_agent">
        <p>
            The storefront has a support chat widget (works for guests and logged-in customers). The admin
            <strong>Support</strong> screen is the staff side of it — a real-time inbox of conversations with
            delivery receipts, replies, and the ability to block a sender. New customer messages ring a bell and
            increment the sidebar badge live over websockets. A <code>SupportBot</code> handles automated
            first-line responses.
        </p>
        <x-docs.pill tone="route">admin.support.*</x-docs.pill>
        <x-docs.pill tone="model">SupportConversation</x-docs.pill>
        <x-docs.pill tone="model">SupportMessage</x-docs.pill>
        <x-docs.pill tone="perm">support.view · reply</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="storefront-extras" title="Wishlist & compare" icon="favorite">
        <p>
            On the storefront, customers keep a session-based <strong>wishlist</strong> and a <strong>compare</strong>
            list (capped at four). These are convenience features resolved against the live catalog; they have no
            dedicated admin screen but share the same product data.
        </p>
    </x-docs.section>

    <x-docs.section id="connects" title="How this area connects" icon="hub">
        <ul>
            <li><strong>&rarr; Sales:</strong> customers and their price tier drive order pricing.</li>
            <li><strong>&larr; Catalog:</strong> reviews, wishlist and compare all attach to products.</li>
            <li><strong>&rarr; Marketing:</strong> customer and subscriber data feed campaigns.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

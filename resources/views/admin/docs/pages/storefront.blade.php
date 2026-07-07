<x-docs.prose>
    <p>
        The public website is the customer-facing half of the system. Every page runs on <strong>real admin
        data</strong> — the same products, categories, orders and content you manage here. This page maps the
        storefront so you can see which admin module drives each part of it.
    </p>

    <x-docs.section id="pages" title="Storefront pages → admin source" icon="storefront">
        <table>
            <thead><tr><th>Page</th><th>Driven by</th></tr></thead>
            <tbody>
                <tr><td><strong>Home</strong></td><td>Hero slides, promo cards, info bar, and products by placement flag (featured / trending / bestseller / on-sale).</td></tr>
                <tr><td><strong>Shop</strong></td><td>Web-listed products with category, brand, price and sort filters.</td></tr>
                <tr><td><strong>Product detail</strong></td><td>A product's gallery, variants, price, highlights, specifications, warranty and approved reviews.</td></tr>
                <tr><td><strong>Cart</strong></td><td>Session cart (<code>CartService</code>), resolved against the live catalog.</td></tr>
                <tr><td><strong>Checkout</strong></td><td>Places a real order via <code>SalesService</code>; applies coupons, shipping and tax from Settings.</td></tr>
                <tr><td><strong>Blog</strong></td><td>Published posts, category/tag sidebars, approved comments.</td></tr>
                <tr><td><strong>Account area</strong></td><td>A signed-in customer's orders, addresses, profile and reorder.</td></tr>
                <tr><td><strong>Wishlist / Compare</strong></td><td>Session shortlists over the live catalog.</td></tr>
                <tr><td><strong>Track order</strong></td><td>Guest lookup by order number + email.</td></tr>
                <tr><td><strong>Support chat</strong></td><td>The customer side of the admin Support inbox.</td></tr>
                <tr><td><strong>Request a quote</strong></td><td>Creates a draft quotation + alerts staff.</td></tr>
                <tr><td><strong>Contact / About</strong></td><td>Informational pages; contact submissions email staff.</td></tr>
            </tbody>
        </table>
    </x-docs.section>

    <x-docs.section id="accounts" title="Two separate logins" icon="lock_person">
        <p>
            Customers and staff sign in through <strong>different doors</strong>. The storefront login and
            registration serve customers (the <code>customers</code>/user records for buyers); the admin login
            (<code>/admin/login</code>) is staff-only. Both support social sign-in when enabled in
            <strong>Settings &rarr; Social login</strong>. A customer account never grants admin access.
        </p>
    </x-docs.section>

    <x-docs.section id="seo" title="SEO & discovery" icon="travel_explore">
        <p>
            Every storefront page emits meta, Open Graph and JSON-LD tags, with per-page overrides. A dynamic
            <code>sitemap.xml</code> and <code>robots.txt</code> are generated from real content, and SEO defaults
            live in <strong>Settings &rarr; SEO</strong>.
        </p>
    </x-docs.section>

    <x-docs.section id="loop" title="The full loop" icon="loop">
        <p>
            The storefront closes the golden thread from the <a href="{{ route('admin.docs.show', 'overview') }}">overview</a>:
            a customer browses the <strong>catalog</strong>, fills a <strong>cart</strong>, and checks out — which
            creates an <strong>order</strong>, draws down <strong>stock</strong>, records a <strong>payment</strong>
            and posts to the <strong>ledger</strong>. Staff then fulfil it, and its numbers surface in
            <strong>reports</strong>. Every module in this handbook is a stop on that loop.
        </p>
    </x-docs.section>
</x-docs.prose>

<x-docs.prose>
    <p>
        This platform is a combined <strong>e-commerce storefront</strong> and <strong>back-office ERP</strong>
        built on Laravel. One codebase serves two audiences: customers who browse and buy on the public
        website, and staff who run the business from this admin panel — catalog, orders, stock, purchasing,
        manufacturing, accounting, marketing and content.
    </p>
    <p>
        Everything a customer does on the storefront flows into the same database the admin panel manages.
        A checkout becomes an <strong>order</strong>; that order draws down <strong>stock</strong>, records a
        <strong>payment</strong>, and posts to the <strong>ledger</strong> — with no manual re-entry. The
        admin panel is where staff steer and observe that flow.
    </p>

    <x-docs.callout tone="tip" title="How to read this handbook">
        Start here, then <strong>Architecture</strong> to see the layers a request travels through, then the
        <strong>Module relationship map</strong> for how the pieces wire together. The <strong>Modules</strong>
        section documents each area in depth; the <strong>Reference</strong> section lists every role and page.
    </x-docs.callout>

    <x-docs.section id="two-sides" title="Two sides, one system" icon="swap_horiz">
        <x-docs.cards :cols="2">
            <x-docs.card title="Storefront (public)" icon="storefront">
                The customer-facing website: home, shop, product pages, cart, checkout, blog, account area,
                wishlist/compare, order tracking and a live support chat. Runs on real catalog and order data.
            </x-docs.card>
            <x-docs.card title="Admin panel (staff)" icon="admin_panel_settings">
                This back office. Role-gated screens for catalog, sales, procurement, manufacturing, inventory,
                finance, marketing, content and system administration.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="areas" title="The major areas" icon="grid_view">
        <p>The admin sidebar groups the system into these areas. Each has its own page in this handbook:</p>
        <x-docs.cards :cols="3">
            <x-docs.card title="Catalog" icon="shopping_bag" :href="route('admin.docs.show', 'catalog')">
                Products &amp; variants, categories, brands, attributes, gallery, and home-page content
                (hero slides, promo cards, info bar).
            </x-docs.card>
            <x-docs.card title="Sales &amp; orders" icon="point_of_sale" :href="route('admin.docs.show', 'sales')">
                Web checkout, POS, vendor sales, quotations, coupons and the order lifecycle.
            </x-docs.card>
            <x-docs.card title="Procurement" icon="local_shipping" :href="route('admin.docs.show', 'procurement')">
                Suppliers, purchase orders, receiving and live stock levels.
            </x-docs.card>
            <x-docs.card title="Manufacturing" icon="precision_manufacturing" :href="route('admin.docs.show', 'manufacturing')">
                Bills of materials and production runs that turn components into finished goods.
            </x-docs.card>
            <x-docs.card title="Customers &amp; support" icon="groups" :href="route('admin.docs.show', 'crm-support')">
                Customer records, review moderation and the live support inbox.
            </x-docs.card>
            <x-docs.card title="Marketing" icon="campaign" :href="route('admin.docs.show', 'marketing')">
                Email campaigns, newsletter subscribers and abandoned-cart recovery.
            </x-docs.card>
            <x-docs.card title="Blog" icon="article" :href="route('admin.docs.show', 'blog')">
                Posts, categories, tags and comment moderation.
            </x-docs.card>
            <x-docs.card title="Finance" icon="account_balance" :href="route('admin.docs.show', 'finance')">
                A double-entry ledger and an analytics/reporting dashboard.
            </x-docs.card>
            <x-docs.card title="System" icon="shield" :href="route('admin.docs.show', 'system')">
                Settings, staff users, roles &amp; permissions, activity log and error logs.
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="golden-thread" title="The golden thread" icon="timeline">
        <p>
            If you remember one thing, remember this chain — it ties the whole system together and appears
            again and again in the pages that follow:
        </p>
        <p class="text-center font-semibold text-on-surface bg-surface-container-high rounded-lg py-3 px-4">
            Catalog &rarr; Sale (checkout / POS) &rarr; Order &rarr; Stock movement &rarr; Payment &rarr; Ledger &rarr; Reports
        </p>
        <p>
            Purchasing and manufacturing feed <em>into</em> stock at the start of that chain; finance and
            reporting read from the <em>end</em> of it. Nearly every module is a station on this line.
        </p>
    </x-docs.section>
</x-docs.prose>

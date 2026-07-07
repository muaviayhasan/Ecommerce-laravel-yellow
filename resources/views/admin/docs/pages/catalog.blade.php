<x-docs.prose>
    <p>
        The catalog defines <strong>what you sell</strong> and <strong>how the storefront looks</strong>. It is
        the upstream hub for almost everything: sales, stock, purchasing and manufacturing all reference a
        product variant that originates here.
    </p>

    <x-docs.section id="products" title="Products & variants" icon="inventory_2">
        <p>
            A <strong>Product</strong> is the catalog listing; a <strong>ProductVariant</strong> is the actual
            sellable, stockable, priced unit. Products come in two modes:
        </p>
        <ul>
            <li><strong>Simple</strong> — one hidden default variant carries the price and stock.</li>
            <li><strong>Variable</strong> — pick variation attributes (e.g. Colour, Size), generate the matrix of combinations, and price/stock each variant row. One is marked default.</li>
        </ul>
        <p>
            The product form also holds images (from the Gallery), key features, grouped specifications, warranty
            and storefront-placement toggles: <strong>active, web-listed, published, featured, trending,
            bestseller</strong> — each labelled with the home-page section it drives.
        </p>
        <x-docs.pill tone="route">admin.products.*</x-docs.pill>
        <x-docs.pill tone="model">Product</x-docs.pill>
        <x-docs.pill tone="model">ProductVariant</x-docs.pill>
        <x-docs.pill tone="perm">products.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="taxonomy" title="Categories, brands & attributes" icon="sell">
        <x-docs.cards :cols="3">
            <x-docs.card title="Categories" icon="account_tree">
                Hierarchical (parent/child). Drive shop navigation and filtering. Deleting a category with
                products is blocked; children re-parent.
                <br><x-docs.pill tone="perm">categories.*</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Brands" icon="branding_watermark">
                Logo (from Gallery) + SEO. A product belongs to a brand; delete is blocked if products use it.
                <br><x-docs.pill tone="perm">brands.*</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Attributes" icon="tune">
                Named options with values (select / swatch / radio). Attributes flagged <em>is-variation</em>
                power the variant matrix on variable products.
                <br><x-docs.pill tone="perm">attributes.*</x-docs.pill>
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="storefront-content" title="Home-page content" icon="web">
        <p>These three modules let staff edit the storefront home page without touching code:</p>
        <x-docs.cards :cols="3">
            <x-docs.card title="Hero slides" icon="view_carousel">The rotating banner at the top of the home page. <br><x-docs.pill tone="perm">hero-slides.*</x-docs.pill></x-docs.card>
            <x-docs.card title="Promo cards" icon="dashboard">The promotional tiles/blocks on the home page. <br><x-docs.pill tone="perm">promo-cards.*</x-docs.pill></x-docs.card>
            <x-docs.card title="Info bar" icon="info">The thin announcement strip (shipping, offers, notices). <br><x-docs.pill tone="perm">info-bar-items.*</x-docs.pill></x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="gallery" title="Gallery (media library)" icon="image">
        <p>
            One shared image library (a Livewire screen) manages the <code>media</code> table: drag-and-drop
            upload, search, folders, and per-asset title/alt editing. <strong>Every</strong> image picker in the
            admin — products, brands, hero slides, promo cards, blog covers — chooses from here, so images are
            uploaded once and reused.
        </p>
        <x-docs.pill tone="route">admin.gallery.index</x-docs.pill>
        <x-docs.pill tone="model">Media</x-docs.pill>
        <x-docs.pill tone="perm">gallery.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How catalog connects" icon="hub">
        <ul>
            <li><strong>&rarr; Storefront:</strong> web-listed products and the placement flags render the shop, home and product pages.</li>
            <li><strong>&rarr; Sales:</strong> checkout, POS, quotations and vendor sales all sell a variant defined here.</li>
            <li><strong>&rarr; Procurement / Manufacturing:</strong> purchases and BOMs reference variants; receiving and production update their stock.</li>
            <li><strong>&larr; Gallery:</strong> images come from the shared media library.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

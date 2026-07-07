<x-docs.prose>
    <p>
        The blog is the content-marketing side of the storefront. It is a self-contained publishing area:
        posts, the taxonomies they're filed under, and the comments readers leave.
    </p>

    <x-docs.section id="posts" title="Posts" icon="article">
        <p>
            A <strong>BlogPost</strong> has a cover and social image (from the Gallery), a draft/published state
            (publishing stamps the date automatically), SEO fields with a noindex option, and many-to-many links
            to <strong>categories</strong> and <strong>tags</strong>. Only published posts appear on the
            storefront blog.
        </p>
        <x-docs.pill tone="route">admin.blog.posts.*</x-docs.pill>
        <x-docs.pill tone="model">BlogPost</x-docs.pill>
        <x-docs.pill tone="perm">blog-posts.*</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="taxonomy" title="Categories & tags" icon="label">
        <x-docs.cards :cols="2">
            <x-docs.card title="Blog categories" icon="folder">
                Hierarchical groupings for posts, managed inline (lightweight create/edit) and re-orderable.
                <br><x-docs.pill tone="perm">blog-categories.*</x-docs.pill>
            </x-docs.card>
            <x-docs.card title="Blog tags" icon="tag">
                Flat keywords attached to posts.
                <br><x-docs.pill tone="perm">blog-tags.*</x-docs.pill>
            </x-docs.card>
        </x-docs.cards>
    </x-docs.section>

    <x-docs.section id="comments" title="Comment moderation" icon="forum">
        <p>
            Readers submit comments on posts (rate-limited on the storefront). Staff <strong>approve</strong>,
            <strong>reply</strong> to, or <strong>delete</strong> them from the admin. Only approved comments show
            publicly.
        </p>
        <x-docs.pill tone="route">admin.blog.comments.*</x-docs.pill>
        <x-docs.pill tone="model">BlogComment</x-docs.pill>
        <x-docs.pill tone="perm">blog-comments.view · moderate · reply · delete</x-docs.pill>
    </x-docs.section>

    <x-docs.section id="connects" title="How the blog connects" icon="hub">
        <ul>
            <li><strong>&larr; Gallery:</strong> cover and OG images come from the shared media library.</li>
            <li><strong>&rarr; Storefront:</strong> published posts, category/tag sidebars and approved comments render the public blog.</li>
            <li><strong>&larr; Settings:</strong> SEO defaults and sitemap inclusion apply to posts.</li>
        </ul>
    </x-docs.section>
</x-docs.prose>

{{--
    Typographic wrapper for documentation body content. Styles plain semantic
    HTML (p, h3, h4, ul, ol, code, strong, a, table) via child selectors so the
    page partials stay close to readable prose. Pair with <x-docs.callout>,
    <x-docs.cards>, <x-docs.steps> and <x-docs.pill> for richer blocks.
--}}
<div {{ $attributes->merge(['class' => implode(' ', [
    'max-w-none',
    '[&_p]:text-[15px] [&_p]:leading-relaxed [&_p]:text-on-surface-variant [&_p]:my-3',
    '[&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-on-surface [&_h3]:mt-10 [&_h3]:mb-3 [&_h3]:scroll-mt-24',
    '[&_h4]:text-base [&_h4]:font-semibold [&_h4]:text-on-surface [&_h4]:mt-6 [&_h4]:mb-2',
    '[&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_ul]:my-3 [&_ul]:text-[15px] [&_ul]:text-on-surface-variant',
    '[&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-2 [&_ol]:my-3 [&_ol]:text-[15px] [&_ol]:text-on-surface-variant',
    '[&_li]:leading-relaxed [&_li>strong]:text-on-surface',
    '[&_a]:text-primary [&_a]:font-medium hover:[&_a]:underline',
    '[&_strong]:font-semibold [&_strong]:text-on-surface',
    '[&_code]:font-mono [&_code]:text-[12.5px] [&_code]:bg-surface-container-high [&_code]:text-on-surface [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded [&_code]:whitespace-nowrap',
    '[&_hr]:my-8 [&_hr]:border-outline-variant/60',
    '[&_table]:w-full [&_table]:text-sm [&_table]:my-4',
    '[&_th]:text-left [&_th]:text-[11px] [&_th]:font-bold [&_th]:uppercase [&_th]:tracking-widest [&_th]:text-outline [&_th]:px-3 [&_th]:py-2 [&_th]:border-b [&_th]:border-outline-variant/60',
    '[&_td]:px-3 [&_td]:py-2.5 [&_td]:align-top [&_td]:text-on-surface-variant [&_td]:border-b [&_td]:border-outline-variant/40',
]) ]) }}>
    {{ $slot }}
</div>

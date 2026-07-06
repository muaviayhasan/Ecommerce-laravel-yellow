@php
    $values = [
        'subject' => $campaign->subject,
        'preheader' => $campaign->preheader,
        'audience' => $campaign->audience ?? 'subscribers',
        'coupon_id' => $campaign->coupon_id,
        'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d\TH:i'),
        'body' => $campaign->body,
    ];

    $sections = [
        [
            'title' => 'Campaign',
            'fields' => [
                'subject' => ['input' => 'text', 'label' => 'Subject', 'max' => 255, 'placeholder' => 'Big weekend sale — 20% off everything'],
                'preheader' => ['input' => 'text', 'label' => 'Preview text', 'max' => 255, 'help' => 'The grey preview line shown next to the subject in the inbox.'],
                'audience' => ['input' => 'select', 'label' => 'Send to', 'options' => [
                    'all_customers' => 'All customers',
                    'retail' => 'Retail customers',
                    'wholesale' => 'Wholesale customers',
                    'subscribers' => 'Newsletter subscribers',
                ]],
                'coupon_id' => ['input' => 'select', 'label' => 'Attach a coupon', 'options' => ['' => 'No coupon'] + $coupons, 'help' => 'Shown as a highlighted code block in the email.'],
                'scheduled_at' => ['input' => 'datetime-local', 'label' => 'Schedule for', 'help' => 'Optional. Leave blank to send manually with the Send button.'],
            ],
        ],
        [
            'title' => 'Message',
            'description' => 'You can use these merge tags: {{ name }}, {{ coupon_code }}, {{ unsubscribe_url }}. Basic HTML is allowed. An unsubscribe link is always added automatically.',
            'fields' => [
                'body' => ['input' => 'textarea', 'label' => 'Body', 'rows' => 12, 'max' => 100000, 'placeholder' => "Hi {{ name }},\n\nWe’ve got something special for you this week…"],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <x-settings.section :title="$section['title']" :description="$section['description'] ?? null">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($section['fields'] as $name => $meta)
                    <div @class(['md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true)])>
                        <x-settings.field group="campaign" :name="$name" :meta="$meta" :value="$values[$name]" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>

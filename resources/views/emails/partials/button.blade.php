{{-- Bulletproof-ish CTA button. Params: $url, $label, optional $align (default center). --}}
@php $align = $align ?? 'center'; @endphp
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;" align="{{ $align }}">
    <tr>
        <td style="border-radius:10px; background:#6f5d00;">
            <a href="{{ $url }}"
                style="display:inline-block; padding:13px 28px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>

@php
    // Branding pulled from admin settings; falls back to sensible defaults.
    $storeName = (string) setting('general', 'app_name', config('app.name', 'Our Store'));
    $supportEmail = setting('store', 'support_email') ?: setting('mail', 'from_address');
    $address = setting('store', 'address');
    $phone = setting('store', 'phone');
    $siteUrl = rtrim(config('app.url'), '/');

    // Palette mirrors the storefront theme (gold / yellow).
    $brand = '#6f5d00';
    $accent = '#fed700';
    $ink = '#1c1b16';
    $muted = '#77746a';
    $border = '#e7e4d6';
    $pageBg = '#f4f3ea';
    $cardBg = '#ffffff';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $storeName }}</title>
    <style>
        /* A few clients honour <style>; everything critical is also inlined. */
        body { margin: 0; padding: 0; width: 100% !important; background: {{ $pageBg }}; }
        a { color: {{ $brand }}; }
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 24px !important; padding-right: 24px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:{{ $pageBg }}; -webkit-font-smoothing:antialiased; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    {{-- Preheader: the grey preview line shown in the inbox, kept off-screen. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
        @yield('preheader', $storeName)
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $pageBg }};">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0"
                    style="width:600px; max-width:600px; background:{{ $cardBg }}; border:1px solid {{ $border }}; border-radius:16px; overflow:hidden;">

                    {{-- Brand bar --}}
                    <tr><td style="height:5px; background:{{ $accent }}; line-height:5px; font-size:5px;">&nbsp;</td></tr>

                    {{-- Header --}}
                    <tr>
                        <td class="px" style="padding:28px 40px 8px 40px;">
                            <a href="{{ $siteUrl }}" style="text-decoration:none; color:{{ $ink }}; font-size:22px; font-weight:800; letter-spacing:-0.02em;">
                                {{ $storeName }}
                            </a>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="px" style="padding:16px 40px 36px 40px; color:{{ $ink }}; font-size:15px; line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="px" style="padding:24px 40px 32px 40px; background:{{ $pageBg }}; border-top:1px solid {{ $border }}; color:{{ $muted }}; font-size:12px; line-height:1.6;">
                            <strong style="color:{{ $ink }};">{{ $storeName }}</strong><br>
                            @if ($address){{ $address }}<br>@endif
                            @if ($phone)Phone: {{ $phone }}<br>@endif
                            @if ($supportEmail)
                                Need help? <a href="mailto:{{ $supportEmail }}" style="color:{{ $brand }}; text-decoration:none;">{{ $supportEmail }}</a><br>
                            @endif
                            @hasSection('footer_extra')
                                <div style="margin-top:12px;">@yield('footer_extra')</div>
                            @endif
                            <div style="margin-top:12px; color:{{ $muted }};">© {{ date('Y') }} {{ $storeName }}. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

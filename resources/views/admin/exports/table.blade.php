<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title }}</title>
        @php($template = $branding['template']['content'] ?? [])
        <style>
            @page { margin: 34px 28px 30px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: {{ $template['text_primary'] ?? '#24303f' }}; }
            .document-shell { width: 100%; }
            .header-wrap {
                margin-bottom: 20px;
                padding: 18px 20px 16px;
                border: 1px solid {{ $template['border'] ?? '#d7dce5' }};
                border-radius: 18px;
                background: {{ $template['surface'] ?? '#f8fafc' }};
            }
            .header-table { width: 100%; border-collapse: collapse; }
            .header-table td { border: none; padding: 0; vertical-align: top; }
            .brand-cell { width: 78px; }
            .brand-mark {
                width: 58px;
                height: 58px;
                border-radius: 16px;
                background: linear-gradient(135deg, {{ $template['accent_start'] ?? '#9d3948' }} 0%, {{ $template['accent_end'] ?? '#c95b69' }} 100%);
                text-align: center;
                color: #fff7f8;
                font-size: 20px;
                font-weight: bold;
                line-height: 58px;
            }
            .brand-logo {
                width: 58px;
                height: 58px;
                border-radius: 16px;
                object-fit: contain;
                background: #ffffff;
                border: 1px solid #e4e7ec;
                padding: 8px;
            }
            .header-kicker {
                margin: 0 0 6px;
                color: {{ $template['accent_start'] ?? '#9d3948' }};
                font-size: 10px;
                font-weight: bold;
                letter-spacing: 0.24em;
                text-transform: uppercase;
            }
            .header-title { margin: 0; font-size: 24px; color: {{ $template['text_primary'] ?? '#18212f' }}; }
            .header-subtitle { margin: 7px 0 0; color: {{ $template['text_muted'] ?? '#617084' }}; font-size: 12px; }
            .meta-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            .meta-table td {
                border: none;
                padding: 0 18px 0 0;
                color: {{ $template['text_muted'] ?? '#617084' }};
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            .meta-value {
                display: block;
                margin-top: 4px;
                color: {{ $template['text_primary'] ?? '#18212f' }};
                font-size: 11px;
                font-weight: bold;
                letter-spacing: 0;
                text-transform: none;
            }
            .data-table { width: 100%; border-collapse: collapse; }
            .data-table th, .data-table td { border: 1px solid {{ $template['border'] ?? '#d9dee7' }}; padding: 8px; text-align: left; vertical-align: top; }
            .data-table th {
                background: {{ $template['heading_background'] ?? '#eef2f7' }};
                color: {{ $template['heading_text'] ?? '#455468' }};
                font-size: 10px;
                font-weight: bold;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }
            .data-table tbody tr:nth-child(even) td { background: #fbfcfe; }
        </style>
    </head>
    <body>
        <div class="document-shell">
            <div class="header-wrap">
                <table class="header-table">
                    <tr>
                        <td class="brand-cell">
                            @if (! empty($branding['logo_data_uri']))
                                <img src="{{ $branding['logo_data_uri'] }}" alt="{{ $branding['project_title'] ?? config('app.name') }}" class="brand-logo">
                            @else
                                <div class="brand-mark">{{ $branding['project_initials'] ?? 'EX' }}</div>
                            @endif
                        </td>
                        <td>
                            <p class="header-kicker">{{ $template['kicker'] ?? ($branding['project_title'] ?? config('app.name')) }}</p>
                            <h1 class="header-title">{{ $template['title'] ?? $title }}</h1>
                            @if (! empty($template['subtitle'] ?? $subtitle))
                                <p class="header-subtitle">{{ $template['subtitle'] ?? $subtitle }}</p>
                            @endif

                            <table class="meta-table">
                                <tr>
                                    <td>
                                        Exported
                                        <span class="meta-value">{{ $branding['generated_at'] ?? now()->format('M d, Y g:i A') }}</span>
                                    </td>
                                    @foreach (($meta ?? []) as $label => $value)
                                        <td>
                                            {{ $label }}
                                            <span class="meta-value">{{ $value }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </body>
</html>
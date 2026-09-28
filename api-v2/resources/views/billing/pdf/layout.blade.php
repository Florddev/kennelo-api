{{-- Mise en page commune des factures et des avoirs. Tout vient de la facture figée : rien n'est relu ailleurs. --}}
@php
    use App\Support\Money;

    $kennelo = $invoice->issuer_details['billing_mandate']['issued_by'] ?? $invoice->issuer_details;
    $currency = $invoice->currency === 'EUR' ? '€' : $invoice->currency;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title') {{ $invoice->number }}</title>
    <style>
        @page { margin: 16mm 16mm 22mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8.5pt; line-height: 1.45; color: #1d2a2e; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #5b6a70; }
        .label { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.8px; color: #5b6a70; margin-bottom: 1.5mm; }
        .issuer-name { font-size: 13pt; font-weight: bold; color: #17403f; }
        .title { font-size: 18pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #17403f; text-align: right; }
        .meta { text-align: right; }
        .meta strong { font-size: 10pt; }
        .parties { margin-top: 9mm; }
        .parties td { width: 50%; padding-right: 8mm; }
        .party-name { font-weight: bold; font-size: 9.5pt; }
        .notice { margin-top: 6mm; padding: 2.5mm 3.5mm; background: #eef3f2; border-left: 2px solid #17403f; }
        .lines { margin-top: 7mm; }
        .lines th { font-size: 7pt; font-weight: normal; text-transform: uppercase; letter-spacing: 0.6px; color: #5b6a70; text-align: left; padding: 1.5mm 1.5mm 2mm; border-bottom: 1px solid #1d2a2e; }
        .lines td { padding: 2.5mm 1.5mm; border-bottom: 1px solid #d5dedd; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 46%; margin-top: 4mm; margin-left: 54%; }
        .totals td { padding: 1.2mm 1.5mm; }
        .totals .grand td { border-top: 1px solid #1d2a2e; padding-top: 2mm; font-size: 10.5pt; font-weight: bold; }
        .mentions { margin-top: 9mm; }
        .mentions p { margin: 0 0 1.5mm; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 6.5pt; color: #5b6a70; text-align: center; }
    </style>
</head>
<body>
    <div class="footer">
        {{ collect([$kennelo['name'] ?? null, $kennelo['legal_form'] ?? null])->filter()->join(', ') }}
        @if (! empty($kennelo['siren'])) · {{ __('billing.pdf.siren', ['value' => $kennelo['siren']]) }} @endif
        @if (! empty($kennelo['vat_number'])) · {{ __('billing.pdf.vat_number', ['value' => $kennelo['vat_number']]) }} @endif
    </div>

    <table>
        <tr>
            <td>
                <div class="issuer-name">{{ $invoice->issuer_details['name'] ?? '' }}</div>
                @if ($invoice->booking_id !== null)
                    <div class="muted">{{ __('billing.pdf.booking', ['reference' => mb_strtoupper(mb_substr($invoice->booking_id, 0, 8))]) }}</div>
                @endif
            </td>
            <td class="meta">
                <div class="title">@yield('title')</div>
                <div><strong>{{ __('billing.pdf.number', ['number' => $invoice->number]) }}</strong></div>
                <div>{{ __('billing.pdf.issued_on', ['date' => $issuedOn]) }}</div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                @include('billing.pdf.party', ['label' => __('billing.pdf.issuer'), 'details' => $invoice->issuer_details])
            </td>
            <td>
                @include('billing.pdf.party', ['label' => __('billing.pdf.recipient'), 'details' => $invoice->recipient_details])
            </td>
        </tr>
    </table>

    @yield('notice')

    @if ($mandateAcceptedOn !== null)
        <div class="notice">
            {{ __('billing.pdf.mandate.'.$invoice->type->value, ['kennelo' => $kennelo['name'] ?? '', 'issuer' => $invoice->issuer_details['name'] ?? '', 'date' => $mandateAcceptedOn]) }}
        </div>
    @endif

    @if ($periodStart !== null)
        <div class="notice">{{ __('billing.pdf.period', ['start' => $periodStart, 'end' => $periodEnd]) }}</div>
    @endif

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('billing.pdf.columns.description') }}</th>
                <th class="num">{{ __('billing.pdf.columns.quantity') }}</th>
                <th class="num">{{ __('billing.pdf.columns.unit_price_ttc') }}</th>
                <th class="num">{{ __('billing.pdf.columns.vat_rate') }}</th>
                <th class="num">{{ __('billing.pdf.columns.total_ht') }}</th>
                <th class="num">{{ __('billing.pdf.columns.total_ttc') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ Money::format($line->quantity) }}</td>
                    <td class="num">{{ Money::format($line->unit_price_ttc) }} {{ $currency }}</td>
                    <td class="num">{{ Money::format($line->vat_rate) }} %</td>
                    <td class="num">{{ Money::format($line->total_ht) }} {{ $currency }}</td>
                    <td class="num">{{ Money::format($line->total_ttc) }} {{ $currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('billing.pdf.totals.ht') }}</td>
            <td class="num">{{ Money::format($invoice->total_ht) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td>{{ __('billing.pdf.totals.vat') }}</td>
            <td class="num">{{ Money::format($invoice->total_vat) }} {{ $currency }}</td>
        </tr>
        <tr class="grand">
            <td>{{ __('billing.pdf.totals.ttc') }}</td>
            <td class="num">{{ Money::format($invoice->total_ttc) }} {{ $currency }}</td>
        </tr>
    </table>

    <div class="mentions">
        @if ($invoice->vat_mention !== null)
            <p><strong>{{ $invoice->vat_mention }}</strong></p>
        @endif
        <p>{{ __('billing.pdf.settlement.'.$settlement) }}</p>
    </div>
</body>
</html>

{{-- Émetteur ou destinataire, tel que copié dans la facture. --}}
<div class="label">{{ $label }}</div>
<div class="party-name">{{ $details['name'] ?? '' }}</div>
@if (! empty($details['address']))
    @foreach (['line1', 'line2'] as $key)
        @if (! empty($details['address'][$key]))
            <div>{{ $details['address'][$key] }}</div>
        @endif
    @endforeach
    <div>{{ trim(($details['address']['postal_code'] ?? '').' '.($details['address']['city'] ?? '')) }}</div>
    @if (! empty($details['address']['country']))
        <div>{{ $details['address']['country'] }}</div>
    @endif
@endif
@if (! empty($details['siret']))
    <div class="muted">{{ __('billing.pdf.siret', ['value' => $details['siret']]) }}</div>
@elseif (! empty($details['siren']))
    <div class="muted">{{ __('billing.pdf.siren', ['value' => $details['siren']]) }}</div>
@endif
@if (! empty($details['vat_number']))
    <div class="muted">{{ __('billing.pdf.vat_number', ['value' => $details['vat_number']]) }}</div>
@endif
@if (! empty($details['email']))
    <div class="muted">{{ $details['email'] }}</div>
@endif

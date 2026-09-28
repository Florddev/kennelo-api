{{-- Avoir : il renvoie à la facture qu'il annule, en tout ou en partie. --}}
@extends('billing.pdf.layout')

@section('title', __('billing.pdf.title.credit_note'))

@section('notice')
    @if ($invoice->creditedInvoice !== null)
        <div class="notice">
            {{ __('billing.pdf.credits', ['number' => $invoice->creditedInvoice->number, 'date' => $creditedOn]) }}
        </div>
    @endif
@endsection

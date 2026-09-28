<?php

declare(strict_types=1);

return [
    'mentions' => [
        'vat_franchise' => 'VAT not applicable, article 293 B of the French General Tax Code',
    ],
    'lines' => [
        'stay' => 'Stay (:unit) from :start to :end',
        'travel_fee' => 'Travel fee',
        'item' => ':service',
        'item_pet' => ':service for :pet',
        'item_scheduled' => ':service, on :date at :time',
        'item_pet_scheduled' => ':service for :pet, on :date at :time',
        'service_fee' => 'Kennelo service fee, booking at :activity on :start',
        'commission' => 'Kennelo commission, booking :reference at :activity from :start to :end',
        'refund' => [
            'client_cancellation' => 'Refund after cancellation by the client (invoice :number)',
            'pro_cancellation' => 'Refund after cancellation by the professional (invoice :number)',
            'adjustment' => 'Refund of a removed service (invoice :number)',
        ],
    ],
    'pdf' => [
        'title' => [
            'invoice' => 'Invoice',
            'credit_note' => 'Credit note',
        ],
        'number' => 'No. :number',
        'issued_on' => 'Date: :date',
        'booking' => 'Booking :reference',
        'issuer' => 'Issuer',
        'recipient' => 'Recipient',
        'siren' => 'SIREN :value',
        'siret' => 'SIRET :value',
        'vat_number' => 'VAT number :value',
        'mandate' => [
            'invoice' => 'Invoice issued by :kennelo in the name and on behalf of :issuer, under the billing mandate accepted on :date.',
            'credit_note' => 'Credit note issued by :kennelo in the name and on behalf of :issuer, under the billing mandate accepted on :date.',
        ],
        'credits' => 'Credit note for invoice no. :number of :date.',
        'period' => 'Period: from :start to :end.',
        'columns' => [
            'description' => 'Description',
            'quantity' => 'Qty',
            'unit_price_ttc' => 'Unit price incl. VAT',
            'vat_rate' => 'VAT',
            'total_ht' => 'Total excl. VAT',
            'total_ttc' => 'Total incl. VAT',
        ],
        'totals' => [
            'ht' => 'Total excl. VAT',
            'vat' => 'VAT',
            'ttc' => 'Total incl. VAT',
        ],
        'settlement' => [
            'paid' => 'Invoice settled: paid online when booking.',
            'withheld' => 'Amount withheld from the payouts of the period.',
            'refunded' => 'Amount refunded to the original payment method.',
        ],
    ],
];

<?php

declare(strict_types=1);

use App\Services\Billing\InvoiceCalculator;

it('computes a line from its price including VAT', function (string $unitPrice, string $quantity, string $rate, array $expected) {
    expect(InvoiceCalculator::line($unitPrice, $quantity, $rate))->toBe($expected);
})->with([
    'VAT exemption' => ['30.00', '2', '0.00', ['total_ht' => '60.00', 'total_vat' => '0.00', 'total_ttc' => '60.00']],
    'standard rate' => ['12.00', '1', '20.00', ['total_ht' => '10.00', 'total_vat' => '2.00', 'total_ttc' => '12.00']],
    'VAT rounded' => ['10.00', '1', '20.00', ['total_ht' => '8.33', 'total_vat' => '1.67', 'total_ttc' => '10.00']],
    'half a cent' => ['3.35', '3', '20.00', ['total_ht' => '8.38', 'total_vat' => '1.67', 'total_ttc' => '10.05']],
]);

it('adds the lines up without rounding the invoice again', function () {
    $lines = array_fill(0, 3, InvoiceCalculator::line('10.00', '1', '20.00'));

    // 30,00 € TTC à 20 % donneraient 25,00 € HT d'un bloc ; ligne par ligne, 3 × 8,33 €.
    expect(InvoiceCalculator::totals($lines))->toBe(['total_ht' => '24.99', 'total_vat' => '5.01', 'total_ttc' => '30.00']);
});

it('adds lines at different rates', function () {
    $totals = InvoiceCalculator::totals([
        InvoiceCalculator::line('10.00', '1', '20.00'),
        InvoiceCalculator::line('10.00', '1', '5.50'),
    ]);

    expect($totals)->toBe(['total_ht' => '17.81', 'total_vat' => '2.19', 'total_ttc' => '20.00']);
});

it('totals nothing to zero', function () {
    expect(InvoiceCalculator::totals([]))->toBe(['total_ht' => '0.00', 'total_vat' => '0.00', 'total_ttc' => '0.00']);
});

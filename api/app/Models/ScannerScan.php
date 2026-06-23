<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScannerScan extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'scanner_id',
        'pet_id',
        'microchip_number',
        'found',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}

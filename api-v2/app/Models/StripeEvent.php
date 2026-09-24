<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Événement Stripe reçu par le webhook. Sert à ne traiter chaque événement qu'une fois :
 * Stripe peut livrer plusieurs fois le même.
 */
class StripeEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
